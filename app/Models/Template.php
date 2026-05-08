<?php
// app/Models/Template.php
namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id', 'name', 'event_type', 'default_module_order', 'styles',
    ];

    protected $casts = [
        'event_type'           => EventType::class,
        'default_module_order' => 'array',
        'styles'               => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Template $template) {
            if (empty($template->public_id)) {
                $template->public_id = (string) Str::ulid();
            }
        });
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
