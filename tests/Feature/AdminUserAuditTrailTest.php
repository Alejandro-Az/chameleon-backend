<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('kaan:install');
    }

    public function test_admin_updating_user_to_suspended_creates_status_audit_and_revokes_sessions(): void
    {
        $admin = clone current(User::role('admin')->get()->all());
        $admin->email_verified_at = now();
        $admin->save();
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);

        // Authenticate the target user to create an active session
        $response = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'Secret123456',
        ]);
        
        $response->assertOk();

        // The target user now has an active session
        $this->assertDatabaseHas('auth_sessions', [
            'user_id' => $user->id,
            'revoked_at' => null,
        ]);

        // Admin suspends the user
        $response = $this->actingAs($admin, 'api')->putJson("/api/v1/admin/users/{$user->public_id}", [
            'status' => 'suspended'
        ]);

        $response->assertOk();

        // Verify Status Audit Log exists
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.status_changed',
            'user_id' => $admin->id,
        ]);

        $statusLog = AuditLog::where('action', 'user.status_changed')->first();
        $this->assertEquals('active', $statusLog->details['from']);
        $this->assertEquals('suspended', $statusLog->details['to']);

        // Verify Sessions were revoked
        $this->assertDatabaseMissing('auth_sessions', [
            'user_id' => $user->id,
            'revoked_at' => null,
        ]);

        // sessions_revoked is now consolidated in user.status_changed audit
        $this->assertArrayHasKey('sessions_revoked', $statusLog->details);
        $this->assertEquals(1, $statusLog->details['sessions_revoked']);
    }

    public function test_admin_creating_user_with_role_audits_role_assignment(): void
    {
        $admin = clone current(User::role('admin')->get()->all());
        $admin->email_verified_at = now();
        $admin->save();

        $response = $this->actingAs($admin, 'api')->postJson("/api/v1/admin/users", [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'Secret123456',
            'role' => 'admin',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.role_changed',
            'user_id' => $admin->id,
        ]);

        $roleLog = AuditLog::where('action', 'user.role_changed')->first();
        $this->assertEmpty($roleLog->details['from']);
        $this->assertEquals(['admin'], $roleLog->details['to']);
    }

    public function test_admin_updating_user_role_audits_from_and_to_roles(): void
    {
        $admin = clone current(User::role('admin')->get()->all());
        $admin->email_verified_at = now();
        $admin->save();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('user');

        $response = $this->actingAs($admin, 'api')->putJson("/api/v1/admin/users/{$user->public_id}", [
            'role' => 'admin'
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.role_changed',
            'user_id' => $admin->id,
        ]);

        $roleLog = AuditLog::where('action', 'user.role_changed')->first();
        $this->assertEquals(['user'], $roleLog->details['from']);
        $this->assertEquals(['admin'], $roleLog->details['to']);
    }

    public function test_combined_update_creates_distinct_audit_logs_and_excludes_status_and_role_from_generic_update_fields(): void
    {
        $admin = clone current(User::role('admin')->get()->all());
        $admin->email_verified_at = now();
        $admin->save();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('user');

        $response = $this->actingAs($admin, 'api')->putJson("/api/v1/admin/users/{$user->public_id}", [
            'name' => 'Updated Name',
            'status' => 'suspended',
            'role' => 'admin'
        ]);

        $response->assertOk();

        // 1. Check Status Change Log
        $statusLog = AuditLog::where('action', 'user.status_changed')->first();
        $this->assertNotNull($statusLog);
        $this->assertEquals('active', $statusLog->details['from']);
        $this->assertEquals('suspended', $statusLog->details['to']);

        // 2. Check Role Change Log
        $roleLog = AuditLog::where('action', 'user.role_changed')->first();
        $this->assertNotNull($roleLog);
        $this->assertEquals(['user'], $roleLog->details['from']);
        $this->assertEquals(['admin'], $roleLog->details['to']);

        // 3. Check Generic Update Log (Should exclude status and role, but include name)
        $updateLog = AuditLog::where('action', 'user.updated')->first();
        $this->assertNotNull($updateLog);
        
        $fields = $updateLog->details['fields'];
        $this->assertContains('name', $fields);
        $this->assertNotContains('status', $fields);
        $this->assertNotContains('role', $fields);
    }
}
