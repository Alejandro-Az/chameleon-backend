<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gift extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_RESERVED  = 'reserved';
    public const STATUS_PURCHASED = 'purchased';

    protected $fillable = [
        'public_id',
        'event_id',
        'name',
        'description',
        'store_label',
        'url',
        'quantity',
        'quantity_reserved',
        'status',
        'display_order',
    ];

    protected $casts = [
        'quantity'          => 'integer',
        'quantity_reserved' => 'integer',
        'display_order'     => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->public_id ??= \Illuminate\Support\Str::ulid();
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function getAvailableUnitsAttribute(): int
    {
        return max(0, $this->quantity - $this->quantity_reserved);
    }
}
