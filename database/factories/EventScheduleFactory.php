<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventSchedule>
 */
class EventScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = $this->faker->dateTimeBetween('+1 week', '+2 weeks');
        $endTime = (clone $startTime)->modify('+2 hours');

        $locationTypes = ['ceremony', 'reception', 'dinner', 'cocktail', 'dessert', 'entertainment', 'ceremony_rehearsal'];
        $titles = [
            'Ceremonia',
            'Recepción de cócteles',
            'Cena de gala',
            'Baile',
            'Corte del pastel',
            'Entrega de recuerdos',
            'Despedida',
        ];

        return [
            'event_id' => null, // Se asigna en el seeder
            'title' => $this->faker->randomElement($titles),
            'description' => $this->faker->optional(0.7)->sentence(10),
            'starts_at' => $startTime,
            'ends_at' => $this->faker->optional(0.6)->randomElement([$endTime, null]),
            'location_label' => $this->faker->optional(0.8)->randomElement([
                'Salón principal',
                'Jardín trasero',
                'Entrada principal',
                'Jardín norte',
                'Salón pequeño',
                'Terraza',
                'Sala de estar',
            ]),
            'location_type' => $this->faker->randomElement($locationTypes),
            'display_order' => 0,
            'is_enabled' => true,
        ];
    }
}
