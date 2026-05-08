<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Tests\TestCase;

final class HealthPolicyCenterTest extends TestCase
{
    use RefreshDatabase;

    private function rebootWithEnv(array $env): void
    {
        foreach ($env as $k => $v) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }

        \Illuminate\Support\Env::enablePutenv();
        $this->refreshApplication();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_health_includes_policy_center_flags_when_enabled(): void
    {
        $this->rebootWithEnv([
            'KAAN_FEATURE_POLICY_CENTER' => 'true',
        ]);

        // Forzar snapshot "ok" (si tu PolicyService cachea)
        Cache::flush();

        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure([
                'ok',
                'data' => [
                    'db',
                    'policy_center_enabled',
                    'policy_center_degraded',
                    'time',
                ],
            ])
            ->assertJsonPath('data.policy_center_enabled', true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_health_marks_policy_center_disabled_when_feature_is_off(): void
    {
        $this->rebootWithEnv([
            'KAAN_FEATURE_POLICY_CENTER' => 'false',
        ]);

        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.policy_center_enabled', false)
            ->assertJsonPath('data.policy_center_degraded', false);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_health_includes_service_accounts_enabled_flag(): void
    {
        $this->rebootWithEnv([
            'KAAN_FEATURE_API_KEYS' => 'true',
        ]);

        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.service_accounts_enabled', true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_health_marks_service_accounts_disabled_when_feature_is_off(): void
    {
        $this->rebootWithEnv([
            'KAAN_FEATURE_API_KEYS' => 'false',
        ]);

        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.service_accounts_enabled', false);
    }
}
