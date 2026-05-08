<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED  = 'failed';
    public const STATUS_BLOCKED = 'blocked';

    public $timestamps = false; // only created_at, no updated_at

    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
        'status',
        'blocked_until',
        'created_at',
    ];

    protected $casts = [
        'blocked_until' => 'datetime',
        'created_at'    => 'datetime',
    ];

    /**
     * Check if login is blocked for a given email.
     */
    public static function isBlocked(string $email): bool
    {
        return static::where('email', $email)
            ->where('blocked_until', '>', now())
            ->exists();
    }

    /**
     * Returns the remaining seconds of the active block for a given email.
     * Returns 0 if there is no active block.
     */
    public static function secondsUntilUnblocked(string $email): int
    {
        $record = static::where('email', $email)
            ->where('blocked_until', '>', now())
            ->orderByDesc('blocked_until')
            ->first();

        if (!$record || !$record->blocked_until) {
            return 0;
        }

        return max(0, (int) ($record->blocked_until->timestamp - now()->timestamp));
    }

    /**
     * Count recent failures for a given email within a time window.
     */
    public static function recentFailures(string $email, int $minutes = 15): int
    {
        return static::where('email', $email)
            ->where('status', self::STATUS_FAILED)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Record a login attempt.
     */
    public static function record(string $email, string $ip, ?string $userAgent, string $status, ?\DateTimeInterface $blockedUntil = null): static
    {
        return static::create([
            'email'         => $email,
            'ip_address'    => $ip,
            'user_agent'    => $userAgent,
            'status'        => $status,
            'blocked_until' => $blockedUntil,
            'created_at'    => now(),
        ]);
    }
}
