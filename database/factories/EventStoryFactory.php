<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventStory>
 */
class EventStoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id'      => Event::factory(),
            'title'         => $this->faker->optional(0.8)->sentence(4),
            'subtitle'      => $this->faker->optional(0.5)->sentence(6),
            'body'          => $this->faker->paragraph(3),
            'display_order' => 0,
            'is_enabled'    => true,
        ];
    }
}
