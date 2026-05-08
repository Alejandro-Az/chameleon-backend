<?php

namespace App\Services\Appointments;

use App\Mail\AppointmentNotificationMail;
use App\Models\Appointment;
use App\Models\AppointmentReminder;
use Illuminate\Support\Facades\Mail;

class AppointmentNotificationService
{
    public function queueCreated(Appointment $appointment): void
    {
        $this->queueMessage($appointment, 'Cita confirmada', 'Tu cita ha sido confirmada.');
    }

    public function queueRescheduled(Appointment $appointment): void
    {
        $this->queueMessage($appointment, 'Cita reagendada', 'Tu cita fue reagendada exitosamente.');
    }

    public function queueCancelled(Appointment $appointment): void
    {
        $this->queueMessage($appointment, 'Cita cancelada', 'Tu cita ha sido cancelada.');
    }

    public function sendReminder(AppointmentReminder $reminder): void
    {
        $appointment = $reminder->appointment()->with(['service', 'assignedEmployee'])->firstOrFail();
        $message = $reminder->type === AppointmentReminder::TYPE_REMINDER_24H
            ? 'Recordatorio: tu cita es dentro de 24 horas.'
            : 'Recordatorio: tu cita es dentro de 2 horas.';

        $this->sendMessage($appointment, 'Recordatorio de cita', $message);
    }

    private function queueMessage(Appointment $appointment, string $title, string $message): void
    {
        if (! $email = $this->resolveRecipientEmail($appointment)) {
            return;
        }

        Mail::to($email)->queue(new AppointmentNotificationMail($appointment->loadMissing(['service', 'assignedEmployee']), $title, $message));
    }

    private function sendMessage(Appointment $appointment, string $title, string $message): void
    {
        if (! $email = $this->resolveRecipientEmail($appointment)) {
            return;
        }

        Mail::to($email)->send(new AppointmentNotificationMail($appointment, $title, $message));
    }

    private function resolveRecipientEmail(Appointment $appointment): ?string
    {
        return $appointment->guest_email ?: $appointment->user?->email;
    }
}
