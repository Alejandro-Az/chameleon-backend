<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if ($user && $user->status !== 'active') {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'AUTH_USER_INACTIVE',
                    'message' => 'Usuario no activo o suspendido.',
                    'details' => null,
                ],
            ], 403);
        }

        return $next($request);
    }
}
