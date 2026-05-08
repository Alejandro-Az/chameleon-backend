<?php
// app/Models/EventModuleConfig.php
namespace App\Models;

use App\Enums\ModuleKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventModuleConfig extends Model
{
    protected $fillable = ['event_id', 'module_key', 'enabled', 'order'];

    protected $casts = [
        'module_key' => ModuleKey::class,
        'enabled'    => 'boolean',
        'order'      => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
