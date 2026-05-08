<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Override actingAs to automatically inject a real JWT Bearer token
     * ensuring tests behave exactly like real API clients.
     */
    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        if ($guard === 'api') {
            $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
            $payload = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->getPayload();
            
            \App\Models\AuthSession::create([
                'user_id' => $user->id,
                'token_id' => $payload->get('jti'),
                'ip' => '127.0.0.1',
                'user_agent' => 'Testing/1.0',
                'last_seen_at' => now(),
                'expires_at' => now()->addMinutes(config('jwt.ttl')),
            ]);

            return $this->withToken($token);
        }

        return parent::actingAs($user, $guard);
    }
}
