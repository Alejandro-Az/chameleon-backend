<?php

namespace App\Models;

use Illuminate\Support\Str;

class Role extends \Spatie\Permission\Models\Role
{
    /**
     * Keep public_id system-managed only.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id', 'public_id'];

    protected static function booted(): void
    {
        static::creating(function (self $role): void {
            if (empty($role->public_id)) {
                $role->public_id = (string) Str::ulid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        return static::query()
            ->where('public_id', (string) $value)
            ->first();
    }
}
