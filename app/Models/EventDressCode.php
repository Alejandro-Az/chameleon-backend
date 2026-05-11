<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventDressCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'event_id',
        'title',
        'description',
        'examples',
        'notes',
        'display_order',
        'is_enabled',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'is_enabled' => 'boolean',
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
