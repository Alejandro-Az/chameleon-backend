<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AppointmentStaffProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'user_id',
        'is_active',
        'can_receive_auto_assignment',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'can_receive_auto_assignment' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $profile): void {
            if (empty($profile->public_id)) {
                $profile->public_id = (string) Str::ulid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function weekdayRules(): HasMany
    {
        return $this->hasMany(AppointmentStaffWeekdayRule::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(AppointmentStaffException::class, 'appointment_staff_profile_id');
    }
}
