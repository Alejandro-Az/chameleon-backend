<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    public const SOURCE_SELF_SERVICE = 'self_service';
    public const SOURCE_INTERNAL = 'internal';

    protected $fillable = [
        'public_id',
        'user_id',
        'guest_name',
        'guest_phone',
        'guest_email',
        'appointment_service_id',
        'assigned_employee_user_id',
        'created_by_user_id',
        'starts_at',
        'ends_at',
        'status',
        'source',
        'business_timezone',
        'customer_notes',
        'internal_notes',
        'cancelled_by_user_id',
        'cancelled_at',
        'cancellation_reason',
        'completed_at',
        'no_show_marked_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'no_show_marked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $appointment): void {
            if (empty($appointment->public_id)) {
                $appointment->public_id = (string) Str::ulid();
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

    public function service(): BelongsTo
    {
        return $this->belongsTo(AppointmentService::class, 'appointment_service_id');
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_employee_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(AppointmentReminder::class);
    }
}
