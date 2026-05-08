<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentWeekdayRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'weekday',
        'is_active',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'is_active' => 'boolean',
    ];
}
