<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicySetting extends Model
{
    protected $table = 'policy_settings';

    protected $fillable = [
        'key',
        'value_json',
        'value_encrypted',
        'updated_by_user_id',
    ];

    protected $casts = [
        'value_json' => 'json',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
