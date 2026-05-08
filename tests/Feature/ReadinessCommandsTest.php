<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Ops\ReadinessChecker;
use Mockery\MockInterface;
use Tests\TestCase;

class ReadinessCommandsTest extends TestCase
{
    public function test_kaan_health_returns_exit_code_1_when_any_check_fails(): void
    {
        $this->mock(ReadinessChecker::class, function (MockInterface $mock): void {
            $mock->shouldReceive('runAllChecks')->once()->andReturn([
                [
                    'id' => 'env_debug',
                    'status' => 'FAIL',
                    'message' => 'APP_DEBUG está activado en producción/staging.',
                    'hint' => 'Cambia APP_DEBUG=false en el archivo .env',
                ],
            ]);
        });

        $this->artisan('kaan:health')
            ->assertExitCode(1);
    }

    public function test_kaan_health_strict_flag_treats_warn_as_fail(): void
    {
        $this->mock(ReadinessChecker::class, function (MockInterface $mock): void {
            $mock->shouldReceive('runAllChecks')->twice()->andReturn([
                [
                    'id' => 'queue_connection',
                    'status' => 'WARN',
                    'message' => 'Usando driver de colas "sync" en producción.',
                    'hint' => '...',
                ],
            ]);
        });

        // Normally, a WARN only results in success (0)
        $this->artisan('kaan:health')
            ->assertExitCode(0);

        // Under --strict, WARN becomes FAIL (1)
        $this->artisan('kaan:health', ['--strict' => true])
            ->assertExitCode(1);
    }

    public function test_kaan_install_aborts_in_production_when_readiness_has_failures(): void
    {
        config(['app.env' => 'production']);

        $this->mock(ReadinessChecker::class, function (MockInterface $mock): void {
            $mock->shouldReceive('runAllChecks')->once()->andReturn([
                [
                    'id' => 'env_debug',
                    'status' => 'FAIL',
                    'message' => 'APP_DEBUG está activado en producción/staging.',
                    'hint' => 'Cambia APP_DEBUG=false en el archivo .env',
                ],
                [
                    'id' => 'admin_password',
                    'status' => 'FAIL',
                    'message' => 'ADMIN_PASSWORD no cumple la política de seguridad estricta.',
                    'hint' => 'Mínimo 12 caracteres, mayúsculas, minúsculas, números y símbolos.',
                ],
                [
                    'id' => 'jwt_secret',
                    'status' => 'FAIL',
                    'message' => 'JWT_SECRET no está definido.',
                    'hint' => 'Ejecuta: php artisan jwt:secret',
                ],
            ]);
        });

        $this->artisan('kaan:install')
            ->expectsOutputToContain('Installation ABORTED')
            ->assertExitCode(1);
    }
}
