<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthBlockingTest extends TestCase
{
    use RefreshDatabase;

    private function throttleKey(string $login): string
    {
        $norm = strtolower(trim($login));
        $hmac = hash_hmac('sha256', $norm, config('app.key'));
        return "auth-login:127.0.0.1|{$hmac}";
    }

    private function attemptLogin(string $login, string $password = 'wrong'): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/auth/login', [
            'login' => $login,
            'password' => $password,
        ]);
    }

    public function test_account_is_blocked_after_5_failures(): void
    {
        $user = User::factory()->create([
            'email' => 'victim@test.com',
            'password' => 'correct123',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Clear RateLimiter so we only test DB-backed blocking
        RateLimiter::clear($this->throttleKey('victim@test.com'));

        // 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::clear($this->throttleKey('victim@test.com'));
            $this->attemptLogin('victim@test.com', 'wrong')->assertUnauthorized();
        }

        // 6th attempt should be blocked (even with correct password)
        RateLimiter::clear($this->throttleKey('victim@test.com'));
        $response = $this->attemptLogin('victim@test.com', 'correct123');
        $response->assertStatus(429);
        $response->assertJsonPath('error.code', 'AUTH_ACCOUNT_LOCKED');

        // Verify DB records
        $this->assertDatabaseHas('login_attempts', [
            'email' => 'victim@test.com',
            'status' => LoginAttempt::STATUS_BLOCKED,
        ]);
    }

    public function test_blocking_is_per_email_not_per_ip(): void
    {
        User::factory()->create([
            'email' => 'victim@test.com',
            'password' => 'correct123',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        User::factory()->create([
            'email' => 'innocent@test.com',
            'password' => 'correct123',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // 5 failed attempts on victim
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::clear($this->throttleKey('victim@test.com'));
            $this->attemptLogin('victim@test.com', 'wrong')->assertUnauthorized();
        }

        // Innocent user should still be able to login
        RateLimiter::clear($this->throttleKey('innocent@test.com'));
        $this->attemptLogin('innocent@test.com', 'correct123')->assertOk();
    }

    public function test_successful_login_records_attempt(): void
    {
        $user = User::factory()->create([
            'email' => 'user@test.com',
            'password' => 'Secret123456',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->attemptLogin('user@test.com', 'Secret123456')->assertOk();

        $this->assertDatabaseHas('login_attempts', [
            'email' => 'user@test.com',
            'status' => LoginAttempt::STATUS_SUCCESS,
        ]);
    }
}
