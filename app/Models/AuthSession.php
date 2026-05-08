<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AuthSession extends Model
{
    protected $table = 'auth_sessions';

    protected $fillable = [
        'user_id',
        'api_key_id',
        'token_id',
        'ip',
        'user_agent',
        'last_seen_at',
        'revoked_at',
        'expires_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Auto-generate public_id on creating if missing.
     */
    protected static function booted(): void
    {
        static::creating(function (AuthSession $model): void {
            if (empty($model->public_id)) {
                $model->public_id = (string) Str::ulid();
            }
        });
    }

    /**
     * Use public_id as the route key name by default.
     */
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * Resolve route binding strictly by public_id.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return static::where('public_id', (string) $value)->first();
    }

    /**
     * Revoke all active sessions for a user.
     * Auth sessions are a core capability — always active.
     */
    public static function revokeAllForUser(User $user): int
    {
        return static::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->update(['revoked_at' => now()]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
