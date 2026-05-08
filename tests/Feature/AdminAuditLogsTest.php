<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditLogsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('kaan:install');
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/admin/audit-logs');
        $response->assertStatus(401);
    }

    public function test_requires_authorization_permission(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $response = $this->actingAs($user, 'api')->getJson('/api/v1/admin/audit-logs');
        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_admin_can_view_paginated_logs(): void
    {
        $admin = clone current(User::role('admin')->get()->all());
        $admin->email_verified_at = now();
        $admin->save();

        AuditLog::factory()->count(20)->create();

        $response = $this->actingAs($admin, 'api')->getJson('/api/v1/admin/audit-logs?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'ok',
                'data' => [
                    'data' => [
                        '*' => ['id', 'action', 'user', 'details', 'created_at']
                    ],
                    'links',
                    'meta'
                ]
            ]);

        $this->assertCount(10, $response->json('data.data'));
    }

    public function test_filters_work_correctly(): void
    {
        $admin = clone current(User::role('admin')->get()->all());
        $admin->email_verified_at = now();
        $admin->save();
        $user = User::factory()->create(['email_verified_at' => now()]);

        AuditLog::factory()->create(['action' => 'auth.login', 'user_id' => $user->id]);
        AuditLog::factory()->create(['action' => 'user.updated', 'user_id' => $user->id]);
        AuditLog::factory()->create(['action' => 'auth.login']);

        // Filter by user ULID
        $response = $this->actingAs($admin, 'api')->getJson("/api/v1/admin/audit-logs?user={$user->public_id}");
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.data'));

        // Filter by Action
        $response2 = $this->actingAs($admin, 'api')->getJson("/api/v1/admin/audit-logs?action=auth.login");
        $response2->assertStatus(200);
        $this->assertCount(2, $response2->json('data.data'));

        // Combined Filter
        $response3 = $this->actingAs($admin, 'api')->getJson("/api/v1/admin/audit-logs?user={$user->public_id}&action=auth.login");
        $response3->assertStatus(200);
        $this->assertCount(1, $response3->json('data.data'));
    }

    public function test_details_sanitization_redacts_sensitive_keys(): void
    {
        $admin = clone current(User::role('admin')->get()->all());
        $admin->email_verified_at = now();
        $admin->save();

        AuditLog::factory()->create([
            'action' => 'test.sanitization',
            'details' => [
                'password' => 'supersecret',
                'token' => 'abc123token',
                'public_data' => 'visible',
                'nested' => [
                    'secret_key' => 'shhhhh'
                ]
            ]
        ]);

        $response = $this->actingAs($admin, 'api')->getJson("/api/v1/admin/audit-logs?action=test.sanitization");
        
        $response->assertStatus(200);
        $details = $response->json('data.data.0.details');

        $this->assertEquals('[REDACTED]', $details['password']);
        $this->assertEquals('[REDACTED]', $details['token']);
        $this->assertEquals('visible', $details['public_data']);
        $this->assertEquals('[REDACTED]', $details['nested']['secret_key']);
    }
}
