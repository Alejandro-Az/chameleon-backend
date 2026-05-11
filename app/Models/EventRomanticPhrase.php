<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRomanticPhrase extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'event_id',
        'phrase',
        'author',
        'display_order',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled'    => 'boolean',
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
