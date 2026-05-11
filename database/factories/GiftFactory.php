<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gift>
 */
class GiftFactory extends Factory
{
    public function definition(): array
    {
        $names = [
            'Vajilla de porcelana',
            'Set de cuchillos de cocina',
            'Cafetera espresso',
            'Licuadora de alta potencia',
            'Juego de sábanas',
            'Toallas de baño',
            'Cuadro decorativo',
            'Lámpara de sala',
            'Televisor 55"',
            'Aspiradora robot',
        ];

        $stores = ['Liverpool', 'El Palacio de Hierro', 'Amazon', 'Coppel', 'Walmart', null];

        return [
            'event_id'          => null,
            'name'              => $this->faker->randomElement($names),
            'description'       => $this->faker->optional(0.6)->sentence(8),
            'store_label'       => $this->faker->randomElement($stores),
            'url'               => $this->faker->optional(0.5)->url(),
            'quantity'          => $this->faker->numberBetween(1, 3),
            'quantity_reserved' => 0,
            'status'            => 'pending',
            'display_order'     => 0,
        ];
    }
}
