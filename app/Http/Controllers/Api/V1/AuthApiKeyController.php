<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\User;
use App\Services\Auth\JwtSessionIssuer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

final class AuthApiKeyController extends Controller
{
    use \App\Traits\HasApiResponse;

    private const TOUCH_THROTTLE_SECONDS = 60;

    public function exchange(Request $request, JwtSessionIssuer $issuer)
    {
        // First, check the pre-validation global rate limit
        if ($this->isPrevalidationRateLimited($request)) {
            $seconds = RateLimiter::availableIn($this->getPrevalidationThrottleKey($request));
            \App\Services\AuditLogger::log('api_key.exchange.failed', null, [
                'reason' => 'rate_limited_prevalidation',
            ]);
            return $this->buildRateLimitResponse($seconds);
        }

        $secret = $request->header('X-API-Key');

        if (!is_string($secret) || trim($secret) === '') {
            $this->hitPrevalidationLimiter($request);

            \App\Services\AuditLogger::log('api_key.exchange.failed', null, [
                'reason' => 'missing',
            ]);

            return $this->error('AUTH_API_KEY_MISSING', 'Falta API key en la solicitud.', 401);
        }

        $secret = trim($secret);

        // Defensa simple contra headers absurdos (sin filtrar info)
        if (strlen($secret) > 4096) {
            $this->hitPrevalidationLimiter($request);

            \App\Services\AuditLogger::log('api_key.exchange.failed', null, [
                'reason' => 'invalid_length',
            ]);

            return $this->error('AUTH_API_KEY_INVALID', 'API key desconocida/incorrecta.', 401);
        }

        // Rate limit per IP + key hash (sin almacenar secreto)
        $ip = $request->ip();
        $hash = hash('sha256', $secret);
        $hashHmac = hash_hmac('sha256', $hash, (string) config('app.key'));
        $throttleKey = "api-key-exchange:{$ip}|{$hashHmac}";

        $maxAttempts = (int) config('kaan.api_keys.exchange.attempts', 10);
        $decaySeconds = (int) config('kaan.api_keys.exchange.decay_seconds', 60);

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            \App\Services\AuditLogger::log('api_key.exchange.failed', null, [
                'reason' => 'rate_limited',
            ]);

            return $this->buildRateLimitResponse($seconds);
        }

        RateLimiter::hit($throttleKey, $decaySeconds);

        /** @var ApiKey|null $apiKey */
        $apiKey = ApiKey::query()
            ->where('key_hash', $hash)
            ->with('owner')
            ->first();

        if (!$apiKey) {
            \App\Services\AuditLogger::log('api_key.exchange.failed', null, [
                'reason' => 'invalid',
            ]);

            return $this->error('AUTH_API_KEY_INVALID', 'API key desconocida/incorrecta.', 401);
        }

        $verbose = (bool) config('kaan.api_keys.exchange.verbose_errors', false);

        if ($apiKey->revoked_at !== null) {
            \App\Services\AuditLogger::log('api_key.exchange.failed', $apiKey, [
                'reason' => 'revoked',
                'api_key_id' => $apiKey->public_id,
                'prefix' => $apiKey->prefix,
            ]);

            return $this->error(
                $verbose ? 'AUTH_API_KEY_REVOKED' : 'AUTH_API_KEY_INVALID',
                $verbose ? 'API key revocada/blacklist.' : 'API key desconocida/incorrecta.',
                401
            );
        }

        if ($apiKey->expires_at !== null && now()->greaterThanOrEqualTo($apiKey->expires_at)) {
            \App\Services\AuditLogger::log('api_key.exchange.failed', $apiKey, [
                'reason' => 'expired',
                'api_key_id' => $apiKey->public_id,
                'prefix' => $apiKey->prefix,
            ]);

            return $this->error(
                $verbose ? 'AUTH_API_KEY_EXPIRED' : 'AUTH_API_KEY_INVALID',
                $verbose ? 'API key expirada.' : 'API key desconocida/incorrecta.',
                401
            );
        }

        /** @var User|null $user */
        $user = $apiKey->owner;

        // Fail-safe: la key debe pertenecer a service account
        if (!$user || $user->type !== 'service') {
            \App\Services\AuditLogger::log('api_key.exchange.failed', $apiKey, [
                'reason' => 'owner_invalid',
                'api_key_id' => $apiKey->public_id,
                'prefix' => $apiKey->prefix,
            ]);

            return $this->error('AUTH_API_KEY_INVALID', 'API key desconocida/incorrecta.', 401);
        }

        if ($user->status !== 'active') {
            \App\Services\AuditLogger::log('api_key.exchange.failed', $apiKey, [
                'reason' => 'user_inactive',
                'service_account' => $user->public_id,
                'api_key_id' => $apiKey->public_id,
            ], $user);

            return $this->error('AUTH_USER_INACTIVE', 'Usuario no activo.', 403);
        }

        if (config('kaan.auth.require_verified_email', false) && ! $user->hasVerifiedEmail()) {
            return $this->error('AUTH_EMAIL_NOT_VERIFIED', 'Debe verificar su correo para continuar.', 403);
        }

        // Success: limpiar rate limiter para ese par (IP+key) y el pre-validador global de IP
        RateLimiter::clear($throttleKey);
        RateLimiter::clear($this->getPrevalidationThrottleKey($request));

        // Touch last_used_at con throttle (atómico)
        try {
            ApiKey::where('id', $apiKey->id)
                ->whereNull('revoked_at')
                ->where(function ($q) {
                    $q->whereNull('last_used_at')
                      ->orWhere('last_used_at', '<=', now()->subSeconds(self::TOUCH_THROTTLE_SECONDS));
                })
                ->update(['last_used_at' => now()]);
        } catch (\Throwable $e) {
            // best-effort
        }

        // Unified JWT + session issuance (with api_key_id linkage)
        $tokenData = $issuer->issue($user, $request, $apiKey->id);

        $user->forceFill(['last_login_at' => now()])->save();

        \App\Services\AuditLogger::log('api_key.exchange.success', $apiKey, [
            'service_account' => $user->public_id,
            'api_key_id' => $apiKey->public_id,
            'prefix' => $apiKey->prefix,
        ], $user);

        return $this->success(array_merge($tokenData, [
            'user' => [
                'id' => $user->public_id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
            ],
        ]));
    }

    private function getPrevalidationThrottleKey(Request $request): string
    {
        return 'api-key-exchange-ip:' . $request->ip();
    }

    private function isPrevalidationRateLimited(Request $request): bool
    {
        $maxAttempts = (int) config('kaan.api_keys.exchange.attempts', 10);
        return RateLimiter::tooManyAttempts($this->getPrevalidationThrottleKey($request), $maxAttempts);
    }

    private function hitPrevalidationLimiter(Request $request): void
    {
        if ($this->isPrevalidationRateLimited($request)) {
            return;
        }

        $decaySeconds = (int) config('kaan.api_keys.exchange.decay_seconds', 60);
        RateLimiter::hit($this->getPrevalidationThrottleKey($request), $decaySeconds);
    }

    private function buildRateLimitResponse(int $seconds)
    {
        return $this->error(
            'AUTH_API_KEY_TOO_MANY_ATTEMPTS',
            "Demasiados intentos. Intente nuevamente en {$seconds} segundos.",
            429
        )->withHeaders(['Retry-After' => $seconds]);
    }
}
