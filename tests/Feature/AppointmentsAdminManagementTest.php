<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AppointmentService;
use App\Models\AppointmentSetting;
use App\Models\AppointmentStaffProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AppointmentsAdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\AppointmentModuleSeeder::class);
    }

    public function test_admin_can_crud_appointment_service_with_staff_assignment(): void
    {
        $admin = $this->createAdmin();
        $employee = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $store = $this->actingAs($admin, 'api')->postJson('/api/v1/admin/appointment-services', [
            'name' => 'Consulta premium',
            'description' => 'Servicio de prueba',
            'duration_minutes' => 45,
            'is_active' => true,
            'staff_ids' => [$employee->public_id],
        ]);

        $store->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.name', 'Consulta premium')
            ->assertJsonPath('data.duration_minutes', 45);

        $serviceId = $store->json('data.id');
        $service = AppointmentService::query()->where('public_id', $serviceId)->firstOrFail();
        $this->assertDatabaseHas('appointment_service_staff', [
            'appointment_service_id' => $service->id,
            'user_id' => $employee->id,
        ]);

        $update = $this->actingAs($admin, 'api')->patchJson('/api/v1/admin/appointment-services/' . $serviceId, [
            'duration_minutes' => 60,
            'is_active' => false,
            'staff_ids' => [],
        ]);

        $update->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.duration_minutes', 60)
            ->assertJsonPath('data.is_active', false);

        $this->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/appointment-services/' . $serviceId)
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.id', $serviceId);

        $this->actingAs($admin, 'api')
            ->deleteJson('/api/v1/admin/appointment-services/' . $serviceId)
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('appointment_services', ['public_id' => $serviceId]);
    }

    public function test_admin_can_manage_staff_profiles_and_assign_employee_role(): void
    {
        $admin = $this->createAdmin();
        $staffUser = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $store = $this->actingAs($admin, 'api')->postJson('/api/v1/admin/appointment-staff', [
            'user_id' => $staffUser->public_id,
            'is_active' => true,
            'can_receive_auto_assignment' => true,
            'notes' => 'Alta inicial de staff',
        ]);

        $store->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.user.id', $staffUser->public_id);

        $this->assertTrue($staffUser->fresh()->hasRole('employee'));

        $profileId = $store->json('data.id');

        $this->actingAs($admin, 'api')
            ->patchJson('/api/v1/admin/appointment-staff/' . $profileId, [
                'is_active' => false,
                'notes' => 'Temporalmente inactivo',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.notes', 'Temporalmente inactivo');

        $this->actingAs($admin, 'api')
            ->deleteJson('/api/v1/admin/appointment-staff/' . $profileId)
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('appointment_staff_profiles', ['public_id' => $profileId]);
    }

    public function test_admin_can_update_availability_and_reset_defaults(): void
    {
        $admin = $this->createAdmin();

        $update = $this->actingAs($admin, 'api')->putJson('/api/v1/admin/appointment-availability', [
            'slot_resolution_minutes' => 20,
            'business_timezone' => 'America/Bogota',
            'self_service_cancel_hours' => 12,
            'self_service_reschedule_hours' => 18,
            'holidays_auto_block' => false,
        ]);

        $update->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.settings.slot_resolution_minutes', 20)
            ->assertJsonPath('data.settings.business_timezone', 'America/Bogota');

        $settings = AppointmentSetting::query()->findOrFail(1);
        $this->assertSame(20, $settings->slot_resolution_minutes);
        $this->assertSame('America/Bogota', $settings->business_timezone);

        $this->actingAs($admin, 'api')
            ->postJson('/api/v1/admin/appointment-availability/reset-defaults')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.settings.slot_resolution_minutes', 30)
            ->assertJsonPath('data.settings.business_timezone', 'America/Mexico_City');
    }

    public function test_non_admin_is_forbidden_in_appointment_admin_endpoints(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $user->assignRole('user');

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/admin/appointment-services')
            ->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        return $admin;
    }
}
