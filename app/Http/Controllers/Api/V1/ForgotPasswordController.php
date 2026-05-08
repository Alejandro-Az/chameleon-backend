<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

class ForgotPasswordController extends Controller
{
    use \App\Traits\HasApiResponse;

    public function __invoke(ForgotPasswordRequest $request)
    {
        $email = strtolower(trim($request->validated('email')));
        $key = 'auth-forgot:' . $request->ip() . '|' . $email;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return $this->error(
                'AUTH_TOO_MANY_ATTEMPTS',
                "Demasiadas solicitudes. Intenta en {$seconds} segundos.",
                429
            )->withHeaders(['Retry-After' => $seconds]);
        }

        RateLimiter::hit($key, 60);

        // Nunca revelamos si existe o no.
        Password::broker()->sendResetLink(['email' => $email]);

        return $this->success([
            'message' => 'Si el correo existe, se enviaron instrucciones para restablecer la contraseña.'
        ]);
    }
}
