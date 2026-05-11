<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EventSong extends Model
{
    use HasFactory;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'public_id',
        'event_id',
        'suggested_by_guest_id',
        'title',
        'artist',
        'url',
        'message_for_couple',
        'show_author',
        'status',
        'votes_count',
    ];

    protected $casts = [
        'show_author'  => 'boolean',
        'votes_count'  => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->public_id ??= (string) Str::ulid();
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function suggestedBy(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'suggested_by_guest_id');
    }
}
