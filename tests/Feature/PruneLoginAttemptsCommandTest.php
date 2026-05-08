<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LoginAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PruneLoginAttemptsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_prunes_old_login_attempts(): void
    {
        // Create old attempts (40 days ago)
        LoginAttempt::create([
            'email'      => 'old@example.com',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'status'     => LoginAttempt::STATUS_FAILED,
            'created_at' => now()->subDays(40),
        ]);

        // Create recent attempt (2 days ago)
        LoginAttempt::create([
            'email'      => 'recent@example.com',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'status'     => LoginAttempt::STATUS_SUCCESS,
            'created_at' => now()->subDays(2),
        ]);

        $this->assertSame(2, LoginAttempt::count());

        $this->artisan('kaan:security:prune-login-attempts', ['--days' => 30])
            ->assertSuccessful();

        $this->assertSame(1, LoginAttempt::count());
        $this->assertDatabaseHas('login_attempts', ['email' => 'recent@example.com']);
        $this->assertDatabaseMissing('login_attempts', ['email' => 'old@example.com']);
    }

    public function test_dry_run_does_not_delete(): void
    {
        LoginAttempt::create([
            'email'      => 'old@example.com',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'status'     => LoginAttempt::STATUS_FAILED,
            'created_at' => now()->subDays(40),
        ]);

        $this->artisan('kaan:security:prune-login-attempts', ['--days' => 30, '--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame(1, LoginAttempt::count());
    }
}
