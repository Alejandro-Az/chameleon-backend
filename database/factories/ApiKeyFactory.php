<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiKey>
 */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        $prefix = (string) config('kaan.api_keys.prefix', 'kk_live_');
        $prefixLen = min((int) config('kaan.api_keys.prefix_length', 12), 64);

        $plaintext = $prefix . Str::random(40);

        return [
            'user_id'            => User::factory(),
            'created_by_user_id' => null,
            'name'               => fake()->words(3, true),
            'prefix'             => substr($plaintext, 0, $prefixLen),
            'key_hash'           => hash('sha256', $plaintext),
            'scopes'             => [],
            'last_used_at'       => null,
            'expires_at'         => null,
            'revoked_at'         => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn () => ['revoked_at' => now()]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }
}
