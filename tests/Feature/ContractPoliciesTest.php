<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Tests\TestCase;

/**
 * Contract Tests: Admin Policies (ON state)
 *
 * Validates auth/permission gating and response envelope
 * when the policy_center feature flag is active.
 *
 * NOTE: policy_center default is false, so we must reboot
 * with the flag enabled to register the routes.
 * Each test runs in a separate process to allow route re-registration.
 */
final class ContractPoliciesTest extends TestCase
{
    /**
     * Reboot app with policy_center enabled and run migrations + seed.
     */
    private function bootWithPolicies(): void
    {
        putenv('KAAN_FEATURE_POLICY_CENTER=true');
        $_ENV['KAAN_FEATURE_POLICY_CENTER'] = 'true';
        $_SERVER['KAAN_FEATURE_POLICY_CENTER'] = 'true';

        \Illuminate\Support\Env::enablePutenv();
        $this->refreshApplication();

        // In separate process, RefreshDatabase won't have run migrations yet.
        // We need to migrate fresh and seed manually.
        $this->artisan('migrate:fresh', ['--seed' => false]);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    // ---------------------------------------------------------------
    // policies index — 401 sin token
    // ---------------------------------------------------------------

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_policies_index_returns_401_without_token(): void
    {
        $this->bootWithPolicies();

        $res = $this->getJson('/api/v1/admin/policies');

        $res->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_UNAUTHENTICATED')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }

    // ---------------------------------------------------------------
    // policies index — 403 sin permiso
    // ---------------------------------------------------------------

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_policies_index_returns_403_without_permission(): void
    {
        $this->bootWithPolicies();

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $user->assignRole('user');

        $res = $this->actingAs($user, 'api')
            ->getJson('/api/v1/admin/policies');

        $res->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }

    // ---------------------------------------------------------------
    // policies index — 200 con permiso
    // ---------------------------------------------------------------

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_policies_index_returns_200_with_permission(): void
    {
        $this->bootWithPolicies();

        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $res = $this->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/policies');

        $res->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonStructure([
                'ok',
                'data',
            ]);
    }

    // ---------------------------------------------------------------
    // policies show — 401 sin token
    // ---------------------------------------------------------------

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_policies_show_returns_401_without_token(): void
    {
        $this->bootWithPolicies();

        $res = $this->getJson('/api/v1/admin/policies/some.key');

        $res->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_UNAUTHENTICATED');
    }

    // ---------------------------------------------------------------
    // policies update — 401 sin token
    // ---------------------------------------------------------------

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_policies_update_returns_401_without_token(): void
    {
        $this->bootWithPolicies();

        $res = $this->patchJson('/api/v1/admin/policies', []);

        $res->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_UNAUTHENTICATED');
    }

    // ---------------------------------------------------------------
    // policies update — 403 sin permiso manage
    // ---------------------------------------------------------------

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_policies_update_returns_403_without_manage_permission(): void
    {
        $this->bootWithPolicies();

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $user->assignRole('user');

        $res = $this->actingAs($user, 'api')
            ->patchJson('/api/v1/admin/policies', []);

        $res->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }
}
