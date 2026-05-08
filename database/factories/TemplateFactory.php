<?php
// database/factories/TemplateFactory.php
namespace Database\Factories;

use App\Enums\EventType;
use App\Enums\ModuleKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'public_id'            => (string) Str::ulid(),
            'name'                 => $this->faker->words(2, true),
            'event_type'           => $this->faker->randomElement(EventType::cases())->value,
            'default_module_order' => ModuleKey::defaultOrder(),
            'styles'               => [
                'primary_color' => '#c47c5a',
                'accent_color'  => '#f9ede0',
                'font_serif'    => 'Georgia, serif',
                'font_sans'     => 'Inter, sans-serif',
                'bg_image_url'  => null,
            ],
        ];
    }

    public function forWedding(): static
    {
        return $this->state(['event_type' => EventType::Wedding->value]);
    }

    public function forQuinceanera(): static
    {
        return $this->state(['event_type' => EventType::Quinceanera->value]);
    }
}
