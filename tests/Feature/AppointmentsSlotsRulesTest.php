<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentException;
use App\Models\AppointmentService;
use App\Models\AppointmentStaffProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class AppointmentsSlotsRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\AppointmentModuleSeeder::class);
    }

    public function test_slots_are_empty_on_inactive_weekday(): void
    {
        [$customer, $service] = $this->createEligibilityContext();

        $sunday = Carbon::now('America/Mexico_City')->next(Carbon::SUNDAY);

        $response = $this->actingAs($customer, 'api')
            ->getJson('/api/v1/appointments/slots?service_id=' . $service->public_id . '&date=' . $sunday->toDateString());

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonCount(0, 'data.data');
    }

    public function test_slots_are_empty_on_holiday_exception(): void
    {
        [$customer, $service] = $this->createEligibilityContext();

        $holiday = AppointmentException::query()
            ->where('type', AppointmentException::TYPE_HOLIDAY)
            ->orderBy('exception_date')
            ->firstOrFail();

        $response = $this->actingAs($customer, 'api')
            ->getJson('/api/v1/appointments/slots?service_id=' . $service->public_id . '&date=' . $holiday->exception_date->toDateString());

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonCount(0, 'data.data');
    }

    public function test_booking_fails_when_no_eligible_employee_for_service(): void
    {
        $customer = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $service = AppointmentService::query()->create([
            'name' => 'Servicio sin staff',
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $startsAt = Carbon::now('America/Mexico_City')->addDays(2)->setTime(10, 0)->toIso8601String();

        $this->actingAs($customer, 'api')
            ->postJson('/api/v1/appointments', [
                'service_id' => $service->public_id,
                'starts_at' => $startsAt,
            ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'APPOINTMENT_SLOT_UNAVAILABLE')
            ->assertJsonPath('error.details.reason', 'employee_unavailable');
    }

    public function test_booking_fails_when_assigned_employee_is_busy(): void
    {
        [$customer, $service, $employee] = $this->createEligibilityContext();

        $startsAtLocal = Carbon::now('America/Mexico_City')->addDays(3)->setTime(10, 0)->second(0);
        while (! in_array($startsAtLocal->isoWeekday(), [1, 2, 3, 4, 5], true)) {
            $startsAtLocal->addDay()->setTime(10, 0);
        }

        Appointment::query()->create([
            'user_id' => $customer->id,
            'appointment_service_id' => $service->id,
            'assigned_employee_user_id' => $employee->id,
            'created_by_user_id' => $customer->id,
            'starts_at' => $startsAtLocal->copy()->setTimezone('UTC'),
            'ends_at' => $startsAtLocal->copy()->addMinutes(60)->setTimezone('UTC'),
            'status' => Appointment::STATUS_CONFIRMED,
            'source' => Appointment::SOURCE_INTERNAL,
            'business_timezone' => 'America/Mexico_City',
        ]);

        $this->actingAs($customer, 'api')
            ->postJson('/api/v1/appointments', [
                'service_id' => $service->public_id,
                'starts_at' => $startsAtLocal->toIso8601String(),
            ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'APPOINTMENT_SLOT_UNAVAILABLE')
            ->assertJsonPath('error.details.reason', 'slot_taken');
    }

    private function createEligibilityContext(): array
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

        AppointmentStaffProfile::query()->create([
            'user_id' => $employee->id,
            'is_active' => true,
            'can_receive_auto_assignment' => true,
        ]);

        $service = AppointmentService::query()->create([
            'name' => 'Servicio elegible',
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $service->staff()->sync([$employee->id]);

        return [$customer, $service, $employee];
    }
}
