<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuthSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthSessionsTest extends TestCase
{
    use RefreshDatabase;

    private function createSessionForUser($user)
    {
        $token = JWTAuth::claims(['rand' => uniqid()])->fromUser($user);
        JWTAuth::setToken($token);
        $payload = JWTAuth::getPayload();
        $jti = $payload->get('jti');

        AuthSession::create([
            'user_id' => $user->id,
            'token_id' => $jti,
            'ip' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'last_seen_at' => now(),
            'expires_at' => now()->addMinutes(60),
        ]);

        return $token;
    }

    public function test_get_sessions_without_token_returns_401()
    {
        $response = $this->getJson('/api/v1/auth/sessions');
        $response->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonStructure(['ok', 'error' => ['code', 'message', 'details']]);
    }

    public function test_get_sessions_with_token_returns_200_and_current_session()
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $token = $this->createSessionForUser($user);

        $response = $this->withToken($token)->getJson('/api/v1/auth/sessions');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data.data')));

        $currentSession = collect($response->json('data.data'))->firstWhere('is_current', true);
        $this->assertNotNull($currentSession);
    }

    public function test_delete_own_session_revokes_it()
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $token = $this->createSessionForUser($user);

        $session = AuthSession::where('user_id', $user->id)->first();

        $response = $this->withToken($token)->deleteJson("/api/v1/auth/sessions/{$session->public_id}");
        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.revoked', true)
            ->assertJsonPath('data.was_already_revoked', false);

        $this->assertNotNull($session->fresh()->revoked_at);
    }

    public function test_delete_already_revoked_session_returns_200_with_flag()
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        
        $token1 = $this->createSessionForUser($user); // Active session
        $token2 = $this->createSessionForUser($user); // Session to be revoked

        $session2 = AuthSession::where('token_id', JWTAuth::setToken($token2)->getPayload()->get('jti'))->first();
        $session2->update(['revoked_at' => now()]); // Revoke it manually first

        // Use token1 (active) to try and revoke session2 (already revoked)
        $response = $this->withToken($token1)->deleteJson("/api/v1/auth/sessions/{$session2->public_id}");
        
        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.revoked', true)
            ->assertJsonPath('data.was_already_revoked', true);
    }

    public function test_cannot_revoke_other_user_session()
    {
        $user1 = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $user2 = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        
        $token1 = $this->createSessionForUser($user1);
        $token2 = $this->createSessionForUser($user2);

        $session2 = AuthSession::where('user_id', $user2->id)->first();

        // User 1 tries to delete User 2's session
        $response = $this->withToken($token1)->deleteJson("/api/v1/auth/sessions/{$session2->public_id}");
        
        // Scope should prevent this, throwing 404
        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'NOT_FOUND');
        $this->assertNull($session2->fresh()->revoked_at);
    }

    public function test_revoke_others_leaves_only_current_session_active()
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        
        // Setup two different tokens (different sessions)
        $token1 = $this->createSessionForUser($user);
        $token2 = $this->createSessionForUser($user);

        $response = $this->withToken($token2)->postJson('/api/v1/auth/sessions/revoke-others');
        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.revoked_count', 1);
        // Token 1 session should be revoked, Token 2 session should be active
        $sessionsResponse = $this->withToken($token2)->getJson('/api/v1/auth/sessions');
        $sessionsResponse->assertStatus(200);
        $data = $sessionsResponse->json('data.data');

        $this->assertCount(1, collect($data)->where('is_current', true));
        $this->assertCount(1, $data); // Default index doesn't include revoked
    }

    public function test_revoke_all_revokes_current_and_invalidates_token()
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $token = $this->createSessionForUser($user);

        $response = $this->withToken($token)->postJson('/api/v1/auth/sessions/revoke-all');
        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.revoked_count', 1);

        $subsequentResponse = $this->withToken($token)->getJson('/api/v1/auth/me');
        $subsequentResponse->assertStatus(401);
    }

    public function test_numeric_session_id_is_not_accepted_anymore()
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $token = $this->createSessionForUser($user);

        $session = AuthSession::where('user_id', $user->id)->firstOrFail();

        $response = $this->withToken($token)->deleteJson("/api/v1/auth/sessions/{$session->id}");

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }
}
