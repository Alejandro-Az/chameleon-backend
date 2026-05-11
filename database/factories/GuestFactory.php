<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Guest>
 */
class GuestFactory extends Factory
{
    protected $model = Guest::class;

    public function definition(): array
    {
        return [
            'public_id'           => (string) Str::ulid(),
            'event_id'            => Event::factory(),
            'name'                => $this->faker->name(),
            'email'               => $this->faker->safeEmail(),
            'phone'               => $this->faker->phoneNumber(),
            'invitation_code'     => strtoupper(Str::random(8)),
            'invited_seats'       => $this->faker->numberBetween(1, 5),
            'rsvp_status'         => Guest::RSVP_PENDING,
            'guests_confirmed'    => null,
            'rsvp_message'        => null,
            'show_in_public_list' => false,
            'dietary_tags'        => null,
            'dietary_notes'       => null,
            'seat_label'          => null,
            'checked_in_at'       => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state([
            'rsvp_status'      => Guest::RSVP_YES,
            'guests_confirmed' => 1,
        ]);
    }

    public function declined(): static
    {
        return $this->state([
            'rsvp_status'      => Guest::RSVP_NO,
            'guests_confirmed' => 0,
        ]);
    }
}
