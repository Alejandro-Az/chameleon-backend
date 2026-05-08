<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_timezone',
        'slot_resolution_minutes',
        'holidays_auto_block',
        'self_service_cancel_hours',
        'self_service_reschedule_hours',
        'auto_confirm_new_appointments',
    ];

    protected function casts(): array
    {
        return [
            'slot_resolution_minutes' => 'integer',
            'holidays_auto_block' => 'boolean',
            'self_service_cancel_hours' => 'integer',
            'self_service_reschedule_hours' => 'integer',
            'auto_confirm_new_appointments' => 'boolean',
        ];
    }
}
