<?php
// app/Models/Event.php
namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'name', 'type', 'owner_id', 'template_id', 'date', 'status',
    ];

    protected $casts = [
        'type' => EventType::class,
        'date' => 'date',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function moduleConfigs(): HasMany
    {
        return $this->hasMany(EventModuleConfig::class)->orderBy('order');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EventSchedule::class)->orderBy('display_order');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(EventLocation::class)->orderBy('display_order');
    }

    public function dressCodes(): HasMany
    {
        return $this->hasMany(EventDressCode::class)->orderBy('display_order');
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }
}
