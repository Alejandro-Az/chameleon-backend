<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'event_id',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'location_label',
        'location_type',
        'display_order',
        'is_enabled',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_enabled' => 'boolean',
        'display_order' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->public_id ??= \Illuminate\Support\Str::ulid();
        });
    }
}
