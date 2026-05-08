<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Limpiar caché de permisos
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function tokenWithSession(User $user): string
    {
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
        $payload = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->getPayload();
        $jti = (string) $payload->get('jti');

        \App\Models\AuthSession::create([
            'user_id' => $user->id,
            'token_id' => $jti,
            'ip' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'last_seen_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        return $token;
    }

    public function test_login_creates_audit_log(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@demo.kaanforge.test',
            'password' => 'Secret123456',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'login' => 'admin@demo.kaanforge.test',
            'password' => 'Secret123456',
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.login',
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
    }

    public function test_logout_creates_audit_log(): void
    {
        $user = User::factory()->create(['status' => 'active', 'password' => 'Secret123456', 'email_verified_at' => now()]);
        $token = $this->tokenWithSession($user);

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.logout',
        ]);
    }

    public function test_admin_create_user_creates_audit_log(): void
    {
        // Setup admin
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $role = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $perm = Permission::create(['name' => 'admin.users.manage', 'guard_name' => 'api']);
        $role->givePermissionTo($perm);
        $admin->assignRole($role);

        $token = $this->tokenWithSession($admin);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/admin/users', [
                'name' => 'New User',
                'email' => 'new@test.com',
                'username' => 'newuser',
                'password' => 'Secret123456',
                'status' => 'active',
            ]);
        
        $response->assertCreated();

        $newUser = User::where('email', 'new@test.com')->first();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id, // Who did it
            'action' => 'user.created',
            'model_type' => User::class,
            'model_id' => $newUser->id, // Who was created
        ]);
    }

    public function test_admin_update_role_creates_audit_log(): void
    {
        // Setup admin
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $role = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $perm = Permission::create(['name' => 'admin.roles.manage', 'guard_name' => 'api']);
        $role->givePermissionTo($perm);
        $admin->assignRole($role);

        // Edit target role
        $targetRole = Role::create(['name' => 'editor', 'guard_name' => 'api']);

        $token = $this->tokenWithSession($admin);

        $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/v1/admin/roles/{$targetRole->public_id}", [
                'name' => 'super-editor',
            ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'role.updated',
            'model_type' => Role::class,
            'model_id' => $targetRole->id,
        ]);
    }
}
