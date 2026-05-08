<?php

namespace App\Services\Appointments;

use App\Exceptions\Appointments\AppointmentActionWindowClosedException;
use App\Exceptions\Appointments\AppointmentStatusTransitionException;
use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Models\AppointmentService;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentLifecycleService
{
    public function __construct(
        protected AppointmentAvailabilityService $availabilityService,
        protected AppointmentNotificationService $notificationService,
    ) {
    }

    public function createForUser(User $actor, array $data): Appointment
    {
        $service = AppointmentService::query()->where('public_id', $data['service_id'])->firstOrFail();
        $slot = $this->availabilityService->resolveSlot($service, Carbon::parse($data['starts_at'], $this->availabilityService->settings()->business_timezone));

        $appointment = DB::transaction(function () use ($actor, $service, $slot, $data) {
            $appointment = Appointment::query()->create([
                'user_id' => $actor->id,
                'appointment_service_id' => $service->id,
                'assigned_employee_user_id' => $slot['employee']->id,
                'created_by_user_id' => $actor->id,
                'starts_at' => $slot['starts_at_utc'],
                'ends_at' => $slot['ends_at_utc'],
                'status' => Appointment::STATUS_CONFIRMED,
                'source' => Appointment::SOURCE_SELF_SERVICE,
                'business_timezone' => $this->availabilityService->settings()->business_timezone,
                'customer_notes' => $data['customer_notes'] ?? null,
            ]);

            $this->syncReminders($appointment->fresh());
            AuditLogger::log('appointment.created.self_service', $appointment, ['service_id' => $service->public_id]);

            return $appointment->fresh(['service', 'assignedEmployee', 'user']);
        });

        $this->notificationService->queueCreated($appointment);

        return $appointment;
    }

    public function createInternal(User $actor, array $data): Appointment
    {
        $service = AppointmentService::query()->where('public_id', $data['service_id'])->firstOrFail();
        $preferredEmployee = !empty($data['assigned_employee_id'])
            ? User::query()->where('public_id', $data['assigned_employee_id'])->firstOrFail()
            : null;
        $slot = $this->availabilityService->resolveSlot($service, Carbon::parse($data['starts_at'], $this->availabilityService->settings()->business_timezone), $preferredEmployee);
        $customer = !empty($data['user_id']) ? User::query()->where('public_id', $data['user_id'])->firstOrFail() : null;

        $appointment = DB::transaction(function () use ($actor, $service, $slot, $customer, $data) {
            $appointment = Appointment::query()->create([
                'user_id' => $customer?->id,
                'guest_name' => $customer ? null : ($data['guest_name'] ?? null),
                'guest_phone' => $customer ? null : ($data['guest_phone'] ?? null),
                'guest_email' => $customer ? ($data['guest_email'] ?? $customer->email) : ($data['guest_email'] ?? null),
                'appointment_service_id' => $service->id,
                'assigned_employee_user_id' => $slot['employee']->id,
                'created_by_user_id' => $actor->id,
                'starts_at' => $slot['starts_at_utc'],
                'ends_at' => $slot['ends_at_utc'],
                'status' => Appointment::STATUS_CONFIRMED,
                'source' => Appointment::SOURCE_INTERNAL,
                'business_timezone' => $this->availabilityService->settings()->business_timezone,
                'customer_notes' => $data['customer_notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
            ]);

            $this->syncReminders($appointment->fresh());
            AuditLogger::log('appointment.created.internal', $appointment, ['service_id' => $service->public_id]);

            return $appointment->fresh(['service', 'assignedEmployee', 'user']);
        });

        $this->notificationService->queueCreated($appointment);

        return $appointment;
    }

    public function reschedule(Appointment $appointment, User $actor, string $startsAt, bool $enforceWindow = false, ?string $preferredEmployeeId = null): Appointment
    {
        if ($enforceWindow) {
            $this->guardWindow($appointment, 'APPOINTMENT_RESCHEDULE_WINDOW_CLOSED', 'La ventana para reagendar ya se cerr�.');
        }

        $service = $appointment->service()->firstOrFail();
        $preferredEmployee = $preferredEmployeeId ? User::query()->where('public_id', $preferredEmployeeId)->firstOrFail() : null;
        $slot = $this->availabilityService->resolveSlot($service, Carbon::parse($startsAt, $appointment->business_timezone), $preferredEmployee);

        DB::transaction(function () use ($appointment, $actor, $slot) {
            $appointment->forceFill([
                'assigned_employee_user_id' => $slot['employee']->id,
                'starts_at' => $slot['starts_at_utc'],
                'ends_at' => $slot['ends_at_utc'],
                'cancelled_at' => null,
                'cancelled_by_user_id' => null,
                'cancellation_reason' => null,
                'status' => Appointment::STATUS_CONFIRMED,
            ])->save();

            $this->syncReminders($appointment->fresh());
            AuditLogger::log('appointment.rescheduled', $appointment, ['actor_user_id' => $actor->public_id ?? null]);
        });

        $appointment = $appointment->fresh(['service', 'assignedEmployee', 'user']);
        $this->notificationService->queueRescheduled($appointment);

        return $appointment;
    }

    public function cancel(Appointment $appointment, User $actor, ?string $reason = null, bool $enforceWindow = false): Appointment
    {
        if ($enforceWindow) {
            $this->guardWindow($appointment, 'APPOINTMENT_CANCELLATION_WINDOW_CLOSED', 'La ventana para cancelar ya se cerr�.');
        }

        DB::transaction(function () use ($appointment, $actor, $reason) {
            $appointment->forceFill([
                'status' => Appointment::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by_user_id' => $actor->id,
                'cancellation_reason' => $reason,
            ])->save();

            AppointmentReminder::query()->where('appointment_id', $appointment->id)->where('status', AppointmentReminder::STATUS_PENDING)->update([
                'status' => AppointmentReminder::STATUS_CANCELLED,
            ]);

            AuditLogger::log('appointment.cancelled', $appointment, ['reason' => $reason]);
        });

        $appointment = $appointment->fresh(['service', 'assignedEmployee', 'user']);
        $this->notificationService->queueCancelled($appointment);

        return $appointment;
    }

    public function updateStatus(Appointment $appointment, User $actor, string $status): Appointment
    {
        $allowed = [
            Appointment::STATUS_PENDING => [Appointment::STATUS_CONFIRMED, Appointment::STATUS_CANCELLED],
            Appointment::STATUS_CONFIRMED => [Appointment::STATUS_COMPLETED, Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW],
            Appointment::STATUS_COMPLETED => [],
            Appointment::STATUS_CANCELLED => [],
            Appointment::STATUS_NO_SHOW => [],
        ];

        if (! in_array($status, $allowed[$appointment->status] ?? [], true)) {
            throw new AppointmentStatusTransitionException($appointment->status, $status);
        }

        $appointment->status = $status;
        if ($status === Appointment::STATUS_COMPLETED) {
            $appointment->completed_at = now();
        }
        if ($status === Appointment::STATUS_NO_SHOW) {
            $appointment->no_show_marked_at = now();
        }
        $appointment->save();

        AuditLogger::log('appointment.status_updated', $appointment, ['from' => $appointment->getOriginal('status'), 'to' => $status, 'actor_user_id' => $actor->public_id ?? null]);

        return $appointment->fresh(['service', 'assignedEmployee', 'user']);
    }

    public function assignEmployee(Appointment $appointment, User $actor, ?string $preferredEmployeeId = null): Appointment
    {
        $preferredEmployee = $preferredEmployeeId ? User::query()->where('public_id', $preferredEmployeeId)->firstOrFail() : null;
        $slot = $this->availabilityService->resolveSlot($appointment->service()->firstOrFail(), $appointment->starts_at->copy()->setTimezone($appointment->business_timezone), $preferredEmployee);
        $appointment->assigned_employee_user_id = $slot['employee']->id;
        $appointment->save();
        $this->syncReminders($appointment->fresh());

        AuditLogger::log('appointment.employee_assigned', $appointment, ['assigned_employee_id' => $slot['employee']->public_id]);

        return $appointment->fresh(['service', 'assignedEmployee', 'user']);
    }

    public function syncReminders(Appointment $appointment): void
    {
        AppointmentReminder::query()->where('appointment_id', $appointment->id)->delete();

        if ($appointment->status !== Appointment::STATUS_CONFIRMED) {
            return;
        }

        $recipientEmail = $appointment->guest_email ?: $appointment->user?->email;
        if (! $recipientEmail) {
            return;
        }

        $reminders = [
            AppointmentReminder::TYPE_REMINDER_24H => $appointment->starts_at->copy()->subDay(),
            AppointmentReminder::TYPE_REMINDER_2H => $appointment->starts_at->copy()->subHours(2),
        ];

        foreach ($reminders as $type => $scheduledFor) {
            if ($scheduledFor->lessThanOrEqualTo(now())) {
                continue;
            }

            AppointmentReminder::query()->create([
                'appointment_id' => $appointment->id,
                'type' => $type,
                'scheduled_for' => $scheduledFor,
                'status' => AppointmentReminder::STATUS_PENDING,
            ]);
        }
    }

    private function guardWindow(Appointment $appointment, string $code, string $message): void
    {
        $settings = $this->availabilityService->settings();
        $hours = $code === 'APPOINTMENT_CANCELLATION_WINDOW_CLOSED'
            ? $settings->self_service_cancel_hours
            : $settings->self_service_reschedule_hours;

        if ($appointment->starts_at->lessThanOrEqualTo(now()->addHours($hours))) {
            throw new AppointmentActionWindowClosedException($code, $message);
        }
    }
}
