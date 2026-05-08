<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use App\Services\ApiKeys\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyExchangeTest extends TestCase
{
    use RefreshDatabase;

    private function assertApiKeysFeatureEnabled(): void
    {
        $this->assertTrue(
            (bool) config('kaan.features.api_keys', false),
            'KAAN_FEATURE_API_KEYS debe estar true para correr tests del exchange (rutas gated).'
        );
    }

    private function makeServiceAccount(): User
    {
        /** @var User $svc */
        $svc = User::factory()->create([
            'name' => 'Svc - ERP',
            'email' => 'svc-erp@service.local',
            'status' => 'active',
            'password' => 'Secret123456',
            'email_verified_at' => now(),
        ]);

        $svc->type = 'service';
        $svc->save();

        return $svc;
    }

    public function test_missing_header_returns_401(): void
    {
        $this->assertApiKeysFeatureEnabled();

        $this->postJson('/api/v1/auth/api-keys/exchange')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_API_KEY_MISSING')
            ->assertJsonStructure(['error' => ['code','message','details']])
            ->assertJsonPath('error.details', null);
    }

    public function test_invalid_key_returns_401(): void
    {
        $this->assertApiKeysFeatureEnabled();

        $secret = 'kk_live_invalid_' . bin2hex(random_bytes(8));

        $this->withHeaders(['X-API-Key' => $secret])
            ->postJson('/api/v1/auth/api-keys/exchange')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_API_KEY_INVALID')
            ->assertJsonPath('error.details', null);
    }

    public function test_revoked_key_returns_401(): void
    {
        $this->assertApiKeysFeatureEnabled();
        config(['kaan.api_keys.exchange.verbose_errors' => false]);

        $svc = $this->makeServiceAccount();

        /** @var ApiKeyService $service */
        $service = app(ApiKeyService::class);

        $gen = $service->generate($svc, 'Key 1', [], null, null);
        /** @var ApiKey $apiKey */
        $apiKey = $gen['api_key'];
        $secret = $gen['secret'];

        $apiKey->update(['revoked_at' => now()]);

        $this->withHeaders(['X-API-Key' => $secret])
            ->postJson('/api/v1/auth/api-keys/exchange')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_API_KEY_INVALID')
            ->assertJsonPath('error.details', null);
    }

    public function test_expired_key_returns_401(): void
    {
        $this->assertApiKeysFeatureEnabled();
        config(['kaan.api_keys.exchange.verbose_errors' => false]);

        $svc = $this->makeServiceAccount();

        /** @var ApiKeyService $service */
        $service = app(ApiKeyService::class);

        $gen = $service->generate($svc, 'Key 1', [], now()->subMinute(), null);
        $secret = $gen['secret'];

        $this->withHeaders(['X-API-Key' => $secret])
            ->postJson('/api/v1/auth/api-keys/exchange')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_API_KEY_INVALID')
            ->assertJsonPath('error.details', null);
    }

    public function test_valid_key_exchanges_to_jwt_and_creates_auth_session(): void
    {
        $this->assertApiKeysFeatureEnabled();

        $svc = $this->makeServiceAccount();

        /** @var ApiKeyService $service */
        $service = app(ApiKeyService::class);

        $gen = $service->generate($svc, 'Key 1', [], null, null);
        /** @var ApiKey $apiKey */
        $apiKey = $gen['api_key'];
        $secret = $gen['secret'];

        $res = $this->withHeaders(['X-API-Key' => $secret])
            ->postJson('/api/v1/auth/api-keys/exchange');

        $res->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure([
                'ok',
                'data' => ['token_type', 'access_token', 'expires_in', 'user'],
            ])
            ->assertJsonPath('data.token_type', 'bearer')
            ->assertJsonPath('data.user.id', $svc->public_id);

        $token = $res->json('data.access_token');
        $this->assertIsString($token);

        // Prueba real del kernel: jwt.not_revoked + auth_sessions debe permitir /me
        $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.id', $svc->public_id);

        // last_used_at best-effort: no exigimos exactitud, pero sí que NO se filtró hash y que existe key
        $this->assertDatabaseHas('api_keys', [
            'id' => $apiKey->id,
            'user_id' => $svc->id,
        ]);
    }

    public function test_rate_limit_returns_429_with_retry_after(): void
    {
        $this->assertApiKeysFeatureEnabled();

        config([
            'kaan.api_keys.exchange.attempts' => 2,
            'kaan.api_keys.exchange.decay_seconds' => 60,
        ]);

        $secret = 'kk_live_invalid_' . bin2hex(random_bytes(12));

        // 1
        $this->withHeaders(['X-API-Key' => $secret])
            ->postJson('/api/v1/auth/api-keys/exchange')
            ->assertStatus(401);

        // 2
        $this->withHeaders(['X-API-Key' => $secret])
            ->postJson('/api/v1/auth/api-keys/exchange')
            ->assertStatus(401);

        // 3 -> bloqueado
        $third = $this->withHeaders(['X-API-Key' => $secret])
            ->postJson('/api/v1/auth/api-keys/exchange');

        $third->assertStatus(429)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_API_KEY_TOO_MANY_ATTEMPTS')
            ->assertJsonPath('error.details', null);

        $this->assertTrue($third->headers->has('Retry-After'));
        $this->assertNotEmpty($third->headers->get('Retry-After'));
    }

    public function test_inactive_service_account_returns_403(): void
    {
        $this->assertApiKeysFeatureEnabled();

        $svc = $this->makeServiceAccount();
        $svc->status = 'suspended';
        $svc->save();

        /** @var \App\Services\ApiKeys\ApiKeyService $service */
        $service = app(\App\Services\ApiKeys\ApiKeyService::class);
        $gen = $service->generate($svc, 'Key 1');
        $secret = $gen['secret'];

        $this->withHeaders(['X-API-Key' => $secret])
            ->postJson('/api/v1/auth/api-keys/exchange')
            ->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_USER_INACTIVE')
            ->assertJsonPath('error.details', null);
    }

    public function test_key_owned_by_human_is_treated_as_invalid(): void
    {
        $this->assertApiKeysFeatureEnabled();
        config(['kaan.api_keys.exchange.verbose_errors' => false]);

        /** @var User $human */
        $human = User::factory()->create([
            'status' => 'active',
            'password' => 'Secret123456',
            'email_verified_at' => now(),
            // type default human
        ]);

        // Bypass ApiKeyService (which now correctly blocks non-service users)
        // and create the key directly via factory to test the exchange validation path
        $prefix = (string) config('kaan.api_keys.prefix', 'kk_live_');
        $plaintext = $prefix . rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $hash = hash('sha256', $plaintext);
        $prefixLen = min((int) config('kaan.api_keys.prefix_length', 12), 64);

        ApiKey::create([
            'user_id' => $human->id,
            'name' => 'Key 1',
            'prefix' => substr($plaintext, 0, $prefixLen),
            'key_hash' => $hash,
            'scopes' => [],
        ]);

        $this->withHeaders(['X-API-Key' => $plaintext])
            ->postJson('/api/v1/auth/api-keys/exchange')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_API_KEY_INVALID')
            ->assertJsonPath('error.details', null);
    }

    public function test_verbose_errors_returns_specific_codes_when_enabled(): void
    {
        $this->assertApiKeysFeatureEnabled();

        \App\Models\PolicySetting::updateOrCreate(
            ['key' => 'security.api_keys.exchange.verbose_errors'],
            ['value_json' => true]
        );
        \Illuminate\Support\Facades\Cache::flush();
        config(['kaan.api_keys.exchange.verbose_errors' => true]);

        $svc = $this->makeServiceAccount();

        /** @var ApiKeyService $service */
        $service = app(ApiKeyService::class);

        // Test revoked key with verbose
        $genRevoked = $service->generate($svc, 'Key Revoked');
        $genRevoked['api_key']->update(['revoked_at' => now()]);

        $this->withHeaders(['X-API-Key' => $genRevoked['secret']])
            ->postJson('/api/v1/auth/api-keys/exchange')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'AUTH_API_KEY_REVOKED');

        // Test expired key with verbose
        $genExpired = $service->generate($svc, 'Key Expired', [], now()->subMinute());

        $this->withHeaders(['X-API-Key' => $genExpired['secret']])
            ->postJson('/api/v1/auth/api-keys/exchange')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'AUTH_API_KEY_EXPIRED');
    }
}
