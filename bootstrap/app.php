<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('kaan:audit:prune')->daily()->withoutOverlapping()->onOneServer();
        $schedule->command('kaan:sessions:prune-expired')->daily()->withoutOverlapping()->onOneServer();
        $schedule->command('kaan:security:prune-login-attempts')->daily()->withoutOverlapping()->onOneServer();
        $schedule->command('kaan:prune-api-keys')->daily()->withoutOverlapping()->onOneServer();
        $schedule->command('kaan:appointments:send-reminders')->everyMinute()->withoutOverlapping()->onOneServer();
    })
    ->withMiddleware(function (Middleware $middleware) {
        // Security headers en todas las respuestas API (OWASP A05)
        $middleware->appendToGroup('api', \App\Http\Middleware\AddSecurityHeaders::class);

        // Rate limiting global: Tier 1 (60 req/min por IP) definido en AppServiceProvider.
        $middleware->appendToGroup('api', \Illuminate\Routing\Middleware\ThrottleRequests::class.':api');

        // Registrar siempre el middleware; el propio middleware hará early-return
        // si la feature está desactivada.
        $middleware->prependToGroup('api', \App\Http\Middleware\ApplyPolicyOverrides::class);
        $middleware->alias([
            'user.active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'user.verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'jwt.not_revoked' => \App\Http\Middleware\EnsureTokenNotRevoked::class,
            'perm' => \App\Http\Middleware\EnsureHasPermission::class,
            'auth.register.throttle' => \App\Http\Middleware\ThrottlePublicRegistration::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })->withExceptions(function (Exceptions $exceptions): void {
        // Rate limiting global: envelope contractual con Retry-After (CONTRACTS.md §4)
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, $request) {
            $retryAfter = (int) ($e->getHeaders()['Retry-After'] ?? 0);
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Demasiadas solicitudes.',
                    'details' => [
                        'retry_after' => $retryAfter,
                    ],
                ],
            ], 429)->withHeaders($e->getHeaders());
        });

        $exceptions->render(function (\App\Domain\Policy\Exceptions\PolicyNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'error' => [
                        'code' => 'POLICY_NOT_FOUND',
                        'message' => 'Policy no encontrada.',
                        'details' => null,
                    ],
                ], 404);
            }
        });

        $exceptions->render(function (\App\Domain\Policy\Exceptions\PolicyReadOnlyException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'error' => [
                        'code' => 'POLICY_READ_ONLY',
                        'message' => 'Policy es de solo lectura.',
                        'details' => null,
                    ],
                ], 403);
            }
        });

        $exceptions->render(function (\App\Exceptions\Appointments\AppointmentSlotUnavailableException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'error' => [
                        'code' => 'APPOINTMENT_SLOT_UNAVAILABLE',
                        'message' => $e->getMessage(),
                        'details' => $e->details(),
                    ],
                ], 422);
            }
        });

        $exceptions->render(function (\App\Exceptions\Appointments\AppointmentActionWindowClosedException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'error' => [
                        'code' => $e->errorCode(),
                        'message' => $e->getMessage(),
                        'details' => null,
                    ],
                ], 403);
            }
        });

        $exceptions->render(function (\App\Exceptions\Appointments\AppointmentStatusTransitionException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'error' => [
                        'code' => 'APPOINTMENT_STATUS_TRANSITION_INVALID',
                        'message' => $e->getMessage(),
                        'details' => [
                            'from' => $e->fromStatus(),
                            'to' => $e->toStatus(),
                        ],
                    ],
                ], 422);
            }
        });

        $exceptions->render(function (\App\Domain\Policy\Exceptions\PolicyConflictException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'error' => [
                        'code' => 'POLICY_CONFLICT',
                        'message' => $e->getMessage(),
                        'details' => null,
                    ],
                ], 422);
            }
        });

    // 1) Validation Exceptions (Return JSON instead of redirect)
    $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
        if ($request->is('api/*') || $request->wantsJson()) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Error de validación.',
                    'details' => $e->errors(),
                ],
            ], 422);
        }
    });
    

    // 2a) Spatie Role/Permission unauthorized
    $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
        if ($request->is('api/*') || $request->wantsJson()) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'AUTH_FORBIDDEN',
                    'message' => 'No tienes permisos para realizar esta acción.',
                    'details' => null,
                ],
            ], 403);
        }
    });

    // 2) No autenticado (sin token / token inválido para guard)
    $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
        if ($request->is('api/*') || $request->wantsJson()) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'AUTH_UNAUTHENTICATED',
                    'message' => 'No autenticado.',
                    'details' => null,
                ],
            ], 401);
        }
    });

    $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
        if ($request->is('api/*') || $request->wantsJson()) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Recurso no encontrado.',
                    'details' => null,
                ],
            ], 404);
        }
    });

    $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
        if ($request->is('api/*') || $request->wantsJson()) {
            // Si el 404 viene de un fallo en Route Model Binding, es un NOT_FOUND de recurso.
            // Si viene de una ruta que no existe, es un RESOURCE_NOT_FOUND de routing.
            $isModelNotFound = $e->getPrevious() instanceof \Illuminate\Database\Eloquent\ModelNotFoundException;

            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => $isModelNotFound ? 'NOT_FOUND' : 'RESOURCE_NOT_FOUND',
                    'message' => 'Recurso no encontrado.',
                    'details' => null,
                ],
            ], 404);
        }
    });



    // 3) Excepciones JWT Específicas
    $exceptions->render(function (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e, $request) {
        return response()->json(['ok' => false, 'error' => ['code' => 'AUTH_TOKEN_EXPIRED', 'message' => 'Token expirado.', 'details' => null]], 401);
    });
    $exceptions->render(function (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e, $request) {
        return response()->json(['ok' => false, 'error' => ['code' => 'AUTH_TOKEN_INVALID', 'message' => 'Token inválido.', 'details' => null]], 401);
    });
    $exceptions->render(function (\Tymon\JWTAuth\Exceptions\TokenBlacklistedException $e, $request) {
        return response()->json(['ok' => false, 'error' => ['code' => 'AUTH_TOKEN_REVOKED', 'message' => 'Token revocado.', 'details' => null]], 401);
    });

    // 4) JWT General
    $exceptions->render(function (\Tymon\JWTAuth\Exceptions\JWTException $e, $request) {
        return response()->json(['ok' => false, 'error' => ['code' => 'AUTH_JWT_ERROR', 'message' => $e->getMessage() ?: 'Error de autenticación.', 'details' => null]], 401);
    });

    // 5) General API Exceptions
    $exceptions->render(function (\Throwable $e, $request) {
        if ($request->is('api/*') || $request->wantsJson()) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor.',
                    'details' => null,
                ],
            ], 500);
        }
    });
})->create();
