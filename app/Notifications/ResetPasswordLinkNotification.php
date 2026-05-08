<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordLinkNotification extends Notification
{
    use Queueable;

    public function __construct(private string $token) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $baseUrl = (string) config('kaan.frontend.reset_password_url');

        // Fallback seguro si no configuras aún el frontend URL (en local)
        $url = $baseUrl !== ''
            ? rtrim($baseUrl, '/') . '?token=' . urlencode($this->token) . '&email=' . urlencode($notifiable->getEmailForPasswordReset())
            : config('app.url') . '/reset-password?token=' . urlencode($this->token) . '&email=' . urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Restablecer contraseña')
            ->line('Recibimos una solicitud para restablecer tu contraseña.')
            ->action('Restablecer contraseña', $url)
            ->line('Si tú no solicitaste esto, ignora este correo.');
    }
}
