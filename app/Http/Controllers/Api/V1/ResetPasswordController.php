<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\AuthSession;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    use \App\Traits\HasApiResponse;

    public function __invoke(ResetPasswordRequest $request)
    {
        // Rate limiting: 5 intentos / 15 min por IP + email (HMAC para no exponer PII en cache)
        $ip = $request->ip();
        $rawEmail = strtolower(trim((string) $request->input('email', '')));
        $key = 'auth-reset:' . $ip . '|' . hash_hmac('sha256', $rawEmail, config('app.key'));

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return $this->error(
                'AUTH_TOO_MANY_ATTEMPTS',
                "Demasiados intentos. Intente nuevamente en {$seconds} segundos.",
                429
            )->withHeaders(['Retry-After' => $seconds]);
        }

        RateLimiter::hit($key, 900); // 15 minutos

        $data = $request->validated();

        $status = Password::broker()->reset(
            [
                'email' => $data['email'],
                'token' => $data['token'],
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'],
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password, // casteo "hashed" en User
                    'remember_token' => Str::random(60),
                ])->save();

                // Revocar TODAS las sesiones
                AuthSession::query()
                    ->where('user_id', $user->id)
                    ->whereNull('revoked_at')
                    ->update([
                        'revoked_at' => now(),
                        'last_seen_at' => now(),
                    ]);

                \App\Services\AuditLogger::log('auth.password_reset', $user, null, $user);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->error(
                'AUTH_PASSWORD_RESET_INVALID',
                'Token inválido o expirado.',
                422
            );
        }

        return $this->success([
            'message' => 'Contraseña restablecida correctamente. Inicia sesión nuevamente.'
        ]);
    }
}
