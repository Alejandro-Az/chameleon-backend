<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottlePublicRegistration
{
    /**
     * Dual rate limit: by IP (5/min) + by email HMAC (3/10min).
     * Runs BEFORE validation to resist brute-force / enumeration.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $ipKey = 'auth-register:ip:' . $ip;

        if (RateLimiter::tooManyAttempts($ipKey, 5)) {
            return $this->tooManyResponse(RateLimiter::availableIn($ipKey));
        }

        // Email key: use HMAC so no PII leaks into Redis/cache keys
        $rawEmail = strtolower(trim((string) $request->input('email', '')));

        if ($rawEmail !== '') {
            $emailKey = 'auth-register:email:' . hash_hmac('sha256', $rawEmail, config('app.key'));

            if (RateLimiter::tooManyAttempts($emailKey, 3)) {
                return $this->tooManyResponse(RateLimiter::availableIn($emailKey));
            }

            RateLimiter::hit($emailKey, 600); // 10 minutes
        }

        RateLimiter::hit($ipKey, 60); // 1 minute

        return $next($request);
    }

    private function tooManyResponse(int $retryAfter): Response
    {
        return response()->json([
            'ok'    => false,
            'error' => [
                'code'    => 'AUTH_TOO_MANY_REQUESTS',
                'message' => "Demasiados intentos de registro. Intente nuevamente en {$retryAfter} segundos.",
                'details' => null,
            ],
        ], 429)->withHeaders([
            'Retry-After' => $retryAfter,
        ]);
    }
}
