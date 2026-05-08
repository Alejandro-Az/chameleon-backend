<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminPermissionsContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create permission for web guard (not present in default seeder)
        Permission::create(['name' => 'web.permission', 'guard_name' => 'web']);
    }

    private function makeAuthorizedAdmin(): User
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $admin->assignRole('admin');
        $this->assertTrue($admin->can('admin.permissions.manage'));

        return $admin;
    }

    public function test_admin_permissions_catalogue_defaults_to_api_guard(): void
    {
        $admin = $this->makeAuthorizedAdmin();

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/permissions');

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonFragment(['name' => 'admin.users.manage'])
            ->assertJsonMissing(['name' => 'web.permission']);
    }

    public function test_admin_permissions_catalogue_supports_explicit_guard_override(): void
    {
        $admin = $this->makeAuthorizedAdmin();

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/permissions?guard_name=web');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'web.permission'])
            ->assertJsonMissing(['name' => 'admin.users.manage']);
    }

    public function test_admin_permissions_catalogue_handles_empty_guard_name_by_defaulting(): void
    {
        $admin = $this->makeAuthorizedAdmin();

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/permissions?guard_name=');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'admin.users.manage']);
    }

    public function test_admin_permissions_catalogue_rejects_array_guard_name(): void
    {
        $admin = $this->makeAuthorizedAdmin();

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/permissions?guard_name[]=api');

        $response->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'error' => [
                    'details' => [
                        'guard_name',
                    ],
                ],
            ]);
    }

    public function test_admin_permissions_catalogue_rejects_invalid_guard_name(): void
    {
        $admin = $this->makeAuthorizedAdmin();

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/permissions?guard_name=invalid_guard');

        $response->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'error' => [
                    'details' => [
                        'guard_name',
                    ],
                ],
            ]);
    }
}
