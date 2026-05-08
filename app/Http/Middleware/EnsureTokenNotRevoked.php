<?php

namespace App\Http\Middleware;

use App\Models\AuthSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;

class EnsureTokenNotRevoked
{
    private const TOUCH_THROTTLE_SECONDS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Defense in depth: Ensure we can parse the token successfully
            $payload = JWTAuth::parseToken()->getPayload();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'ok' => false,
                'error' => ['code' => 'AUTH_TOKEN_EXPIRED', 'message' => 'Token expirado.', 'details' => null],
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenBlacklistedException $e) {
            return response()->json([
                'ok' => false,
                'error' => ['code' => 'AUTH_TOKEN_REVOKED', 'message' => 'Token revocado o blacklistado.', 'details' => null],
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'ok' => false,
                'error' => ['code' => 'AUTH_TOKEN_INVALID', 'message' => 'Token inválido.', 'details' => null],
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'ok' => false,
                'error' => ['code' => 'AUTH_JWT_ERROR', 'message' => 'Error de autenticación JWT.', 'details' => null],
            ], 401);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => ['code' => 'AUTH_TOKEN_INVALID', 'message' => 'Token inválido o no proporcionado.', 'details' => null],
            ], 401);
        }

        $jti = (string) $payload->get('jti');
        if ($jti === '') {
            return response()->json([
                'ok' => false,
                'error' => ['code' => 'AUTH_TOKEN_INVALID', 'message' => 'Token inválido (sin JTI).', 'details' => null],
            ], 401);
        }

        $session = AuthSession::query()
            ->where('token_id', $jti)
            ->first();

        // Fail-closed
        if (!$session) {
            return response()->json([
                'ok' => false,
                'error' => ['code' => 'AUTH_SESSION_NOT_FOUND', 'message' => 'Sesión no encontrada.', 'details' => null],
            ], 401);
        }

        if ($session->revoked_at !== null) {
            return response()->json([
                'ok' => false,
                'error' => ['code' => 'AUTH_TOKEN_REVOKED', 'message' => 'Token revocado.', 'details' => null],
            ], 401);
        }

        if ($session->expires_at !== null && now()->greaterThan($session->expires_at)) {
            return response()->json([
                'ok' => false,
                'error' => ['code' => 'AUTH_SESSION_EXPIRED', 'message' => 'Sesión expirada.', 'details' => null],
            ], 401);
        }

        // Session touch (last_seen_at) is intentionally handled here rather than in a separate
        // TouchAuthSession middleware, to avoid an additional DB query per request.
        // Touch conservador, atómico a nivel base de datos para evitar race conditions
        try {
            AuthSession::where('token_id', $jti)
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->where(function($query) {
                    $query->whereNull('last_seen_at')
                          ->orWhere('last_seen_at', '<=', now()->subSeconds(self::TOUCH_THROTTLE_SECONDS));
                })
                ->update(['last_seen_at' => now()]);
        } catch (\Throwable $e) {
            // Ignorar errores de escritura de última vez
        }

        // Setup current JTI cleanly for subsequent requests/resources (no global mutants)
        $request->attributes->set('current_jti', $jti);

        return $next($request);
    }
}
