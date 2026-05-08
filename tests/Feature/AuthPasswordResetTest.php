<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\AuthSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Notifications\ResetPasswordLinkNotification;
use Illuminate\Support\Facades\Hash;

class AuthPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('kaan:install');
    }

    public function test_forgot_password_returns_200_for_existing_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.message', 'Si el correo existe, se enviaron instrucciones para restablecer la contraseña.')
            ->assertJsonStructure(['ok', 'data' => ['message']]);

        Notification::assertSentTo(
            [$user], ResetPasswordLinkNotification::class
        );
    }

    public function test_forgot_password_returns_200_for_non_existing_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'doesnt.exist@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.message', 'Si el correo existe, se enviaron instrucciones para restablecer la contraseña.')
            ->assertJsonStructure(['ok', 'data' => ['message']]);

        Notification::assertNothingSent();
    }

    public function test_reset_password_changes_password_and_revokes_sessions(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('oldSecret123456'),
        ]);

        // Create a fake active session
        AuthSession::create([
            'user_id' => $user->id,
            'token_id' => 'fake_jti_123',
            'ip' => '127.0.0.1',
            'user_agent' => 'Test',
            'last_seen_at' => now(),
            'expires_at' => now()->addMinutes(60),
            'revoked_at' => null,
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewSecret123456',
            'password_confirmation' => 'NewSecret123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.message', 'Contraseña restablecida correctamente. Inicia sesión nuevamente.')
            ->assertJsonStructure(['ok', 'data' => ['message']]);

        // Verify password changed
        $user->refresh();
        $this->assertTrue(Hash::check('NewSecret123456', $user->password));

        // Verify session was revoked
        $this->assertDatabaseMissing('auth_sessions', [
            'user_id' => $user->id,
            'token_id' => 'fake_jti_123',
            'revoked_at' => null,
        ]);

        // Verify token was used/deleted
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }
}
