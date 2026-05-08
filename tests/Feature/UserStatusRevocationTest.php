<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\AuthSession;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

final class UserStatusRevocationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Issue a JWT + create an auth_sessions row, without hitting /login.
     */
    private function issueTokenWithSession(User $user): string
    {
        $token   = JWTAuth::fromUser($user);
        $payload = JWTAuth::setToken($token)->getPayload();
        $jti     = (string) $payload->get('jti');
        $ttl     = (int) config('jwt.ttl', 60);

        AuthSession::create([
            'user_id'      => $user->id,
            'token_id'     => $jti,
            'ip'           => '127.0.0.1',
            'user_agent'   => 'PHPUnit',
            'last_seen_at' => now(),
            'expires_at'   => now()->addMinutes($ttl),
        ]);

        JWTAuth::unsetToken();

        return $token;
    }

    private function makeAdmin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'status'            => 'active',
            'password'          => 'Secret123456',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        return $admin;
    }

    // ---------------------------------------------------------------
    // 1) Suspend → reactivate → old token fails with 401
    //    (proves revocation is real, not just user.active middleware)
    //
    //    Uses actingAs($admin) for admin calls to avoid tymon/jwt-auth
    //    guard singleton contamination between different-user tokens.
    // ---------------------------------------------------------------
    public function test_status_change_revokes_sessions_and_old_token_fails_after_reactivation(): void
    {
        config()->set('kaan.auth.require_verified_email', false);

        $admin = $this->makeAdmin();

        $user = User::factory()->create([
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);
        $userToken = $this->issueTokenWithSession($user);

        // Verify user session exists and is active
        $this->assertDatabaseHas('auth_sessions', [
            'user_id'    => $user->id,
            'revoked_at' => null,
        ]);

        // Suspend user via admin (actingAs to avoid JWT guard collision)
        $this->actingAs($admin, 'api')
            ->putJson("/api/v1/admin/users/{$user->public_id}", [
                'status' => 'suspended',
            ])
            ->assertOk();

        // All user sessions should be revoked
        $this->assertDatabaseMissing('auth_sessions', [
            'user_id'    => $user->id,
            'revoked_at' => null,
        ]);

        // suspended_at should be set
        $user->refresh();
        $this->assertNotNull($user->suspended_at);

        // Reactivate user so user.active middleware passes
        $this->actingAs($admin, 'api')
            ->putJson("/api/v1/admin/users/{$user->public_id}", [
                'status' => 'active',
            ])
            ->assertOk();

        // suspended_at should be cleared
        $user->refresh();
        $this->assertNull($user->suspended_at);

        // Old token must STILL fail — session was revoked
        // Reset guard so the test uses the JWT token, not actingAs
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$userToken}")
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_TOKEN_REVOKED');
    }

    // ---------------------------------------------------------------
    // 2) Audit log: user.status_changed without PII
    // ---------------------------------------------------------------
    public function test_status_change_creates_audit_without_pii(): void
    {
        config()->set('kaan.features.audit', true);
        config()->set('kaan.auth.require_verified_email', false);

        $admin = $this->makeAdmin();

        $user = User::factory()->create([
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin, 'api')
            ->putJson("/api/v1/admin/users/{$user->public_id}", [
                'status' => 'suspended',
            ])
            ->assertOk();

        $log = AuditLog::where('action', 'user.status_changed')
            ->where('model_type', User::class)
            ->where('model_id', $user->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('active', $log->details['from'] ?? null);
        $this->assertSame('suspended', $log->details['to'] ?? null);
        $this->assertArrayHasKey('sessions_revoked', $log->details ?? []);

        // Must NOT contain PII
        $this->assertArrayNotHasKey('email', $log->details ?? []);
        $this->assertArrayNotHasKey('name', $log->details ?? []);
        $this->assertArrayNotHasKey('password', $log->details ?? []);
    }

    // ---------------------------------------------------------------
    // 3) Inactive user blocked by middleware (without revocation)
    //    Proves user.active middleware works independently
    // ---------------------------------------------------------------
    public function test_inactive_user_is_blocked_by_middleware_without_revoke(): void
    {
        $user = User::factory()->create([
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);
        $token = $this->issueTokenWithSession($user);

        // Bypass the controller (raw DB update, no revocation)
        User::query()->where('id', $user->id)->update(['status' => 'suspended']);

        // user.active middleware should block with 403
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'AUTH_USER_INACTIVE');
    }
}
