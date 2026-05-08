<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\ApiKeys\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PolicyConsumersTest extends TestCase
{
    use RefreshDatabase;

    private function seedAdmin(): User
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

        $this->assertTrue($admin->can('admin.policies.manage'));
        $this->assertTrue($admin->can('admin.users.manage'));
        $this->assertTrue($admin->can('admin.service_accounts.manage'));
        $this->assertTrue($admin->can('admin.api_keys.manage'));

        return $admin;
    }

    private function loginToken(User $user): string
    {
        $res = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'Secret123456',
        ]);

        $res->assertOk()->assertJsonPath('ok', true);

        return (string) $res->json('data.access_token');
    }

    private function authHeader(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_policy_center_governs_pagination_max_per_page(): void
    {
        $admin = $this->seedAdmin();
        $token = $this->loginToken($admin);

        $this->withHeaders($this->authHeader($token))
            ->patchJson('/api/v1/admin/policies', [
                'policies' => [
                    'api.pagination.max_per_page' => 20,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        // Force the app container to forget the PolicyService singleton so it gets 
        // reconstructed and fetches the latest DB row instead of its in-memory memoized array.
        $this->app->forgetInstance(\App\Services\Policy\PolicyService::class);
        \Illuminate\Support\Facades\Cache::flush();

        $this->withHeaders($this->authHeader($token))
            ->getJson('/api/v1/admin/users?per_page=999')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.meta.per_page', 20);

        $this->withHeaders($this->authHeader($token))
            ->getJson('/api/v1/admin/service-accounts?per_page=999')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.meta.per_page', 20);
    }

    public function test_policy_center_governs_verbose_errors_for_api_key_exchange(): void
    {
        $admin = $this->seedAdmin();
        $token = $this->loginToken($admin);

        /** @var User $svc */
        $svc = User::factory()->create([
            'name' => 'Svc - Bot',
            'email' => 'svc-bot@service.local',
            'status' => 'active',
            'password' => 'Secret123456',
            'email_verified_at' => now(),
        ]);
        $svc->type = 'service';
        $svc->save();

        /** @var ApiKeyService $keys */
        $keys = app(ApiKeyService::class);

        $result = $keys->generate(
            serviceAccount: $svc,
            name: 'Test Key',
            scopes: [],
            expiresAt: null,
            createdBy: $admin,
        );

        $apiKey = $result['api_key'];
        $secret = $result['secret'];

        $keys->revoke($apiKey);

        $this->withHeaders($this->authHeader($token))
            ->patchJson('/api/v1/admin/policies', [
                'policies' => [
                    'security.api_keys.exchange.verbose_errors' => true,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->withHeaders(['X-API-Key' => $secret])
            ->postJson('/api/v1/auth/api-keys/exchange')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_API_KEY_REVOKED');

        $this->withHeaders($this->authHeader($token))
            ->patchJson('/api/v1/admin/policies', [
                'policies' => [
                    'security.api_keys.exchange.verbose_errors' => false,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->withHeaders(['X-API-Key' => $secret])
            ->postJson('/api/v1/auth/api-keys/exchange')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_API_KEY_INVALID');
    }
}
