<?php

namespace App\Observers;

use App\Models\ApiKey;
use App\Models\AuthSession;
use App\Models\User;

/**
 * Handles session revocation and audit on user status changes.
 *
 * Runs in-transaction (Opción A): revocation + audit are 100% DB side-effects,
 * so executing them atomically with the status change is the strongest guarantee.
 * A rollback undoes both the status change AND the revocation.
 *
 * NOTE: Raw query builder (DB::table('users')->update(...)) bypasses Eloquent
 * events and will NOT trigger this observer. Security is still maintained via
 * the user.active middleware (403 AUTH_USER_INACTIVE).
 */
final class UserObserver
{
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        if (! $user->wasChanged('status')) {
            return;
        }

        $from = $user->getOriginal('status');
        $to   = $user->status;

        // Revoke active sessions on any status change
        $revokedCount = AuthSession::revokeAllForUser($user);

        $apiKeysRevoked = 0;

        // Premium: if a SERVICE account is suspended, revoke all its API keys
        if (($user->type ?? null) === 'service' && $to === 'suspended') {
            $apiKeysRevoked = ApiKey::revokeAllForUser($user);
        }

        \App\Services\AuditLogger::log('user.status_changed', $user, [
            'from'             => $from,
            'to'               => $to,
            'sessions_revoked' => $revokedCount,
            'api_keys_revoked' => $apiKeysRevoked,
        ]);
    }
}
