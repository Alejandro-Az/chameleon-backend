<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kernel Contract: Admin Payload Shapes
 *
 * Seals the paginated response contract for the Admin Control Plane.
 * This is distinct from self-service endpoints and ensures any
 * admin dashboard frontend can depend on a stable shape.
 */
final class KernelContractAdminPayloadTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        /** @var User $admin */
        $admin = User::factory()->create([
            'name'              => 'Contract Admin',
            'email'             => 'contract-admin@kaanforge.test',
            'username'          => 'contract_admin',
            'status'            => 'active',
            'password'          => 'Secret123456',
            'email_verified_at' => now(),
        ]);

        $admin->assignRole('admin');

        return $admin;
    }

    // ---------------------------------------------------------------
    // Admin paginated list shape — users endpoint
    // ---------------------------------------------------------------

    public function test_admin_users_paginated_shape_is_stable(): void
    {
        $admin = $this->makeAdmin();

        // Create some extra users to populate the list
        User::factory()->count(3)->create(['status' => 'active']);

        $res = $this->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/users?per_page=15');

        $res->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonStructure([
                'ok',
                'data' => [
                    'data' => [
                        '*' => ['id', 'name', 'email', 'status'],
                    ],
                    'links',
                    'meta',
                ],
            ]);

        // CONTRACT: data.*.id must be string (ULID public_id), never integer
        $items = $res->json('data.data');
        $this->assertNotEmpty($items, 'List should contain at least one user');
        foreach ($items as $item) {
            $this->assertIsString($item['id'], "User id must be string (ULID), got: " . gettype($item['id']));
        }
    }

    // ---------------------------------------------------------------
    // Admin user show — id is string ULID
    // ---------------------------------------------------------------

    public function test_admin_user_show_id_is_string(): void
    {
        $admin = $this->makeAdmin();

        $target = User::factory()->create(['status' => 'active']);

        $res = $this->actingAs($admin, 'api')
            ->getJson("/api/v1/admin/users/{$target->public_id}");

        $res->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonStructure([
                'ok',
                'data' => ['id', 'name', 'email', 'status'],
            ]);

        $this->assertIsString($res->json('data.id'));
        $this->assertEquals($target->public_id, $res->json('data.id'));
    }

    // ---------------------------------------------------------------
    // Admin roles paginated shape — stable contract
    // ---------------------------------------------------------------

    public function test_admin_roles_paginated_shape_is_stable(): void
    {
        $admin = $this->makeAdmin();

        $res = $this->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/roles?per_page=15');

        $res->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonStructure([
                'ok',
                'data' => [
                    'data' => [
                        '*' => ['id', 'name', 'guard_name'],
                    ],
                    'links',
                    'meta',
                ],
            ]);

        // CONTRACT: data.*.id must be string (ULID public_id), never integer
        $items = $res->json('data.data');
        $this->assertNotEmpty($items, 'List should contain at least one role');
        foreach ($items as $item) {
            $this->assertIsString($item['id'], "Role id must be string (ULID), got: " . gettype($item['id']));
        }
    }

    // ---------------------------------------------------------------
    // Admin user numeric ID rejected — contract: only public_id accepted
    // ---------------------------------------------------------------

    public function test_admin_user_numeric_id_returns_404(): void
    {
        $admin = $this->makeAdmin();

        $user = User::factory()->create(['status' => 'active']);

        $res = $this->actingAs($admin, 'api')
            ->getJson("/api/v1/admin/users/{$user->id}");

        $res->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'NOT_FOUND')
            ->assertJsonStructure([
                'error' => ['code', 'message', 'details'],
            ]);
    }
}
