<?php

namespace App\Console\Commands;

use App\Models\AuthSession;
use Illuminate\Console\Command;

class PruneExpiredSessionsCommand extends Command
{
    protected $signature = 'kaan:sessions:prune-expired {--expired-days=7 : Days to retain expired sessions} {--revoked-days=30 : Days to retain revoked sessions} {--dry-run : Execute without deleting data}';

    protected $description = 'Prune old expired or revoked auth sessions from the database in chunks';

    public function handle()
    {
        $expiredDays = (int) ($this->option('expired-days') ?: config('kaan.security.sessions.retention.expired_days', 7));
        $revokedDays = (int) ($this->option('revoked-days') ?: config('kaan.security.sessions.retention.revoked_days', 30));
        $dryRun = $this->option('dry-run');

        $this->info("Pruning sessions: expired > {$expiredDays} days ago, revoked > {$revokedDays} days ago.");

        $chunkSize = 1000;

        // --- EXPIRED SESSIONS ---
        $expiredCutoff = now()->subDays($expiredDays);
        $expiredQuery = AuthSession::where('expires_at', '<', $expiredCutoff);
        
        $totalExpired = (clone $expiredQuery)->count();
        $deletedExpired = 0;

        if ($totalExpired > 0) {
            $this->info("Found {$totalExpired} expired sessions to prune.");
            if (!$dryRun) {
                while (true) {
                    $ids = (clone $expiredQuery)->limit($chunkSize)->pluck('id');
                    if ($ids->isEmpty()) break;
                    $deletedExpired += AuthSession::whereIn('id', $ids)->delete();
                }
            } else {
                $this->info("[DRY-RUN] Would have deleted {$totalExpired} expired sessions.");
            }
        }

        // --- REVOKED SESSIONS ---
        $revokedCutoff = now()->subDays($revokedDays);
        $revokedQuery = AuthSession::where('revoked_at', '<', $revokedCutoff);
        
        $totalRevoked = (clone $revokedQuery)->count();
        $deletedRevoked = 0;

        if ($totalRevoked > 0) {
            $this->info("Found {$totalRevoked} revoked sessions to prune.");
            if (!$dryRun) {
                while (true) {
                    $ids = (clone $revokedQuery)->limit($chunkSize)->pluck('id');
                    if ($ids->isEmpty()) break;
                    $deletedRevoked += AuthSession::whereIn('id', $ids)->delete();
                }
            } else {
                $this->info("[DRY-RUN] Would have deleted {$totalRevoked} revoked sessions.");
            }
        }

        if (!$dryRun) {
            $this->info("Pruned {$deletedExpired} expired sessions.");
            $this->info("Pruned {$deletedRevoked} revoked sessions.");
        }

        return self::SUCCESS;
    }
}
