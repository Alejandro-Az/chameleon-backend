<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventLocation>
 */
class EventLocationFactory extends Factory
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
            'name' => $this->faker->randomElement([
                'Iglesia San Miguel',
                'Salón principal',
                'Jardín central',
                'Terraza',
                'Hotel sede',
            ]),
            'address' => $this->faker->optional(0.8)->address(),
            'maps_url' => $this->faker->optional(0.7)->url(),
            'type' => $this->faker->optional(0.8)->randomElement([
                'ceremony',
                'reception',
                'cocktail',
                'dinner',
                'party',
            ]),
            'display_order' => 0,
            'is_enabled' => true,
        ];
    }
}
