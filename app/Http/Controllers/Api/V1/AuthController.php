<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuthSession;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Services\Auth\JwtSessionIssuer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use \App\Traits\HasApiResponse;

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     tags={"Auth"},
     *     summary="Login de usuario",
     *     description="Retorna JWT. Soporta email o username según configuración.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"login","password"},
     *             @OA\Property(property="login", type="string", example="admin@camaleon.dev",
     *                 description="Email o username del usuario"),
     *             @OA\Property(property="password", type="string", format="password", example="Secret123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1Q..."),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Credenciales inválidas"),
     *     @OA\Response(response=422, description="Validación fallida"),
     *     @OA\Response(response=429, description="Demasiados intentos — rate limit")
     * )
     */
    public function login(Request $request, JwtSessionIssuer $issuer)
    {
        $request->validate([
            'login' => ['required','string'],
            'password' => ['required','string'],
        ]);

        $ip = $request->ip();
        $loginParam = (string) $request->input('login');
        $loginNorm  = strtolower(trim($loginParam));
        $loginHmac  = hash_hmac('sha256', $loginNorm, config('app.key'));
        $throttleKey = "auth-login:{$ip}|{$loginHmac}";
        
        // --- Layer 1: RateLimiter (fast, in-memory, per IP+login) ---
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->error(
                'AUTH_TOO_MANY_ATTEMPTS', 
                "Demasiados intentos de inicio de sesión. Intente nuevamente en {$seconds} segundos.", 
                429
            )->withHeaders(['Retry-After' => $seconds]);
        }

        // --- Layer 2: DB-backed blocking (persistent, per email) ---
        if (config('kaan.features.login_attempts', true)) {
            if (LoginAttempt::isBlocked($loginNorm)) {
                $retryAfter = LoginAttempt::secondsUntilUnblocked($loginNorm);
                LoginAttempt::record($loginNorm, $ip, $request->userAgent(), LoginAttempt::STATUS_BLOCKED);
                return $this->error(
                    'AUTH_ACCOUNT_LOCKED',
                    'Cuenta temporalmente bloqueada por múltiples intentos fallidos. Intente más tarde.',
                    429
                )->withHeaders(['Retry-After' => $retryAfter]);
            }
        }

        $loginField = config('kaan.auth.login_field', 'email');
        $password = (string)$request->input('password');

        $userQuery = User::query();

        if ($loginField === 'email') {
            $userQuery->where('email', $loginNorm);
        } else { // email_or_username
            $userQuery->where('email', $loginNorm)->orWhere('username', $loginNorm);
        }

        /** @var User|null $user */
        $user = $userQuery->first();

        RateLimiter::hit($throttleKey, 60);

        if (!$user || !Hash::check($password, $user->password)) {
            // --- Record failed attempt in DB ---
            if (config('kaan.features.login_attempts', true)) {
                LoginAttempt::record($loginNorm, $ip, $request->userAgent(), LoginAttempt::STATUS_FAILED);

                // Auto-block after 5 failures in 15 minutes
                $failures = LoginAttempt::recentFailures($loginNorm, 15);
                if ($failures >= 5) {
                    $blockedUntil = now()->addMinutes(15);
                    LoginAttempt::record($loginNorm, $ip, $request->userAgent(), LoginAttempt::STATUS_BLOCKED, $blockedUntil);
                }
            }

            return $this->error('AUTH_INVALID', 'Credenciales inválidas.', 401);
        }

        if ($user->status !== 'active') {
            return $this->error('AUTH_USER_INACTIVE', 'Usuario no activo.', 403);
        }

        if (config('kaan.auth.require_verified_email', false) && ! $user->hasVerifiedEmail()) {
            return $this->error('AUTH_EMAIL_NOT_VERIFIED', 'Debe verificar su correo para iniciar sesión.', 403);
        }

        // Éxito: limpiar intentos
        RateLimiter::clear($throttleKey);

        // Record success + clear any blocks
        if (config('kaan.features.login_attempts', true)) {
            LoginAttempt::record($loginNorm, $ip, $request->userAgent(), LoginAttempt::STATUS_SUCCESS);
        }

        // Unified JWT + session issuance
        $tokenData = $issuer->issue($user, $request);

        $user->forceFill(['last_login_at' => now()])->save();

        \App\Services\AuditLogger::log('auth.login', $user, null, $user);

        return $this->success(array_merge($tokenData, [
            'user' => [
                'id' => $user->public_id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
            ],
        ]));
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     tags={"Auth"},
     *     summary="Usuario autenticado",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Datos del usuario",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="01HXYZ..."),
     *                 @OA\Property(property="name", type="string", example="Pablo Azuara"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string", example="master"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function me()
    {
        $user = auth('api')->user();

        return $this->success([
            'id' => $user->public_id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     tags={"Auth"},
     *     summary="Cerrar sesión",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Sesión cerrada"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function logout(Request $request)
    {
        $user = auth('api')->user(); // Obtener usuario antes de invalidar
        $token = JWTAuth::parseToken()->getToken();
        $payload = JWTAuth::parseToken()->getPayload();
        $jti = (string) $payload->get('jti');

        AuthSession::where('token_id', $jti)->update([
            'revoked_at' => now(),
            'last_seen_at' => now(),
        ]);

        JWTAuth::invalidate($token);

        if ($user) {
            \App\Services\AuditLogger::log('auth.logout', $user, null, $user);
        }

        return $this->success(['message' => 'Sesión cerrada correctamente.']);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/refresh",
     *     tags={"Auth"},
     *     summary="Refrescar token JWT",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Nuevo token emitido",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Token inválido o expirado")
     * )
     */
    public function refresh(Request $request, JwtSessionIssuer $issuer)
    {
        $user   = $request->user();
        $oldJti = (string) $request->attributes->get('current_jti');

        if (!$user || $oldJti === '') {
            return $this->error('AUTH_UNAUTHENTICATED', 'No autenticado.', 401);
        }

        if (config('kaan.auth.require_verified_email', false) && ! $user->hasVerifiedEmail()) {
            return $this->error('AUTH_EMAIL_NOT_VERIFIED', 'Debe verificar su correo para continuar.', 403);
        }

        try {
            // Invalidate old token in JWT blacklist
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (\Throwable $e) {
            // best-effort invalidation of old token
        }

        // Revoke old session
        AuthSession::where('token_id', $oldJti)->update([
            'revoked_at' => now(),
            'last_seen_at' => now(),
        ]);

        // Issue new token + session via unified issuer
        try {
            $tokenData = $issuer->issue($user, $request);
        } catch (\Throwable $e) {
            return $this->error('AUTH_JWT_ERROR', 'No se pudo refrescar el token.', 401);
        }

        return $this->success($tokenData);
    }
}