<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AppointmentException extends Model
{
    use HasFactory;

    public const TYPE_HOLIDAY = 'holiday';
    public const TYPE_CLOSED_BLOCK = 'closed_block';
    public const TYPE_OPEN_EXCEPTION = 'open_exception';

    protected $fillable = [
        'public_id',
        'name',
        'exception_date',
        'type',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'exception_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $exception): void {
            if (empty($exception->public_id)) {
                $exception->public_id = (string) Str::ulid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
