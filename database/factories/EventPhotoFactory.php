<?php

namespace Database\Factories;

use App\Models\EventPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventPhoto>
 */
class EventPhotoFactory extends Factory
{
    protected $model = EventPhoto::class;

    public function definition(): array
    {
        return [
            'event_id'       => null, // Assign in test/seeder
            'guest_id'       => null,
            'type'           => EventPhoto::TYPE_GALLERY,
            'file_path'      => 'events/1/photos/originals/' . $this->faker->uuid() . '.jpg',
            'thumbnail_path' => null,
            'caption'        => $this->faker->optional(0.6)->sentence(5),
            'status'         => EventPhoto::STATUS_APPROVED,
            'display_order'  => $this->faker->numberBetween(1, 100),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => EventPhoto::STATUS_PENDING]);
    }

    public function guestUpload(): static
    {
        return $this->state(['type' => EventPhoto::TYPE_GUEST_UPLOAD]);
    }
}
