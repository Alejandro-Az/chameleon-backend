<?php
// database/factories/EventFactory.php
namespace Database\Factories;

use App\Enums\EventType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->words(3, true);
        return [
            'slug'        => Str::slug($name) . '-' . Str::random(4),
            'name'        => $name,
            'type'        => $this->faker->randomElement(EventType::cases())->value,
            'owner_id'    => User::factory(),
            'template_id' => null,
            'date'        => $this->faker->dateTimeBetween('+1 month', '+1 year')->format('Y-m-d'),
            'status'      => 'draft',
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => 'published']);
    }
}
