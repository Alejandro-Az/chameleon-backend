<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\AppointmentStaffProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class AppointmentsSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\AppointmentModuleSeeder::class);
    }

    public function test_user_can_book_own_appointment_with_auto_assignment(): void
    {
        [$customer, $service] = $this->createBookableContext();

        $slot = null;
        $cursor = Carbon::now('America/Mexico_City')->addDay();

        for ($i = 0; $i < 30; $i++) {
            if (! in_array($cursor->isoWeekday(), [1, 2, 3, 4, 5], true)) {
                $cursor->addDay();
                continue;
            }

            $slotsResponse = $this->actingAs($customer, 'api')
                ->getJson('/api/v1/appointments/slots?service_id=' . $service->public_id . '&date=' . $cursor->toDateString());

            $slotsResponse->assertOk()->assertJsonPath('ok', true);
            $candidate = $slotsResponse->json('data.data.0');
            if (is_array($candidate)) {
                $slot = $candidate;
                break;
            }

            $cursor->addDay();
        }

        $this->assertIsArray($slot, 'No se encontraron slots para reservar en los proximos 30 dias habiles.');

        $bookResponse = $this->actingAs($customer, 'api')->postJson('/api/v1/appointments', [
            'service_id' => $service->public_id,
            'starts_at' => $slot['starts_at'],
            'customer_notes' => 'Prefiero cita por la manana',
        ]);

        $bookResponse->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.status', Appointment::STATUS_CONFIRMED)
            ->assertJsonPath('data.user.id', $customer->public_id)
            ->assertJsonPath('data.service.id', $service->public_id);

        $this->assertDatabaseHas('appointments', [
            'public_id' => $bookResponse->json('data.id'),
            'user_id' => $customer->id,
            'appointment_service_id' => $service->id,
            'status' => Appointment::STATUS_CONFIRMED,
            'source' => Appointment::SOURCE_SELF_SERVICE,
        ]);
    }

    public function test_user_cannot_view_other_user_appointment(): void
    {
        [$owner, $service, $employee] = $this->createBookableContext();

        $appointment = Appointment::query()->create([
            'user_id' => $owner->id,
            'appointment_service_id' => $service->id,
            'assigned_employee_user_id' => $employee->id,
            'created_by_user_id' => $owner->id,
            'starts_at' => Carbon::now('UTC')->addDays(3)->setTime(16, 0),
            'ends_at' => Carbon::now('UTC')->addDays(3)->setTime(17, 0),
            'status' => Appointment::STATUS_CONFIRMED,
            'source' => Appointment::SOURCE_SELF_SERVICE,
            'business_timezone' => 'America/Mexico_City',
        ]);

        $otherUser = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($otherUser, 'api')
            ->getJson('/api/v1/appointments/' . $appointment->public_id)
            ->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_cancel_is_blocked_when_window_is_closed(): void
    {
        [$customer, $service, $employee] = $this->createBookableContext();

        $appointment = Appointment::query()->create([
            'user_id' => $customer->id,
            'appointment_service_id' => $service->id,
            'assigned_employee_user_id' => $employee->id,
            'created_by_user_id' => $customer->id,
            'starts_at' => now('UTC')->addHours(6),
            'ends_at' => now('UTC')->addHours(7),
            'status' => Appointment::STATUS_CONFIRMED,
            'source' => Appointment::SOURCE_SELF_SERVICE,
            'business_timezone' => 'America/Mexico_City',
        ]);

        $this->actingAs($customer, 'api')
            ->postJson('/api/v1/appointments/' . $appointment->public_id . '/cancel', [
                'reason' => 'No podre asistir',
            ])
            ->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'APPOINTMENT_CANCELLATION_WINDOW_CLOSED');
    }

    public function test_reschedule_is_blocked_when_window_is_closed(): void
    {
        [$customer, $service, $employee] = $this->createBookableContext();

        $appointment = Appointment::query()->create([
            'user_id' => $customer->id,
            'appointment_service_id' => $service->id,
            'assigned_employee_user_id' => $employee->id,
            'created_by_user_id' => $customer->id,
            'starts_at' => now('UTC')->addHours(8),
            'ends_at' => now('UTC')->addHours(9),
            'status' => Appointment::STATUS_CONFIRMED,
            'source' => Appointment::SOURCE_SELF_SERVICE,
            'business_timezone' => 'America/Mexico_City',
        ]);

        $this->actingAs($customer, 'api')
            ->postJson('/api/v1/appointments/' . $appointment->public_id . '/reschedule', [
                'starts_at' => now('America/Mexico_City')->addDays(2)->setTime(10, 0)->toIso8601String(),
            ])
            ->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'APPOINTMENT_RESCHEDULE_WINDOW_CLOSED');
    }

    private function createBookableContext(): array
    {
        $customer = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $employee = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $employee->assignRole('employee');

        $service = AppointmentService::query()->create([
            'name' => 'Consulta general',
            'description' => 'Consulta estandar de prueba',
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        AppointmentStaffProfile::query()->create([
            'user_id' => $employee->id,
            'is_active' => true,
            'can_receive_auto_assignment' => true,
        ]);

        $service->staff()->sync([$employee->id]);

        return [$customer, $service, $employee];
    }
}
