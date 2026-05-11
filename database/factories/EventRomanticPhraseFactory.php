<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventRomanticPhrase>
 */
class EventRomanticPhraseFactory extends Factory
{
    public function definition(): array
    {
        $phrases = [
            'El amor no se mide en días, se mide en momentos.',
            'Juntos somos más de lo que jamás fuimos solos.',
            'Eres mi hoy y todo mi mañana.',
            'En ti encontré la calma que siempre busqué.',
            'Cada día a tu lado es el mejor día de mi vida.',
            'Tu risa es mi canción favorita.',
            'Amar es encontrar en la felicidad del otro tu propia felicidad.',
            'El amor verdadero no tiene final.',
        ];

        $authors = [
            'Pablo Neruda',
            'Gabriel García Márquez',
            'Anónimo',
            'Antoine de Saint-Exupéry',
            null,
        ];

        return [
            'event_id'      => null, // Se asigna al crear
            'phrase'        => $this->faker->randomElement($phrases),
            'author'        => $this->faker->randomElement($authors),
            'display_order' => 0,
            'is_enabled'    => true,
        ];
    }
}
