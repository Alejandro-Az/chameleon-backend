<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kernel Contract: Payload Shapes
 *
 * Seals the API response contracts that any frontend can depend on.
 * If a future change breaks these shapes, this test MUST fail.
 */
final class KernelContractPayloadTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------
    // Validation Error shape (422) — transversal contract
    // ---------------------------------------------------------------

    public function test_validation_error_shape_is_stable(): void
    {
        $res = $this->postJson('/api/v1/auth/login', []);

        $res->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'ok',
                'error' => [
                    'code',
                    'message',
                    'details' => [
                        'login',
                        'password',
                    ],
                ],
            ]);
    }

    // ---------------------------------------------------------------
    // Paginated payload shape (self-service sessions)
    // ---------------------------------------------------------------

    public function test_paginated_payload_shape_is_stable_for_sessions(): void
    {
        $user = User::factory()->create([
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        $res = $this->actingAs($user, 'api')
            ->getJson('/api/v1/auth/sessions?per_page=15');

        $res->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonStructure([
                'ok',
                'data' => [
                    'data',
                    'links',
                    'meta',
                ],
            ]);
    }

    // ---------------------------------------------------------------
    // Error envelope: details key always present (even as null)
    // ---------------------------------------------------------------

    public function test_unauthenticated_error_shape_includes_details(): void
    {
        $res = $this->getJson('/api/v1/auth/me');

        $res->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonStructure([
                'error' => ['code', 'message', 'details'],
            ])
            ->assertJsonPath('error.details', null);
    }

    // ---------------------------------------------------------------
    // Error envelope: ModelNotFoundException (404)
    // ---------------------------------------------------------------

    public function test_model_not_found_error_shape_includes_details(): void
    {
        // Require auth to hit an endpoint that throws ModelNotFound, but request a non-existent ID
        $user = User::factory()->create([
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);
        
        // We need the admin role to exist in the DB for this feature test since we use Spatie
        \App\Models\Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $user->assignRole('admin');

        $res = $this->actingAs($user, 'api')
            ->getJson('/api/v1/admin/users/01HZZZZZZZZZZZZZZZZZZZZZZZ');

        $res->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'NOT_FOUND')
            ->assertJsonStructure([
                'error' => ['code', 'message', 'details'],
            ])
            ->assertJsonPath('error.details', null);
    }

    // ---------------------------------------------------------------
    // Error envelope: AUTH_FORBIDDEN (403) — RBAC denied
    // ---------------------------------------------------------------

    public function test_forbidden_error_shape_includes_details_null(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $user = User::factory()->create([
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);
        // User sin rol admin → no tiene permiso admin.users.manage

        $res = $this->actingAs($user, 'api')
            ->getJson('/api/v1/admin/users');

        $res->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN')
            ->assertJsonStructure([
                'error' => ['code', 'message', 'details'],
            ])
            ->assertJsonPath('error.details', null);
    }

    // ---------------------------------------------------------------
    // Error envelope: AUTH_TOO_MANY_ATTEMPTS (429) — Rate limited
    // ---------------------------------------------------------------

    public function test_rate_limited_error_shape_includes_details_null(): void
    {
        $loginNorm = strtolower(trim('ratelimit@test.com'));
        $loginHmac = hash_hmac('sha256', $loginNorm, config('app.key'));
        $throttleKey = "auth-login:127.0.0.1|{$loginHmac}";
        
        \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);

        // Agotar los 5 intentos permitidos
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'login'    => 'ratelimit@test.com',
                'password' => 'wrong',
            ]);
        }

        // El intento 6 debe devolver 429 con envelope correcto
        $res = $this->postJson('/api/v1/auth/login', [
            'login'    => 'ratelimit@test.com',
            'password' => 'wrong',
        ]);

        $res->assertStatus(429)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'AUTH_TOO_MANY_ATTEMPTS')
            ->assertJsonStructure([
                'error' => ['code', 'message', 'details'],
            ])
            ->assertJsonPath('error.details', null);
    }
}
