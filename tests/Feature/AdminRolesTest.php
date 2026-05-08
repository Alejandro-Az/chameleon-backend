<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminRolesTest extends TestCase
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
        
        // El seeder crea el rol admin y los permisos
        /** @var User $admin */
        $admin = User::factory()->create([
            'email' => 'admin_test@test.com',
            'password' => 'Secret123456',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/v1/admin/roles')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonStructure(['ok', 'error' => ['code', 'message', 'details']]);
    }

    public function test_index_requires_permission(): void
    {
        $user = User::factory()->create(['status'=>'active', 'password'=>'Secret123456', 'email_verified_at' => now()]);
        $token = $this->loginToken($user);

        $this->withHeaders($this->authHeader($token))
             ->getJson('/api/v1/admin/roles')
             ->assertStatus(403)
             ->assertJsonPath('ok', false)
             ->assertJsonPath('error.code', 'AUTH_FORBIDDEN')
             ->assertJsonStructure(['ok', 'error' => ['code', 'message', 'details']]);
    }

    public function test_admin_can_list_roles(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        Role::create(['name' => 'editor', 'guard_name' => 'api']);

        $res = $this->withHeaders($this->authHeader($token))
                    ->getJson('/api/v1/admin/roles?q=editor');

        $listedRole = Role::where('name', 'editor')->firstOrFail();

        $res->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.data.0.name', 'editor')
            ->assertJsonPath('data.data.0.id', $listedRole->public_id);
    }

    public function test_admin_can_create_role(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $res = $this->withHeaders($this->authHeader($token))
                    ->postJson('/api/v1/admin/roles', [
                        'name' => 'moderator',
                    ]);

        $res->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.name', 'moderator')
            ->assertJsonPath('data.guard_name', 'api');

        $createdRole = Role::where('name', 'moderator')->firstOrFail();

        $res->assertJsonPath('data.id', $createdRole->public_id);

        $this->assertDatabaseHas('roles', ['name' => 'moderator']);
    }

    public function test_admin_can_update_role(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $role = Role::create(['name' => 'editor', 'guard_name' => 'api']);

        $res = $this->withHeaders($this->authHeader($token))
                    ->putJson("/api/v1/admin/roles/{$role->public_id}", [
                        'name' => 'super-editor',
                    ]);

        $res->assertOk()
            ->assertJsonPath('data.id', $role->public_id)
            ->assertJsonPath('data.name', 'super-editor');
            
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'super-editor']);
    }

    public function test_admin_can_show_role_by_public_id(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $role = Role::create(['name' => 'editor', 'guard_name' => 'api']);

        $res = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/admin/roles/{$role->public_id}");

        $res->assertOk()
            ->assertJsonPath('data.id', $role->public_id)
            ->assertJsonPath('data.name', 'editor');
    }

    public function test_admin_can_patch_role_with_public_id(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $role = Role::create(['name' => 'editor', 'guard_name' => 'api']);

        $res = $this->withHeaders($this->authHeader($token))
            ->patchJson("/api/v1/admin/roles/{$role->public_id}", [
                'name' => 'editor-patched',
            ]);

        $res->assertOk()
            ->assertJsonPath('data.id', $role->public_id)
            ->assertJsonPath('data.name', 'editor-patched');
    }

    public function test_numeric_role_id_is_not_accepted_anymore(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $role = Role::create(['name' => 'editor', 'guard_name' => 'api']);

        $res = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/admin/roles/{$role->id}");

        $res->assertStatus(404)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_admin_cannot_delete_admin_role(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $adminRole = Role::where('name', 'admin')->first();

        $res = $this->withHeaders($this->authHeader($token))
                    ->deleteJson("/api/v1/admin/roles/{$adminRole->public_id}");

        $res->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN')
            ->assertJsonStructure(['ok', 'error' => ['code', 'message', 'details']]);
    }

    public function test_admin_can_delete_other_role(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $role = Role::create(['name' => 'editor', 'guard_name' => 'api']);

        $res = $this->withHeaders($this->authHeader($token))
                    ->deleteJson("/api/v1/admin/roles/{$role->public_id}");

        $res->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_admin_can_sync_permissions(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $role = Role::create(['name' => 'editor', 'guard_name' => 'api']);
        $perm = Permission::create(['name' => 'edit.posts', 'guard_name' => 'api']);

        $res = $this->withHeaders($this->authHeader($token))
                    ->putJson("/api/v1/admin/roles/{$role->public_id}/permissions", [
                        'permissions' => ['edit.posts'],
                    ]);

        $res->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['ok', 'data' => ['id', 'name', 'permissions']]);

        $this->assertTrue($role->refresh()->hasPermissionTo('edit.posts'));
    }

}
