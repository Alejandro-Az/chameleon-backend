<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('kaan.auth.require_verified_email', false)) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && !$user->hasVerifiedEmail()) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'AUTH_EMAIL_NOT_VERIFIED',
                    'message' => 'Debe verificar su correo electrónico para acceder a este recurso.',
                    'details' => null,
                ],
            ], 403);
        }

        return $next($request);
    }
}
