<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

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
        // Seeder de roles/permisos (debe crear: role admin + permission admin.users.manage con guard api)
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

        // Sanity check: el permiso debe existir y estar ligado al rol admin
        $this->assertTrue(
            $admin->can('admin.users.manage'),
            'Admin debe tener permiso admin.users.manage (revisa RolesAndPermissionsSeeder guard_name=api y role_has_permissions).'
        );

        return $admin;
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/v1/admin/users')
            ->assertStatus(401);
    }

    public function test_index_requires_permission(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'password' => 'Secret123456',
            'email_verified_at' => now(),
        ]);

        $token = $this->loginToken($user);

        $this->withHeaders($this->authHeader($token))
            ->getJson('/api/v1/admin/users')
            ->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_admin_can_list_users(): void
    {
        $admin = $this->makeAdmin();

        // Data extra
        User::factory()->count(3)->create(['status' => 'active']);
        User::factory()->create(['status' => 'suspended']);

        $token = $this->loginToken($admin);

        $res = $this->withHeaders($this->authHeader($token))
            ->getJson('/api/v1/admin/users?per_page=15');

        $res->assertOk()
            ->assertJsonPath('ok', true)
            // paginated: data.data
            ->assertJsonStructure([
                'ok',
                'data' => ['data', 'links', 'meta'],
            ]);
    }

    public function test_admin_can_filter_by_status_and_q(): void
    {
        $admin = $this->makeAdmin();

        User::factory()->create(['name' => 'Alice', 'email' => 'alice@test.com', 'status' => 'active']);
        User::factory()->create(['name' => 'Bob', 'email' => 'bob@test.com', 'status' => 'suspended']);

        $token = $this->loginToken($admin);

        // status=suspended should return Bob only (or at least include him)
        $res = $this->withHeaders($this->authHeader($token))
            ->getJson('/api/v1/admin/users?status=suspended&q=bob');

        $res->assertOk()
            ->assertJsonPath('ok', true);

        $items = $res->json('data.data');
        $this->assertIsArray($items);

        // Verify each returned item matches intent
        foreach ($items as $u) {
            $this->assertEquals('suspended', $u['status'] ?? null);
        }
    }

    public function test_admin_can_create_show_update_and_soft_delete_user(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        // CREATE
        $create = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/admin/users', [
                'name' => 'Nuevo Usuario',
                'email' => 'nuevo@test.com',
                'username' => 'nuevo',
                'password' => 'Secret123456',
                'status' => 'active',
                'timezone' => 'America/Mexico_City',
                'locale' => 'es',
                'role' => 'admin', // opcional si lo soportas en StoreUserRequest
            ]);

        $create->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['ok', 'data' => ['id', 'name', 'email']]);

        $publicId = $create->json('data.id');

        $this->assertIsString($publicId);

        // SHOW (route key: public_id)
        $show = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/admin/users/{$publicId}");

        $show->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.email', 'nuevo@test.com');

        // UPDATE
        $update = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/v1/admin/users/{$publicId}", [
                'name' => 'Nuevo Usuario Updated',
                'status' => 'suspended',
            ]);

        $update->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.name', 'Nuevo Usuario Updated');

        // DELETE (soft)
        $delete = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/admin/users/{$publicId}");

        $delete->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSoftDeleted('users', ['public_id' => $publicId]);
    }

    public function test_admin_can_create_user_with_role_public_id(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $role = \App\Models\Role::query()->where('name', 'admin')->firstOrFail();

        $create = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/admin/users', [
                'name' => 'Usuario Con Rol',
                'email' => 'conrol@test.com',
                'password' => 'Secret123456',
                'role_id' => $role->public_id,
            ]);

        $create->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.email', 'conrol@test.com');

        $created = User::query()->where('email', 'conrol@test.com')->firstOrFail();
        $this->assertTrue($created->hasRole('admin'));
    }

    public function test_create_user_with_invalid_role_public_id_returns_validation_error(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $create = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/admin/users', [
                'name' => 'Usuario Sin Rol',
                'email' => 'sinrol@test.com',
                'password' => 'Secret123456',
                'role_id' => '01INVALIDULID0000000000000',
            ]);

        $create->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_admin_can_update_user_role_using_role_public_id(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('user');

        $adminRole = \App\Models\Role::query()->where('name', 'admin')->firstOrFail();

        $res = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/v1/admin/users/{$user->public_id}", [
                'role_id' => $adminRole->public_id,
            ]);

        $res->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertTrue($user->fresh()->hasRole('admin'));
    }

    public function test_update_with_only_unknown_fields_returns_validation_error(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $res = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/v1/admin/users/{$user->public_id}", [
                'foo' => 'bar',
            ]);

        $res->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_show_returns_404_for_unknown_public_id(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $this->withHeaders($this->authHeader($token))
            ->getJson('/api/v1/admin/users/01NOTAREALULID0000000000000')
            ->assertStatus(404);
    }

    // ---------------------------------------------------------------
    // Human/Service Boundary Tests
    // ---------------------------------------------------------------

    public function test_index_excludes_service_accounts(): void
    {
        $admin = $this->makeAdmin();

        // Create human users
        User::factory()->count(2)->create(['status' => 'active']);

        // Create a service account (should NOT appear in /admin/users)
        $svc = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $svc->forceFill(['type' => 'service'])->save();

        $token = $this->loginToken($admin);

        $res = $this->withHeaders($this->authHeader($token))
            ->getJson('/api/v1/admin/users?per_page=100');

        $res->assertOk()->assertJsonPath('ok', true);

        $items = $res->json('data.data');
        $this->assertIsArray($items);

        // No item should have type = service
        foreach ($items as $item) {
            // Items don't expose 'type', so verify the service account's public_id is not in the list
            $this->assertNotEquals($svc->public_id, $item['id'] ?? null);
        }
    }

    public function test_show_rejects_service_account(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $svc = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $svc->forceFill(['type' => 'service'])->save();

        $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/admin/users/{$svc->public_id}")
            ->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND');
    }

    public function test_update_rejects_service_account(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $svc = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $svc->forceFill(['type' => 'service'])->save();

        $this->withHeaders($this->authHeader($token))
            ->putJson("/api/v1/admin/users/{$svc->public_id}", ['name' => 'Hacked'])
            ->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND');
    }

    public function test_destroy_rejects_service_account(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $svc = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $svc->forceFill(['type' => 'service'])->save();

        $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/admin/users/{$svc->public_id}")
            ->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND');
    }
}
