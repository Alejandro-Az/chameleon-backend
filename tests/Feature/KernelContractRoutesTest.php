<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

/**
 * Kernel Contract: Route Gating
 *
 * Validates that feature flags correctly prevent route registration.
 * Routes are registered at boot, so toggling config() at runtime does NOT
 * unregister them — we must reboot the application with the env overrides.
 */
final class KernelContractRoutesTest extends TestCase
{
    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'KAAN_FEATURE_ADMIN',
            'KAAN_FEATURE_AUDIT',
            'KAAN_FEATURE_ADMIN_SECURITY',
            'KAAN_FEATURE_API_KEYS',
            'KAAN_FEATURE_POLICY_CENTER',
            'KAAN_FEATURE_APPOINTMENTS',
        ] as $key) {
            $this->originalEnv[$key] = getenv($key) !== false ? getenv($key) : null;
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->originalEnv as $key => $value) {
            if ($value === null) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }

        $this->refreshApplication();
        parent::tearDown();
    }

    /**
     * Set environment variables and reboot the application so routes
     * are re-registered with the new config values.
     */
    private function rebootWithEnv(array $env): void
    {
        foreach ($env as $k => $v) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }

        // Force Dotenv repository to pick up putenv values
        \Illuminate\Support\Env::enablePutenv();

        $this->refreshApplication();
    }

    // ---------------------------------------------------------------
    // Admin OFF → admin routes must NOT be registered at all
    // ---------------------------------------------------------------

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_admin_routes_are_not_registered_when_admin_feature_is_off(): void
    {
        $this->rebootWithEnv([
            'KAAN_FEATURE_ADMIN' => 'false',
        ]);

        $res = $this->getJson('/api/v1/admin/users');

        $res->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']])
            ->assertJsonPath('error.details', null);
    }

    // ---------------------------------------------------------------
    // Audit OFF (admin ON) → audit-logs routes NOT registered
    // ---------------------------------------------------------------

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_audit_logs_routes_are_not_registered_when_audit_is_off(): void
    {
        $this->rebootWithEnv([
            'KAAN_FEATURE_ADMIN' => 'true',
            'KAAN_FEATURE_AUDIT' => 'false',
        ]);

        $this->assertFalse(
            config('kaan.features.audit'),
            'Config kaan.features.audit should be false after reboot with KAAN_FEATURE_AUDIT=false'
        );

        $res = $this->getJson('/api/v1/admin/audit-logs');

        $res->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']])
            ->assertJsonPath('error.details', null);
    }

    // ---------------------------------------------------------------
    // Security Center OFF (admin ON) → security routes NOT registered
    // ---------------------------------------------------------------

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_admin_security_routes_are_not_registered_when_admin_security_is_off(): void
    {
        $this->rebootWithEnv([
            'KAAN_FEATURE_ADMIN'           => 'true',
            'KAAN_FEATURE_ADMIN_SECURITY'  => 'false',
        ]);

        // Verify config reflects the flag correctly
        $this->assertFalse(
            config('kaan.features.admin_security'),
            'Config kaan.features.admin_security should be false after reboot with KAAN_FEATURE_ADMIN_SECURITY=false'
        );

        $res = $this->getJson('/api/v1/admin/security/login-attempts');

        $res->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']])
            ->assertJsonPath('error.details', null);
    }

    // ---------------------------------------------------------------
    // API Keys OFF → auth exchange and admin routes NOT registered
    // ---------------------------------------------------------------

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_api_keys_routes_are_not_registered_when_api_keys_is_off(): void
    {
        $this->rebootWithEnv([
            'KAAN_FEATURE_ADMIN'    => 'true',
            'KAAN_FEATURE_API_KEYS' => 'false',
        ]);

        $this->assertFalse(config('kaan.features.api_keys'));

        $this->postJson('/api/v1/auth/api-keys/exchange')
            ->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']])
            ->assertJsonPath('error.details', null);

        $this->getJson('/api/v1/admin/service-accounts')
            ->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']])
            ->assertJsonPath('error.details', null);

        // admin.api-keys routes (rotate, delete) also gated
        $this->postJson('/api/v1/admin/api-keys/fake-ulid/rotate')
            ->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND');

        $this->deleteJson('/api/v1/admin/api-keys/fake-ulid')
            ->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND');
    }

    // ---------------------------------------------------------------
    // Policy Center OFF → admin policies NOT registered
    // ---------------------------------------------------------------

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_policy_center_routes_are_not_registered_when_policy_center_is_off(): void
    {
        $this->rebootWithEnv([
            'KAAN_FEATURE_ADMIN'         => 'true',
            'KAAN_FEATURE_POLICY_CENTER' => 'false',
        ]);

        $this->assertFalse(config('kaan.features.policy_center'));

        $this->getJson('/api/v1/admin/policies')
            ->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']])
            ->assertJsonPath('error.details', null);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_appointments_routes_are_not_registered_when_appointments_feature_is_off(): void
    {
        $this->rebootWithEnv([
            'KAAN_FEATURE_APPOINTMENTS' => 'false',
            'KAAN_FEATURE_ADMIN' => 'true',
        ]);

        $this->assertFalse(config('kaan.features.appointments'));

        $this->getJson('/api/v1/appointments')
            ->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']])
            ->assertJsonPath('error.details', null);

        $this->getJson('/api/v1/admin/appointment-services')
            ->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']])
            ->assertJsonPath('error.details', null);
    }
}
