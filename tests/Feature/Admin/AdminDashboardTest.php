<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\AuthSession;
use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function loginToken(User $user, string $password = 'Secret123456'): string
    {
        $res = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => $password,
        ]);

        $res->assertOk()->assertJsonPath('ok', true);
        return $res->json('data.access_token');
    }

    private function authHeader(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_dashboard_returns_empty_metrics_for_user_without_permissions()
    {
        $user = User::factory()->create([
            'status' => 'active', 
            'password' => 'Secret123456', 
            'email_verified_at' => now()
        ]);
        $token = $this->loginToken($user);
        
        $response = $this->withHeaders($this->authHeader($token))->getJson('/api/v1/admin/dashboard/summary');
        
        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['ok', 'data' => ['metrics', 'recent_activity']]);
        $this->assertEquals([], $response->json('data.metrics'));
        $this->assertEquals([], $response->json('data.recent_activity'));
    }

    public function test_dashboard_returns_partial_metrics_based_on_permissions()
    {
        $manager = User::factory()->create([
            'status' => 'active', 
            'password' => 'Secret123456', 
            'email_verified_at' => now()
        ]);
        
        // Asignamos una política personalizada que solo permita ver la data de Usuarios
        // Creamos un rol temporal al vuelo para simular un usuario de RH sin acceso a seguridad
        $role = \App\Models\Role::create(['name' => 'hr_manager', 'guard_name' => 'api']);
        $role->givePermissionTo('admin.users.manage');
        $manager->assignRole('hr_manager');
        
        $token = $this->loginToken($manager);
        
        User::factory()->count(2)->create(['type' => 'human', 'status' => 'active']);
        
        $response = $this->withHeaders($this->authHeader($token))->getJson('/api/v1/admin/dashboard/summary');
        
        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['ok', 'data' => ['metrics', 'recent_activity']]);

        // Verifica que VE métricas de usuarios
        $this->assertArrayHasKey('users', $response->json('data.metrics'));
        
        // Verifica que NO VE métricas de seguridad o roles (porque el factory del rol no lo tiene)
        $this->assertArrayNotHasKey('active_sessions', $response->json('data.metrics'));
        $this->assertArrayNotHasKey('roles', $response->json('data.metrics'));
        $this->assertArrayNotHasKey('audit_logs', $response->json('data.recent_activity'));
        $this->assertArrayNotHasKey('login_attempts', $response->json('data.recent_activity'));
    }

    public function test_dashboard_returns_metrics_for_admin_user()
    {
        $admin = User::factory()->create([
            'status' => 'active', 
            'password' => 'Secret123456', 
            'email_verified_at' => now()
        ]);
        $admin->assignRole('admin');
        
        User::factory()->count(3)->create(['type' => 'human', 'status' => 'active']);
        
        AuthSession::create([
            'user_id' => $admin->id,
            'token_id' => 'test-token',
            'ip' => '127.0.0.1',
            'user_agent' => 'Test',
            'expires_at' => now()->addDays(7),
        ]);
        
        LoginAttempt::create([
            'email' => 'test@example.com',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'status' => 'failed',
            'created_at' => now(),
        ]);
        
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'test.event',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
        ]);
        
        $adminToken = $this->loginToken($admin);
        $responseAdmin = $this->withHeaders($this->authHeader($adminToken))->getJson('/api/v1/admin/dashboard/summary');
        
        $responseAdmin->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['ok', 'data' => ['metrics', 'recent_activity']]);

        // Admin should see users and active_sessions
        $this->assertArrayHasKey('users', $responseAdmin->json('data.metrics'));
        $this->assertArrayHasKey('active_sessions', $responseAdmin->json('data.metrics'));
        
        // Admin should see login_attempts and audit_logs
        $this->assertArrayHasKey('login_attempts', $responseAdmin->json('data.recent_activity'));
        $this->assertArrayHasKey('audit_logs', $responseAdmin->json('data.recent_activity'));
    }
}
