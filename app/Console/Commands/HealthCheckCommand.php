<?php

namespace App\Console\Commands;

use App\Services\Ops\ReadinessChecker;
use Illuminate\Console\Command;

class HealthCheckCommand extends Command
{
    protected $signature = 'kaan:health {--strict : Tratar advertencias (WARN) como fallos (FAIL)}';
    protected $description = 'Verifica el estado de configuración y seguridad del Kaan Core (Production Readiness)';

    public function handle(ReadinessChecker $checker): int
    {
        $this->info('Ejecutando Kaan Core Health Check...');
        $this->line('');

        $strict = $this->option('strict');
        $results = $checker->runAllChecks();

        $rows = [];
        $hasFail = false;

        foreach ($results as $result) {
            $status = $result['status'];
            
            if ($status === 'FAIL' || ($strict && $status === 'WARN')) {
                $hasFail = true;
            }

            // Colorize status
            $badge = match ($status) {
                'OK'   => '<info> [OK] </info>',
                'WARN' => '<comment>[WARN]</comment>',
                'FAIL' => '<error>[FAIL]</error>',
                default => "[$status]",
            };

            $hint = $result['hint'] ? "\n<fg=gray>» {$result['hint']}</>" : '';

            $rows[] = [
                $badge,
                "<fg=cyan>{$result['id']}</>",
                $result['message'] . $hint
            ];
        }

        $this->table(
            ['Estado', 'Check ID', 'Mensaje & Sugerencia'],
            $rows
        );

        $this->line('');

        if ($hasFail) {
            $this->error('Health Check completado con errores (FAIL).');
            return Command::FAILURE; // Exit 1
        }

        $this->info('Health Check completado: Todo OK.');
        return Command::SUCCESS; // Exit 0
    }
}
