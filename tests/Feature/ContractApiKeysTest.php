<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Tests\TestCase;

/**
 * Contract Tests: Admin API Keys + Service Accounts (ON state)
 *
 * Validates auth/permission gating and response envelope
 * when the api_keys feature flag is active.
 *
 * NOTE: api_keys default is false, so we must reboot
 * with the flag enabled to register the routes.
 * Each test runs in a separate process to allow route re-registration.
 */
final class ContractApiKeysTest extends TestCase
{
    /**
     * Reboot app with api_keys enabled and run migrations + seed.
     */
    private function bootWithApiKeys(): void
    {
        putenv('KAAN_FEATURE_API_KEYS=true');
        $_ENV['KAAN_FEATURE_API_KEYS'] = 'true';
        $_SERVER['KAAN_FEATURE_API_KEYS'] = 'true';

        \Illuminate\Support\Env::enablePutenv();
        $this->refreshApplication();

        $this->artisan('migrate:fresh', ['--seed' => false]);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    // ===================================================================
    // Service Accounts
    // ===================================================================

    // ---------------------------------------------------------------
    // service-accounts index — 401 sin token
    // ---------------------------------------------------------------

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_service_accounts_index_returns_401_without_token(): void
    {
        $this->bootWithApiKeys();

        $res = $this->getJson('/api/v1/admin/service-accounts');

        $res->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_UNAUTHENTICATED')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }

    // ---------------------------------------------------------------
    // service-accounts index — 403 sin permiso
    // ---------------------------------------------------------------

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_service_accounts_index_returns_403_without_permission(): void
    {
        $this->bootWithApiKeys();

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $user->assignRole('user');

        $res = $this->actingAs($user, 'api')
            ->getJson('/api/v1/admin/service-accounts');

        $res->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }

    // ---------------------------------------------------------------
    // service-accounts index — 200 con permiso
    // ---------------------------------------------------------------

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_service_accounts_index_returns_200_with_permission(): void
    {
        $this->bootWithApiKeys();

        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $res = $this->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/service-accounts');

        $res->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonStructure([
                'ok',
                'data' => [
                    'data',
                    'links',
                    'meta',
                ],
            ]);
    }

    // ===================================================================
    // API Keys Management (rotate, delete)
    // ===================================================================

    // ---------------------------------------------------------------
    // rotate / delete — 401 sin token
    // ---------------------------------------------------------------

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_api_keys_management_returns_401_without_token(): void
    {
        $this->bootWithApiKeys();

        $serviceAccount = User::factory()->create(['type' => 'service']);
        $apiKey = \App\Models\ApiKey::factory()->create(['user_id' => $serviceAccount->id]);
        $apiKey->refresh();

        $resRotate = $this->postJson("/api/v1/admin/api-keys/{$apiKey->public_id}/rotate");
        $resRotate->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_UNAUTHENTICATED')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);

        $resDelete = $this->deleteJson("/api/v1/admin/api-keys/{$apiKey->public_id}");
        $resDelete->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_UNAUTHENTICATED')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }

    // ---------------------------------------------------------------
    // rotate / delete — 403 sin permiso
    // ---------------------------------------------------------------

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_api_keys_management_returns_403_without_permission(): void
    {
        $this->bootWithApiKeys();

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $user->assignRole('user');

        $serviceAccount = User::factory()->create(['type' => 'service']);
        $apiKey = \App\Models\ApiKey::factory()->create(['user_id' => $serviceAccount->id]);
        $apiKey->refresh();

        $resRotate = $this->actingAs($user, 'api')->postJson("/api/v1/admin/api-keys/{$apiKey->public_id}/rotate");
        $resRotate->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);

        $resDelete = $this->actingAs($user, 'api')->deleteJson("/api/v1/admin/api-keys/{$apiKey->public_id}");
        $resDelete->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }

    // ===================================================================
    // API Keys Exchange
    // ===================================================================

    // ---------------------------------------------------------------
    // exchange — 401 sin API key header
    // ---------------------------------------------------------------

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_exchange_returns_401_without_api_key(): void
    {
        $this->bootWithApiKeys();

        $res = $this->postJson('/api/v1/auth/api-keys/exchange');

        $res->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_API_KEY_MISSING')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }
}
