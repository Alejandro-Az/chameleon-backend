<?php

namespace Database\Factories;

use App\Models\EventSong;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventSong>
 */
class EventSongFactory extends Factory
{
    public function definition(): array
    {
        $songs = [
            ['title' => 'Perfect', 'artist' => 'Ed Sheeran'],
            ['title' => 'A Thousand Years', 'artist' => 'Christina Perri'],
            ['title' => 'Can\'t Help Falling in Love', 'artist' => 'Elvis Presley'],
            ['title' => 'All of Me', 'artist' => 'John Legend'],
            ['title' => 'Thinking Out Loud', 'artist' => 'Ed Sheeran'],
            ['title' => 'At Last', 'artist' => 'Etta James'],
        ];

        $pick = $this->faker->randomElement($songs);

        return [
            'event_id'              => null, // asignar en seeder
            'suggested_by_guest_id' => null,
            'title'                 => $pick['title'],
            'artist'                => $pick['artist'],
            'url'                   => $this->faker->optional(0.5)->url(),
            'message_for_couple'    => $this->faker->optional(0.6)->sentence(8),
            'show_author'           => true,
            'status'                => EventSong::STATUS_APPROVED,
            'votes_count'           => $this->faker->numberBetween(0, 20),
        ];
    }
}
