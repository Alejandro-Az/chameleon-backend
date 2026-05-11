<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EventPhoto extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const TYPE_GALLERY      = 'gallery';
    public const TYPE_HERO         = 'hero';
    public const TYPE_GUEST_UPLOAD = 'guest_upload';
    public const TYPE_DRESS_CODE   = 'dress_code';
    public const TYPE_STORY        = 'story';

    public const STATUS_APPROVED = 'approved';
    public const STATUS_PENDING  = 'pending';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'public_id',
        'event_id',
        'guest_id',
        'type',
        'file_path',
        'thumbnail_path',
        'caption',
        'status',
        'display_order',
    ];

    protected $casts = [
        'display_order' => 'integer',
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

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
