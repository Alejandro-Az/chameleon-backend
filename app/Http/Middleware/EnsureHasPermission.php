<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class EnsureHasPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = auth('api')->user();

        try {
            $allowed = $user && $user->can($permission);
        } catch (PermissionDoesNotExist $e) {
            // En un entorno sano, esto no debería pasar. Pero en tests/instalación fresca, evita 500.
            $allowed = false;
        }

        if (!$allowed) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'AUTH_FORBIDDEN',
                    'message' => 'No tienes permiso para realizar esta acción.',
                    'details' => null,
                ],
            ], 403);
        }

        return $next($request);
    }
}
