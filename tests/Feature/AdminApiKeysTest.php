<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\AuthSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminApiKeysTest extends TestCase
{
    use RefreshDatabase;

    private function assertApiKeysFeatureEnabled(): void
    {
        $this->assertTrue(
            (bool) config('kaan.features.api_keys', false),
            'KAAN_FEATURE_API_KEYS debe estar en true para correr PR3 tests (rutas gated). Agrega <env name="KAAN_FEATURE_API_KEYS" value="true"/> a phpunit.xml.'
        );
    }

    private function loginToken(User $user, string $password = 'Secret123456'): string
    {
        $res = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => $password,
        ]);

        $res->assertOk()->assertJsonPath('ok', true);

        $token = $res->json('data.access_token');
        $this->assertIsString($token);

        return $token;
    }

    private function authHeader(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    private function makeAdmin(): User
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        /** @var User $admin */
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@demo.kaanforge.test',
            'username' => 'admin',
            'status' => 'active',
            'password' => 'Secret123456',
            'email_verified_at' => now(),
        ]);

        $admin->assignRole('admin');

        $this->assertTrue(
            $admin->can('admin.api_keys.manage'),
            'Admin debe tener permiso admin.api_keys.manage (revisa RolesAndPermissionsSeeder: guard api + syncPermissions).'
        );

        return $admin;
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

        // type no es fillable => set server-side
        $svc->type = 'service';
        $svc->save();

        return $svc;
    }

    public function test_routes_require_authentication(): void
    {
        $this->assertApiKeysFeatureEnabled();

        $svc = $this->makeServiceAccount();

        $this->getJson("/api/v1/admin/service-accounts/{$svc->public_id}/api-keys")->assertStatus(401);
        $this->postJson("/api/v1/admin/service-accounts/{$svc->public_id}/api-keys", ['name' => 'x'])->assertStatus(401);
    }

    public function test_routes_require_permission(): void
    {
        $this->assertApiKeysFeatureEnabled();

        $svc = $this->makeServiceAccount();

        $user = User::factory()->create([
            'status' => 'active',
            'password' => 'Secret123456',
            'email_verified_at' => now(),
        ]);

        $token = $this->loginToken($user);

        $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/admin/service-accounts/{$svc->public_id}/api-keys")
            ->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_admin_can_create_list_rotate_and_revoke_api_key(): void
    {
        $this->assertApiKeysFeatureEnabled();

        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $svc = $this->makeServiceAccount();

        // CREATE
        $create = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/admin/service-accounts/{$svc->public_id}/api-keys", [
                'name' => 'ERP Sync Key',
                'scopes' => ['read:users', 'write:orders'],
                'expires_at' => now()->addDays(30)->toIso8601String(),
            ]);

        $create->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonStructure([
                'ok',
                'data' => [
                    'api_key' => ['id', 'name', 'prefix', 'scopes', 'created_at'],
                    'secret',
                ],
            ]);

        $secret = $create->json('data.secret');
        $this->assertIsString($secret);
        $this->assertStringStartsWith((string) config('kaan.api_keys.prefix', 'kk_live_'), $secret);

        $apiKeyId = $create->json('data.api_key.id');
        $this->assertIsString($apiKeyId);

        // DB: existe key
        $this->assertDatabaseHas('api_keys', [
            'public_id' => $apiKeyId,
            'user_id' => $svc->id,
        ]);

        /** @var ApiKey $apiKey */
        $apiKey = ApiKey::query()->where('public_id', $apiKeyId)->firstOrFail();

        // LIST
        $list = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/admin/service-accounts/{$svc->public_id}/api-keys?per_page=15");

        $list->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['ok', 'data' => ['data', 'links', 'meta']]);

        $items = $list->json('data.data');
        $this->assertIsArray($items);
        $this->assertNotEmpty($items);

        // Ensure no secret/key_hash is leaked
        foreach ($items as $item) {
            $this->assertArrayNotHasKey('secret', $item);
            $this->assertArrayNotHasKey('key_hash', $item);
        }

        // ROTATE
        $rotate = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/admin/api-keys/{$apiKey->public_id}/rotate");

        $rotate->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure([
                'ok',
                'data' => [
                    'api_key' => ['id', 'name', 'prefix', 'scopes', 'created_at'],
                    'secret',
                ],
            ]);

        $newApiKeyId = $rotate->json('data.api_key.id');
        $this->assertIsString($newApiKeyId);
        $this->assertNotEquals($apiKeyId, $newApiKeyId);

        $apiKey->refresh();
        $this->assertNotNull($apiKey->revoked_at, 'La key vieja debe quedar revocada tras rotate().');

        /** @var ApiKey $newKey */
        $newKey = ApiKey::query()->where('public_id', $newApiKeyId)->firstOrFail();
        $this->assertNull($newKey->revoked_at);

        // REVOKE (DELETE / admin/api-keys/{apiKey})
        $revoke = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/admin/api-keys/{$newKey->public_id}");

        $revoke->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.message', 'API key revocada correctamente.');

        $newKey->refresh();
        $this->assertNotNull($newKey->revoked_at, 'La key debe quedar revocada.');

        // Idempotencia: revocar de nuevo no debe fallar
        $revoke2 = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/admin/api-keys/{$newKey->public_id}");

        $revoke2->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_revoking_api_key_revokes_owner_sessions(): void
    {
        $this->assertApiKeysFeatureEnabled();

        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $svc = $this->makeServiceAccount();

        // Crear key vía endpoint admin (así validamos wiring completo)
        $create = $this->withHeaders($this->authHeader($token))
            ->postJson("/api/v1/admin/service-accounts/{$svc->public_id}/api-keys", [
                'name' => 'ERP Key',
            ]);

        $create->assertStatus(201)->assertJsonPath('ok', true);

        $apiKeyId = $create->json('data.api_key.id');

        // Obtener el ID interno de la API key para vincular la sesión
        $apiKeyModel = ApiKey::query()->where('public_id', $apiKeyId)->firstOrFail();

        // Crear sesión activa vinculada a esta API key
        AuthSession::create([
            'user_id' => $svc->id,
            'api_key_id' => $apiKeyModel->id,
            'token_id' => Str::random(64),
            'ip' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'last_seen_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        // Crear sesión de OTRA key (no debe revocarse)
        $otherSession = AuthSession::create([
            'user_id' => $svc->id,
            'api_key_id' => null, // sesión sin API key (login humano)
            'token_id' => Str::random(64),
            'ip' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'last_seen_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        // Revocar key
        $revoke = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/admin/api-keys/{$apiKeyId}");

        $revoke->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.message', 'API key revocada correctamente.');

        // Assert: sesiones de ESTA key están revocadas
        $this->assertTrue(
            AuthSession::query()
                ->where('api_key_id', $apiKeyModel->id)
                ->whereNull('revoked_at')
                ->count() === 0,
            'Revocar una API key debe cortar sesiones generadas por esa key (revocación quirúrgica).'
        );

        // Assert: sesión de OTRA key sigue activa (no hay blast radius)
        $otherSession->refresh();
        $this->assertNull(
            $otherSession->revoked_at,
            'Revocar una API key NO debe cortar sesiones de otras keys del mismo owner.'
        );
    }
}
