<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneApiKeysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kaan:prune-api-keys {--dry-run : Muestra qué se borraría sin afectar la base de datos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune revoked and expired API keys that exceed the retention period.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!config('kaan.features.api_keys', false)) {
            $this->info('API Keys feature is disabled. Skipping pruning.');
            return self::SUCCESS;
        }

        $retentionDays = (int) config('kaan.api_keys.retention_days', 30);
        $threshold = Carbon::now()->subDays($retentionDays);
        $isDryRun = $this->option('dry-run');

        $query = ApiKey::query()
            ->where(function ($q) use ($threshold) {
                $q->where(function ($q2) use ($threshold) {
                    // Keys that have been revoked for longer than the retention period
                    $q2->whereNotNull('revoked_at')
                       ->where('revoked_at', '<', $threshold);
                })
                ->orWhere(function ($q2) use ($threshold) {
                    // Keys that have expired for longer than the retention period
                    $q2->whereNotNull('expires_at')
                       ->where('expires_at', '<', $threshold);
                });
            });

        if ($isDryRun) {
            $totalToPrune = $query->count();
            if ($totalToPrune === 0) {
                $this->info('No old API keys to prune.');
            } else {
                $this->info("[DRY RUN] {$totalToPrune} API key(s) ready to be pruned (older than {$retentionDays} days).");
            }
            return self::SUCCESS;
        }

        $totalDeleted = 0;
        $chunkSize = 500;

        $query->chunkById($chunkSize, function ($keys) use (&$totalDeleted) {
            $ids = $keys->pluck('id')->toArray();
            if (!empty($ids)) {
                ApiKey::whereIn('id', $ids)->delete();
                $totalDeleted += count($ids);
            }
        });

        if ($totalDeleted > 0) {
            $this->info("Successfully pruned {$totalDeleted} old API key(s).");
            \App\Services\AuditLogger::log('system.pruned_api_keys', null, [
                'count' => $totalDeleted,
                'retention_days' => $retentionDays,
            ]);
        } else {
            $this->info('No old API keys to prune.');
        }

        return self::SUCCESS;
    }
}
