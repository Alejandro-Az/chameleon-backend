<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LoginAttempt;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminLoginAttemptsTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): array
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'status'            => 'active',
            'password'          => 'Secret123456',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $res = $this->postJson('/api/v1/auth/login', [
            'login'    => $admin->email,
            'password' => 'Secret123456',
        ]);
        $res->assertOk();

        return [$admin, $res->json('data.access_token')];
    }

    private function seedAttempts(): void
    {
        LoginAttempt::record('john@example.com', '192.168.1.1', 'Mozilla/5.0', LoginAttempt::STATUS_SUCCESS);
        LoginAttempt::record('john@example.com', '192.168.1.1', 'Mozilla/5.0', LoginAttempt::STATUS_FAILED);
        LoginAttempt::record('jane@example.com', '10.0.0.1', 'Chrome/100', LoginAttempt::STATUS_BLOCKED, now()->addMinutes(15));
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/security/login-attempts')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonStructure(['ok', 'error' => ['code', 'message', 'details']]);
    }

    public function test_requires_admin_security_view_permission(): void
    {
        $user = User::factory()->create([
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/admin/security/login-attempts')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_admin_gets_paginated_list_with_masked_login(): void
    {
        config()->set('kaan.auth.require_verified_email', false);

        [, $token] = $this->loginAsAdmin();
        $this->seedAttempts();

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/security/login-attempts');

        $res->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['ok', 'data' => ['data', 'links', 'meta']]);

        $items = $res->json('data.data');
        // 3 seeded + 1 admin login = 4 total
        $this->assertCount(4, $items);

        // Ordered by created_at desc → last attempt first
        // All should have login_masked, and login_raw should be null by default
        foreach ($items as $item) {
            $this->assertArrayHasKey('login_masked', $item);
            $this->assertNull($item['login_raw']);
            $this->assertStringContainsString('***', $item['login_masked']);
        }
    }

    public function test_filter_by_status(): void
    {
        config()->set('kaan.auth.require_verified_email', false);

        [, $token] = $this->loginAsAdmin();
        $this->seedAttempts();

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/security/login-attempts?status=blocked');

        $res->assertOk();
        $items = $res->json('data.data');
        $this->assertCount(1, $items);
        $this->assertSame('blocked', $items[0]['status']);
    }

    public function test_filter_by_ip(): void
    {
        config()->set('kaan.auth.require_verified_email', false);

        [, $token] = $this->loginAsAdmin();
        $this->seedAttempts();

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/security/login-attempts?ip=10.0.0.1');

        $res->assertOk();
        $items = $res->json('data.data');
        $this->assertCount(1, $items);
        $this->assertSame('10.0.0.1', $items[0]['ip_address']);
    }

    public function test_login_raw_exposed_when_config_enabled(): void
    {
        config()->set('kaan.auth.require_verified_email', false);
        config()->set('kaan.security.login_attempts.expose_raw_identifier_to_admin', true);

        [, $token] = $this->loginAsAdmin();
        $this->seedAttempts();

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/security/login-attempts');

        $res->assertOk();
        $items = $res->json('data.data');

        foreach ($items as $item) {
            $this->assertNotNull($item['login_raw']);
        }
    }
}
