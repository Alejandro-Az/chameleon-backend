<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Role;
use Tests\TestCase;

final class AuthRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure base role exists (independent of seeders)
        Role::findOrCreate('user', 'api');

        // Clear rate limit keys to avoid contamination between tests
        RateLimiter::clear('auth-register:ip:127.0.0.1');
    }

    // ---------------------------------------------------------------
    // 1) Feature flag OFF → 403
    // ---------------------------------------------------------------
    public function test_registration_is_disabled_by_default(): void
    {
        config()->set('kaan.auth.allow_public_registration', false);

        $res = $this->postJson('/api/v1/auth/register', [
            'email'                 => 'new.user@example.com',
            'password'              => 'TestPassword1',
            'password_confirmation' => 'TestPassword1',
            'name'                  => 'New User',
        ]);

        $res->assertStatus(403)
            ->assertJson([
                'ok'    => false,
                'error' => [
                    'code' => 'REGISTRATION_DISABLED',
                ],
            ]);
    }

    // ---------------------------------------------------------------
    // 2) Happy path: register + token + session + role
    // ---------------------------------------------------------------
    public function test_register_ok_issues_token_and_creates_session(): void
    {
        config()->set('kaan.auth.allow_public_registration', true);
        config()->set('kaan.auth.registration_default_status', 'active');
        config()->set('kaan.auth.register_issue_token', true);
        config()->set('kaan.auth.default_role', 'user');
        config()->set('kaan.auth.require_verified_email', false);

        $res = $this->postJson('/api/v1/auth/register', [
            'email'                 => 'new.user@example.com',
            'password'              => 'TestPassword1',
            'password_confirmation' => 'TestPassword1',
            'name'                  => 'New User',
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.user.email', 'new.user@example.com')
            ->assertJsonPath('data.user.status', 'active')
            ->assertJsonStructure([
                'ok',
                'data' => [
                    'user' => ['id', 'name', 'email', 'status'],
                    'requires_email_verification',
                    'token_type',
                    'access_token',
                    'expires_in',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email'  => 'new.user@example.com',
            'status' => 'active',
        ]);

        // auth_sessions row created (core capability — always active)
        $this->assertDatabaseCount('auth_sessions', 1);

        /** @var User $user */
        $user = User::where('email', 'new.user@example.com')->firstOrFail();

        // RBAC is a core capability — role always assigned
        $this->assertTrue($user->hasRole('user'));
    }

    // ---------------------------------------------------------------
    // 3) require_verified_email → sends notification
    // ---------------------------------------------------------------
    public function test_require_verified_email_sends_notification(): void
    {
        Notification::fake();

        config()->set('kaan.auth.allow_public_registration', true);
        config()->set('kaan.auth.require_verified_email', true);
        config()->set('kaan.auth.registration_default_status', 'active');
        config()->set('kaan.auth.register_issue_token', true);

        $res = $this->postJson('/api/v1/auth/register', [
            'email'                 => 'verify.me@example.com',
            'password'              => 'TestPassword1',
            'password_confirmation' => 'TestPassword1',
            'name'                  => 'Verify Me',
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.requires_email_verification', true);

        $user = User::where('email', 'verify.me@example.com')->firstOrFail();

        Notification::assertSentTo(
            $user,
            \App\Notifications\VerifyEmailLinkNotification::class
        );
    }

    // ---------------------------------------------------------------
    // 4) Rate limit by IP → 429
    // ---------------------------------------------------------------
    public function test_rate_limit_by_ip_returns_429_with_envelope(): void
    {
        config()->set('kaan.auth.allow_public_registration', true);

        $payload = [
            'email'                 => 'spam.ip@example.com',
            'password'              => 'TestPassword1',
            'password_confirmation' => 'TestPassword1',
            'name'                  => 'Spam',
        ];

        // IP limit = 5/min, fire 6 attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/register', $payload);
        }

        $res = $this->postJson('/api/v1/auth/register', $payload);

        $res->assertStatus(429)
            ->assertJson([
                'ok'    => false,
                'error' => [
                    'code' => 'AUTH_TOO_MANY_REQUESTS',
                ],
            ]);
    }

    // ---------------------------------------------------------------
    // 5) Duplicate email → 422
    // ---------------------------------------------------------------
    public function test_duplicate_email_returns_422_validation_error(): void
    {
        config()->set('kaan.auth.allow_public_registration', true);

        User::factory()->create([
            'email'  => 'dup@example.com',
            'status' => 'active',
        ]);

        $res = $this->postJson('/api/v1/auth/register', [
            'email'                 => 'dup@example.com',
            'password'              => 'TestPassword1',
            'password_confirmation' => 'TestPassword1',
            'name'                  => 'Dup',
        ]);

        $res->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    // ---------------------------------------------------------------
    // 6) Profile persists in user_profiles.meta (allowlist enforced)
    // ---------------------------------------------------------------
    public function test_profile_payload_persists_in_user_profiles(): void
    {
        config()->set('kaan.auth.allow_public_registration', true);

        $res = $this->postJson('/api/v1/auth/register', [
            'email'                 => 'profile.user@example.com',
            'password'              => 'TestPassword1',
            'password_confirmation' => 'TestPassword1',
            'name'                  => 'Profile User',
            'profile' => [
                'phone'   => '+52 222 123 4567',
                'company' => 'Kaan Forge Solutions',
                'role'    => 'admin', // must be ignored by allowlist
            ],
        ]);

        $res->assertStatus(201);

        $user = User::where('email', 'profile.user@example.com')->firstOrFail();

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
        ]);

        $profile = $user->profile;
        $this->assertNotNull($profile);
        $this->assertSame('+52 222 123 4567', $profile->meta['phone'] ?? null);
        $this->assertSame('Kaan Forge Solutions', $profile->meta['company'] ?? null);

        // Security: keys outside allowlist must NOT be persisted
        $this->assertArrayNotHasKey('role', $profile->meta ?? []);
    }

    // ---------------------------------------------------------------
    // 7) Audit auth.registered created without PII
    // ---------------------------------------------------------------
    public function test_audit_log_created_without_pii(): void
    {
        config()->set('kaan.auth.allow_public_registration', true);
        config()->set('kaan.features.audit', true);

        $res = $this->postJson('/api/v1/auth/register', [
            'email'                 => 'audit.user@example.com',
            'password'              => 'TestPassword1',
            'password_confirmation' => 'TestPassword1',
            'name'                  => 'Audit User',
        ]);

        $res->assertStatus(201);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.registered',
        ]);

        $log = \App\Models\AuditLog::where('action', 'auth.registered')->first();
        $this->assertNotNull($log);

        $details = $log->details;
        $this->assertArrayHasKey('user_public_id', $details);
        $this->assertArrayHasKey('source', $details);
        $this->assertSame('public_registration', $details['source']);

        // Must NOT contain PII
        $this->assertArrayNotHasKey('email', $details);
        $this->assertArrayNotHasKey('password', $details);
        $this->assertArrayNotHasKey('name', $details);
    }
}
