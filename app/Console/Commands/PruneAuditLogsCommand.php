<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AuditLog;
use Carbon\Carbon;

class PruneAuditLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kaan:audit:prune {--days= : Number of days to retain logs} {--dry-run : Execute without deleting data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old audit logs based on retention days configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days') ?? config('kaan.audit.retention_days');

        if (!$days || (int)$days <= 0) {
            $this->info('Audit pruning is disabled (retention days is null or zero). Exiting.');
            return 0;
        }

        $cutoffDate = Carbon::now()->subDays((int)$days);
        $this->info("Pruning audit logs created before {$cutoffDate->toDateTimeString()}...");

        $query = AuditLog::query()->where('created_at', '<', $cutoffDate);
        $totalToDelete = (clone $query)->count();

        if ($totalToDelete === 0) {
            $this->info('No audit logs found to prune.');
            return 0;
        }

        if ($this->option('dry-run')) {
            $this->info("[DRY-RUN] Would have deleted {$totalToDelete} log(s).");
            return 0;
        }

        $deletedCount = 0;
        $chunkSize = 1000;

        // Using chunks with manual deletion ensures we do not block large tables for a long time
        $bar = $this->output->createProgressBar((int) ceil($totalToDelete / $chunkSize));
        $bar->start();

        while (true) {
            $idsToDelete = AuditLog::query()
                ->where('created_at', '<', $cutoffDate)
                ->limit($chunkSize)
                ->pluck('id');

            if ($idsToDelete->isEmpty()) {
                break;
            }

            $deletedObj = AuditLog::whereIn('id', $idsToDelete)->delete();
            $deletedCount += $deletedObj;
            $bar->advance();
        }

        $bar->finish();

        $this->newLine();
        $this->info("Successfully deleted {$deletedCount} old audit log(s).");

        return 0;
    }
}
