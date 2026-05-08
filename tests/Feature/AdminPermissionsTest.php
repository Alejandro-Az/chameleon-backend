<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function loginToken(User $user): string
    {
        $res = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'Secret123456',
        ]);
        return $res->json('data.access_token');
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
            'email' => 'admin_perms@test.com',
            'password' => 'Secret123456',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/v1/admin/permissions')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonStructure(['ok', 'error' => ['code', 'message', 'details']]);
    }

    public function test_index_requires_permission(): void
    {
        $user = User::factory()->create(['status'=>'active', 'password'=>'Secret123456', 'email_verified_at' => now()]);
        $token = $this->loginToken($user);

        $this->withHeaders($this->authHeader($token))
             ->getJson('/api/v1/admin/permissions')
             ->assertStatus(403)
             ->assertJsonPath('ok', false)
             ->assertJsonPath('error.code', 'AUTH_FORBIDDEN')
             ->assertJsonStructure(['ok', 'error' => ['code', 'message', 'details']]);
    }

    public function test_admin_can_list_permissions(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        // Seeder creates 3 params already
        $res = $this->withHeaders($this->authHeader($token))
                    ->getJson('/api/v1/admin/permissions');

        $res->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonCount(15, 'data.data'); // count sincronizado con seeder actual

        $this->assertArrayNotHasKey('id', $res->json('data.data.0'));
    }

    public function test_admin_can_filter_permissions(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $res = $this->withHeaders($this->authHeader($token))
                    ->getJson('/api/v1/admin/permissions?q=users');

        $res->assertOk()
            ->assertJsonPath('data.data.0.name', 'admin.users.manage');
            
        // Assuming no other perm contains "users"
        $this->assertCount(1, $res->json('data.data'));
    }
}
