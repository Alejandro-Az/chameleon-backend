<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentStaffWeekdayRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_staff_profile_id',
        'weekday',
        'is_active',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'is_active' => 'boolean',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(AppointmentStaffProfile::class, 'appointment_staff_profile_id');
    }
}
