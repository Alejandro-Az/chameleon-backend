<?php

namespace App\Services\Auth;

use App\Models\AuthSession;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtSessionIssuer
{
    /**
     * Issue a JWT token and create an auth_sessions row.
     * Auth sessions are a core capability — always created.
     *
     * @return array{token_type: string, access_token: string, expires_in: int}
     */
    public function issue(User $user, Request $request, ?int $apiKeyId = null): array
    {
        $token   = JWTAuth::fromUser($user);
        $payload = JWTAuth::setToken($token)->getPayload();
        $jti     = (string) $payload->get('jti');
        $ttl     = (int) config('jwt.ttl', 60);

        $sessionData = [
            'user_id'      => $user->id,
            'token_id'     => $jti,
            'ip'           => $request->ip(),
            'user_agent'   => $request->userAgent(),
            'last_seen_at' => now(),
            'expires_at'   => now()->addMinutes($ttl),
        ];

        if ($apiKeyId !== null) {
            $sessionData['api_key_id'] = $apiKeyId;
        }

        AuthSession::create($sessionData);

        return [
            'token_type'   => 'bearer',
            'access_token' => $token,
            'expires_in'   => $ttl * 60,
        ];
    }
}
