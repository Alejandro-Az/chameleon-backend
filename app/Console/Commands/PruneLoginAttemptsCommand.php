<?php

namespace App\Console\Commands;

use App\Models\LoginAttempt;
use Illuminate\Console\Command;

class PruneLoginAttemptsCommand extends Command
{
    protected $signature = 'kaan:security:prune-login-attempts
        {--days= : Days to retain login attempts (falls back to config)}
        {--dry-run : Execute without deleting data}';

    protected $description = 'Prune old login attempts from the database in chunks';

    public function handle(): int
    {
        $days  = (int) ($this->option('days') ?: config('kaan.security.login_attempts.retention_days', 30));
        $dryRun = $this->option('dry-run');

        $cutoff    = now()->subDays($days);
        $chunkSize = 1000;

        $this->info("Pruning login attempts older than {$days} days (before {$cutoff->toDateTimeString()}).");

        $query = LoginAttempt::where('created_at', '<', $cutoff);
        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No login attempts to prune.');
            return self::SUCCESS;
        }

        $this->info("Found {$total} login attempts to prune.");

        if ($dryRun) {
            $this->info("[DRY-RUN] Would have deleted {$total} login attempts.");
            return self::SUCCESS;
        }

        $deleted = 0;
        while (true) {
            $ids = (clone $query)->limit($chunkSize)->pluck('id');
            if ($ids->isEmpty()) {
                break;
            }
            $deleted += LoginAttempt::whereIn('id', $ids)->delete();
        }

        $this->info("Pruned {$deleted} login attempts.");

        return self::SUCCESS;
    }
}
