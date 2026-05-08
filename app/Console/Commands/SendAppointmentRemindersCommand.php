<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Services\Appointments\AppointmentNotificationService;
use Throwable;
use Illuminate\Console\Command;

class SendAppointmentRemindersCommand extends Command
{
    protected $signature = 'kaan:appointments:send-reminders';

    protected $description = 'Envia recordatorios pendientes de citas';

    public function handle(AppointmentNotificationService $notificationService): int
    {
        AppointmentReminder::query()
            ->where('status', AppointmentReminder::STATUS_PENDING)
            ->where('scheduled_for', '<=', now())
            ->with('appointment.service', 'appointment.assignedEmployee', 'appointment.user')
            ->orderBy('scheduled_for')
            ->chunkById(100, function ($reminders) use ($notificationService) {
                foreach ($reminders as $reminder) {
                    try {
                        if (! $reminder->appointment || $reminder->appointment->status !== Appointment::STATUS_CONFIRMED) {
                            $reminder->update(['status' => AppointmentReminder::STATUS_CANCELLED]);
                            continue;
                        }

                        $notificationService->sendReminder($reminder);
                        $reminder->update([
                            'status' => AppointmentReminder::STATUS_SENT,
                            'sent_at' => now(),
                            'last_error' => null,
                        ]);
                    } catch (Throwable $e) {
                        $reminder->update([
                            'status' => AppointmentReminder::STATUS_FAILED,
                            'last_error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return self::SUCCESS;
    }
}
