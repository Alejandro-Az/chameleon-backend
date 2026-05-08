<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\AdminStoreAppointmentExceptionRequest;
use App\Http\Requests\Appointment\AdminStoreAppointmentStaffExceptionRequest;
use App\Http\Requests\Appointment\AdminUpdateAppointmentAvailabilityRequest;
use App\Http\Resources\AppointmentStaffProfileResource;
use App\Models\AppointmentException;
use App\Models\AppointmentSetting;
use App\Models\AppointmentStaffException;
use App\Models\AppointmentStaffProfile;
use App\Models\AppointmentWeekdayRule;
use App\Services\Appointments\AppointmentDefaultsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAppointmentAvailabilityController extends Controller
{
    use \App\Traits\HasApiResponse;

    public function __construct(
        protected AppointmentDefaultsService $defaultsService,
    ) {
    }

    public function show()
    {
        $settings = AppointmentSetting::query()->find(1) ?? $this->defaultsService->ensureDefaults();
        $rules = AppointmentWeekdayRule::query()->orderBy('weekday')->get();

        return $this->success([
            'settings' => [
                'business_timezone' => $settings->business_timezone,
                'slot_resolution_minutes' => $settings->slot_resolution_minutes,
                'holidays_auto_block' => $settings->holidays_auto_block,
                'self_service_cancel_hours' => $settings->self_service_cancel_hours,
                'self_service_reschedule_hours' => $settings->self_service_reschedule_hours,
                'auto_confirm_new_appointments' => $settings->auto_confirm_new_appointments,
            ],
            'weekday_rules' => $rules->map(fn ($rule) => [
                'weekday' => $rule->weekday,
                'is_active' => $rule->is_active,
                'start_time' => $rule->start_time,
                'end_time' => $rule->end_time,
            ])->values(),
        ]);
    }

    public function update(AdminUpdateAppointmentAvailabilityRequest $request)
    {
        DB::transaction(function () use ($request) {
            $settings = AppointmentSetting::query()->find(1) ?? $this->defaultsService->ensureDefaults();
            $settings->fill($request->safe()->except('weekday_rules'));
            $settings->save();

            foreach ($request->validated('weekday_rules', []) as $rule) {
                AppointmentWeekdayRule::query()->updateOrCreate(
                    ['weekday' => $rule['weekday']],
                    [
                        'is_active' => $rule['is_active'],
                        'start_time' => $rule['start_time'] ? $rule['start_time'] . ':00' : null,
                        'end_time' => $rule['end_time'] ? $rule['end_time'] . ':00' : null,
                    ],
                );
            }
        });

        return $this->show();
    }

    public function exceptions(Request $request)
    {
        $query = AppointmentException::query()->orderByDesc('exception_date');
        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        return $this->success(['data' => $query->get()->map(fn ($exception) => [
            'id' => $exception->public_id,
            'name' => $exception->name,
            'date' => $exception->exception_date?->toDateString(),
            'type' => $exception->type,
            'start_time' => $exception->start_time,
            'end_time' => $exception->end_time,
            'is_active' => $exception->is_active,
        ])->values()]);
    }

    public function storeException(AdminStoreAppointmentExceptionRequest $request)
    {
        $exception = AppointmentException::query()->create([
            'name' => $request->validated('name'),
            'exception_date' => $request->validated('date'),
            'type' => $request->validated('type'),
            'start_time' => $request->validated('start_time') ? $request->validated('start_time') . ':00' : null,
            'end_time' => $request->validated('end_time') ? $request->validated('end_time') . ':00' : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        \App\Services\AuditLogger::log('appointment.availability.exception.created', $exception, $request->validated());
        return $this->success(['id' => $exception->public_id], 201);
    }

    public function updateException(AdminStoreAppointmentExceptionRequest $request, AppointmentException $appointmentException)
    {
        $appointmentException->fill([
            'name' => $request->validated('name'),
            'exception_date' => $request->validated('date'),
            'type' => $request->validated('type'),
            'start_time' => $request->validated('start_time') ? $request->validated('start_time') . ':00' : null,
            'end_time' => $request->validated('end_time') ? $request->validated('end_time') . ':00' : null,
            'is_active' => $request->boolean('is_active', true),
        ])->save();

        \App\Services\AuditLogger::log('appointment.availability.exception.updated', $appointmentException, $request->validated());
        return $this->success(['id' => $appointmentException->public_id]);
    }

    public function destroyException(AppointmentException $appointmentException)
    {
        $appointmentException->delete();
        \App\Services\AuditLogger::log('appointment.availability.exception.deleted', $appointmentException);
        return $this->success(['message' => 'Excepci�n global eliminada correctamente.']);
    }

    public function showStaff(AppointmentStaffProfile $appointmentStaffProfile)
    {
        return $this->success(new AppointmentStaffProfileResource($appointmentStaffProfile->load(['user', 'weekdayRules', 'exceptions'])));
    }

    public function updateStaff(AdminUpdateAppointmentAvailabilityRequest $request, AppointmentStaffProfile $appointmentStaffProfile)
    {
        DB::transaction(function () use ($request, $appointmentStaffProfile) {
            if ($request->has('weekday_rules')) {
                foreach ($request->validated('weekday_rules', []) as $rule) {
                    $appointmentStaffProfile->weekdayRules()->updateOrCreate(
                        ['weekday' => $rule['weekday']],
                        [
                            'is_active' => $rule['is_active'],
                            'start_time' => $rule['start_time'] ? $rule['start_time'] . ':00' : null,
                            'end_time' => $rule['end_time'] ? $rule['end_time'] . ':00' : null,
                        ],
                    );
                }
            }
        });

        return $this->showStaff($appointmentStaffProfile->fresh(['user', 'weekdayRules', 'exceptions']));
    }

    public function storeStaffException(AdminStoreAppointmentStaffExceptionRequest $request, AppointmentStaffProfile $appointmentStaffProfile)
    {
        $exception = $appointmentStaffProfile->exceptions()->create([
            'name' => $request->validated('name'),
            'exception_date' => $request->validated('date'),
            'type' => $request->validated('type'),
            'start_time' => $request->validated('start_time') ? $request->validated('start_time') . ':00' : null,
            'end_time' => $request->validated('end_time') ? $request->validated('end_time') . ':00' : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        \App\Services\AuditLogger::log('appointment.staff.exception.created', $exception, ['staff_profile_id' => $appointmentStaffProfile->public_id]);
        return $this->success(['id' => $exception->public_id], 201);
    }

    public function updateStaffException(AdminStoreAppointmentStaffExceptionRequest $request, AppointmentStaffProfile $appointmentStaffProfile, AppointmentStaffException $appointmentStaffException)
    {
        if ($appointmentStaffException->appointment_staff_profile_id !== $appointmentStaffProfile->id) {
            return $this->error('NOT_FOUND', 'Excepci�n no encontrada.', 404);
        }

        $appointmentStaffException->fill([
            'name' => $request->validated('name'),
            'exception_date' => $request->validated('date'),
            'type' => $request->validated('type'),
            'start_time' => $request->validated('start_time') ? $request->validated('start_time') . ':00' : null,
            'end_time' => $request->validated('end_time') ? $request->validated('end_time') . ':00' : null,
            'is_active' => $request->boolean('is_active', true),
        ])->save();

        \App\Services\AuditLogger::log('appointment.staff.exception.updated', $appointmentStaffException, ['staff_profile_id' => $appointmentStaffProfile->public_id]);
        return $this->success(['id' => $appointmentStaffException->public_id]);
    }

    public function destroyStaffException(AppointmentStaffProfile $appointmentStaffProfile, AppointmentStaffException $appointmentStaffException)
    {
        if ($appointmentStaffException->appointment_staff_profile_id !== $appointmentStaffProfile->id) {
            return $this->error('NOT_FOUND', 'Excepci�n no encontrada.', 404);
        }

        $appointmentStaffException->delete();
        \App\Services\AuditLogger::log('appointment.staff.exception.deleted', $appointmentStaffException, ['staff_profile_id' => $appointmentStaffProfile->public_id]);
        return $this->success(['message' => 'Excepci�n de staff eliminada correctamente.']);
    }

    public function resetDefaults()
    {
        $settings = $this->defaultsService->ensureDefaults(true);
        \App\Services\AuditLogger::log('appointment.availability.reset_defaults', $settings);
        return $this->show();
    }
}
