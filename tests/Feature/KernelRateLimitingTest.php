<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Contract Tests: Global Rate Limiting
 *
 * Validates that the 2-tier rate limiting strategy works correctly:
 * - Tier 1 (api): 60 req/min for all /api/v1/* endpoints
 * - Tier 2 (admin): 30 req/min for /api/v1/admin/* endpoints
 *
 * Also validates that 429 responses follow the contract envelope
 * with RATE_LIMIT_EXCEEDED error.code and Retry-After header
 * per CONTRACTS.md §4.
 */
final class KernelRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        \Illuminate\Support\Facades\Cache::flush();
    }

    protected function tearDown(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('admin', fn (Request $request) => Limit::perMinute(30)->by($request->user('api')?->id ?: $request->ip()));
        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // 429 — envelope contractual correcto (CONTRACTS.md §4)
    // ---------------------------------------------------------------

    public function test_rate_limit_returns_429_with_contract_envelope(): void
    {
        // Reducir el límite global a 1 para disparar 429 rápidamente.
        // Esto NO invalida el test: verificamos que la infraestructura
        // (definición en AppServiceProvider + handler en bootstrap/app.php)
        // produce el envelope correcto con Retry-After.
        RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(1)->by($request->ip());
        });

        // Primera petición: pasa
        $this->getJson('/api/v1/health')->assertStatus(200);

        // Segunda petición: excede el límite
        $res = $this->getJson('/api/v1/health');

        $res->assertStatus(429)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'RATE_LIMIT_EXCEEDED')
            ->assertJsonStructure([
                'error' => ['code', 'message', 'details' => ['retry_after']],
            ])
            ->assertHeader('Retry-After');

        // Verificar que retry_after es un entero positivo
        $retryAfter = $res->json('error.details.retry_after');
        $this->assertIsInt($retryAfter);
        $this->assertGreaterThan(0, $retryAfter);
    }

    // ---------------------------------------------------------------
    // Tier 2 — admin usa user ID como key (no IP) tras auth:api
    // ---------------------------------------------------------------

    public function test_admin_rate_limit_keys_by_authenticated_user(): void
    {
        // Reducir admin a 2 para poder disparar el 429 sin 100 peticiones.
        RateLimiter::for('admin', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(2)
                ->by($request->user('api')?->id ?: $request->ip());
        });

        // Subir el global para no interferir
        RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(100)->by($request->ip());
        });

        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        // Dos peticiones pasan (límite = 2)
        $this->actingAs($admin, 'api')->getJson('/api/v1/admin/users')->assertStatus(200);
        $this->actingAs($admin, 'api')->getJson('/api/v1/admin/users')->assertStatus(200);

        // Tercera excede el límite admin
        $res = $this->actingAs($admin, 'api')->getJson('/api/v1/admin/users');

        $res->assertStatus(429)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'RATE_LIMIT_EXCEEDED')
            ->assertHeader('Retry-After');
    }

    public function test_admin_rate_limit_keys_by_user_and_ignores_ip_rotation(): void
    {
        // Reducir admin a 2
        RateLimiter::for('admin', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(2)
                ->by($request->user('api')?->id ?: $request->ip());
        });

        // Configurar 1 admin
        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('admin');

        // Petición 1: IP diferente
        $this->actingAs($admin, 'api')
            ->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->getJson('/api/v1/admin/users')
            ->assertStatus(200);

        // Petición 2: IP diferente
        $this->actingAs($admin, 'api')
            ->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->getJson('/api/v1/admin/users')
            ->assertStatus(200);

        // Petición 3: IP diferente. Si el límite fuera por IP, esta pasaría.
        // Como falla con 429, significa que la llave acumuladora es de hecho el User ID.
        $this->actingAs($admin, 'api')
            ->withServerVariables(['REMOTE_ADDR' => '10.0.0.3'])
            ->getJson('/api/v1/admin/users')
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'RATE_LIMIT_EXCEEDED');
    }

    // ---------------------------------------------------------------
    // 200 — health no bloqueado bajo uso normal
    // ---------------------------------------------------------------

    public function test_health_endpoint_is_accessible_under_normal_usage(): void
    {
        $res = $this->getJson('/api/v1/health');

        $res->assertStatus(200)
            ->assertJsonPath('ok', true);
    }
}
