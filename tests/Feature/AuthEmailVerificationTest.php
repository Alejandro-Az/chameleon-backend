<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\VerifyEmailLinkNotification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\AuditLog;

class AuthEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('kaan.frontend.verify_email_url', 'https://frontend.test/verify');
    }

    public function test_resend_endpoint_requires_auth()
    {
        $response = $this->postJson('/api/v1/auth/email/verification-notification');
        $response->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonStructure(['ok', 'error' => ['code', 'message', 'details']]);
    }

    public function test_resend_notification_successfully()
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'test_resend@example.com',
            'password' => bcrypt('Secret123456'),
            'email_verified_at' => null
        ]);

        Config::set('kaan.auth.require_verified_email', false);
        $loginRes = $this->postJson('/api/v1/auth/login', ['login' => 'test_resend@example.com', 'password' => 'Secret123456']);
        $token = $loginRes->json('data.access_token');

        $response = $this->withToken($token)->postJson('/api/v1/auth/email/verification-notification');
        
        $response->assertStatus(200)
                 ->assertJsonPath('data.message', 'Verification link sent.');

        Notification::assertSentTo($user, VerifyEmailLinkNotification::class);

        $auditLog = AuditLog::where('action', 'auth.email_verification_sent')->first();
        $this->assertNotNull($auditLog);
        $this->assertArrayNotHasKey('email', $auditLog->details);
        $this->assertArrayHasKey('user_public_id', $auditLog->details);
        $this->assertEquals('resend', $auditLog->details['via']);
    }

    public function test_resend_throttles_after_3_attempts()
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'test_throttle@example.com',
            'password' => bcrypt('Secret123456'),
            'email_verified_at' => null
        ]);

        Config::set('kaan.auth.require_verified_email', false);
        $loginRes = $this->postJson('/api/v1/auth/login', ['login' => 'test_throttle@example.com', 'password' => 'Secret123456']);
        $token = $loginRes->json('data.access_token');

        // 3 successful hits
        for ($i = 0; $i < 3; $i++) {
            $this->withToken($token)->postJson('/api/v1/auth/email/verification-notification')->assertStatus(200);
        }

        // 4th hit should be throttled
        $response = $this->withToken($token)->postJson('/api/v1/auth/email/verification-notification');
        
        $response->assertStatus(429)
                 ->assertHeader('Retry-After')
                 ->assertJsonPath('error.code', 'AUTH_TOO_MANY_REQUESTS')
                 ->assertJsonPath('error.details', null);
    }

    public function test_verify_endpoint_redirects_on_success()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        
        $url = URL::temporarySignedRoute(
            'auth.verify-email',
            now()->addMinutes(60),
            ['id' => $user->public_id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->get($url); // Browser simulated click without Accept json
        
        $response->assertRedirect('https://frontend.test/verify?verified=1');
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_verify_endpoint_returns_json_on_success()
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $url = URL::temporarySignedRoute(
            'auth.verify-email',
            now()->addMinutes(60),
            ['id' => $user->public_id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->getJson($url); // API call

        $response->assertStatus(200)
                 ->assertJsonPath('ok', true)
                 ->assertJsonPath('data.verified', true)
                 ->assertJsonStructure(['ok', 'data' => ['verified']]);
        
        $this->assertNotNull($user->fresh()->email_verified_at);

        $auditLog = AuditLog::where('action', 'auth.email_verified')->first();
        $this->assertNotNull($auditLog);
        $this->assertArrayNotHasKey('email', $auditLog->details);
        $this->assertArrayHasKey('user_public_id', $auditLog->details);
        $this->assertEquals('verified', $auditLog->details['mode']);
    }

    public function test_verify_fails_with_invalid_signature()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        
        $url = URL::temporarySignedRoute(
            'auth.verify-email',
            now()->addMinutes(60),
            ['id' => $user->public_id, 'hash' => sha1($user->getEmailForVerification())]
        ) . 'invalid'; // tamper url

        $response = $this->get($url);
        
        $response->assertRedirect('https://frontend.test/verify?verified=0&reason=invalid_signature');
    }

    public function test_login_fails_if_email_not_verified_when_required()
    {
        Config::set('kaan.auth.require_verified_email', true);
        
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Secret123456'),
            'email_verified_at' => null
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'test@example.com',
            'password' => 'Secret123456',
        ]);

        $response->assertStatus(403)
                 ->assertJsonPath('error.code', 'AUTH_EMAIL_NOT_VERIFIED');
    }

    public function test_login_succeeds_if_email_not_verified_when_optional()
    {
        Config::set('kaan.auth.require_verified_email', false); // default
        
        $user = User::factory()->create([
            'email' => 'test2@example.com',
            'password' => bcrypt('Secret123456'),
            'email_verified_at' => null
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'test2@example.com',
            'password' => 'Secret123456',
        ]);

        $response->assertStatus(200)->assertJsonStructure(['data' => ['access_token']]);
    }

    public function test_existing_token_is_blocked_after_flipping_require_verified_config()
    {
        Config::set('kaan.auth.require_verified_email', false);

        $user = User::factory()->create([
            'email' => 'test3@example.com',
            'password' => bcrypt('Secret123456'),
            'email_verified_at' => null
        ]);

        $loginRes = $this->postJson('/api/v1/auth/login', [
            'login' => 'test3@example.com',
            'password' => 'Secret123456',
        ]);
        
        $token = $loginRes->json('data.access_token');
        
        // El usuario obtiene su token y en este momento todo está bien.
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(200);

        // CONFIG FLIP: En producción activamos el flag de verificación.
        Config::set('kaan.auth.require_verified_email', true);

        // El usuario malicioso intenta usar su token validado anteriormente.
        // EXPECT: Bloqueo quirúrgico de la sesión
        $response = $this->withToken($token)->getJson('/api/v1/auth/me');
        $response->assertStatus(403)
                 ->assertJsonPath('error.code', 'AUTH_EMAIL_NOT_VERIFIED');
    }

    public function test_status_endpoint()
    {
        $user = User::factory()->create([
            'email' => 'test_status@example.com',
            'password' => bcrypt('Secret123456'),
            'email_verified_at' => null
        ]);

        Config::set('kaan.auth.require_verified_email', false);
        $loginRes = $this->postJson('/api/v1/auth/login', ['login' => 'test_status@example.com', 'password' => 'Secret123456']);
        $token = $loginRes->json('data.access_token');

        $response = $this->withToken($token)->getJson('/api/v1/auth/email/verification-status');
        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.verified', false)
            ->assertJsonStructure(['ok', 'data' => ['verified']]);

        $saved = $user->markEmailAsVerified();
        $this->assertTrue($saved, "markEmailAsVerified should return true");
        $this->assertNotNull($user->fresh()->email_verified_at, "email_verified_at should be saved in DB");

        auth('api')->forgetUser();

        $response = $this->withToken($token)->getJson('/api/v1/auth/email/verification-status');
        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.verified', true)
            ->assertJsonStructure(['ok', 'data' => ['verified']]);
    }
    public function test_verify_is_idempotent()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        
        $url = URL::temporarySignedRoute(
            'auth.verify-email',
            now()->addMinutes(60),
            ['id' => $user->public_id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response1 = $this->getJson($url);
        $response1->assertStatus(200);

        // Second hit should still return ok=true but not re-log
        $response2 = $this->getJson($url);
        $response2->assertStatus(200)->assertJsonPath('data.message', 'Already verified');
    }

    public function test_verify_fails_gracefully_when_config_is_missing()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        
        Config::set('kaan.frontend.verify_email_url', ''); // Remove config
        
        $url = URL::temporarySignedRoute(
            'auth.verify-email',
            now()->addMinutes(60),
            ['id' => $user->public_id, 'hash' => sha1($user->getEmailForVerification())]
        );

        // API hit -> 500 CONFIG_VERIFY_EMAIL_URL_MISSING
        $responseJson = $this->getJson($url);
        $responseJson->assertStatus(500)
                     ->assertJsonPath('error.code', 'CONFIG_VERIFY_EMAIL_URL_MISSING');

        // Browser hit -> redirect to app.url logic
        $responseBrowser = $this->get($url);
        $responseBrowser->assertRedirectContains('?error=verification_config_missing');
    }
}
