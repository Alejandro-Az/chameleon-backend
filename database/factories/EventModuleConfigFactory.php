<?php

namespace Database\Factories;

use App\Enums\ModuleKey;
use App\Models\EventModuleConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventModuleConfigFactory extends Factory
{
    protected $model = EventModuleConfig::class;

    public function definition(): array
    {
        return [
            'module_key'   => $this->faker->randomElement(ModuleKey::cases())->value,
            'enabled'      => true,
            'auto_approve' => true,
            'order'        => $this->faker->numberBetween(0, 10),
        ];
    }
}
