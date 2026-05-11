<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Guest extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const RSVP_PENDING = 'pending';
    public const RSVP_YES     = 'yes';
    public const RSVP_NO      = 'no';
    public const RSVP_MAYBE   = 'maybe';

    protected $fillable = [
        'public_id',
        'event_id',
        'name',
        'email',
        'phone',
        'invitation_code',
        'invited_seats',
        'rsvp_status',
        'guests_confirmed',
        'rsvp_message',
        'show_in_public_list',
        'dietary_tags',
        'dietary_notes',
        'seat_label',
        'checked_in_at',
    ];

    protected $casts = [
        'show_in_public_list' => 'boolean',
        'dietary_tags'        => 'array',
        'checked_in_at'       => 'datetime',
        'invited_seats'       => 'integer',
        'guests_confirmed'    => 'integer',
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
}
