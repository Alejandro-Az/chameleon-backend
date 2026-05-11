<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventDressCode>
 */
class EventDressCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'title' => $this->faker->randomElement([
                'Formal elegante',
                'Casual chic',
                'Etiqueta rigurosa',
                'Cóctel',
                'Blanco total',
            ]),
            'description' => $this->faker->optional(0.8)->sentence(12),
            'examples' => $this->faker->optional(0.6)->sentence(15),
            'notes' => $this->faker->optional(0.5)->sentence(12),
            'display_order' => 0,
            'is_enabled' => true,
        ];
    }
}
