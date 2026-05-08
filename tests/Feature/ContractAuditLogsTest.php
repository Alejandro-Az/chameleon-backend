<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contract Tests: Admin Audit Logs (ON state)
 *
 * Validates auth/permission gating and response envelope
 * when the audit feature flag is active (default: true).
 */
final class ContractAuditLogsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    // ---------------------------------------------------------------
    // 401 — sin token
    // ---------------------------------------------------------------

    public function test_audit_logs_returns_401_without_token(): void
    {
        $res = $this->getJson('/api/v1/admin/audit-logs');

        $res->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_UNAUTHENTICATED')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }

    // ---------------------------------------------------------------
    // 403 — sin permiso admin.audit.view
    // ---------------------------------------------------------------

    public function test_audit_logs_returns_403_without_permission(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $user->assignRole('user');

        $res = $this->actingAs($user, 'api')
            ->getJson('/api/v1/admin/audit-logs');

        $res->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }

    // ---------------------------------------------------------------
    // 200 — con permiso → envelope paginado
    // ---------------------------------------------------------------

    public function test_audit_logs_returns_200_with_valid_auth_and_permission(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $res = $this->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/audit-logs');

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
}
