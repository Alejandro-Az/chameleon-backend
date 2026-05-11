<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventSong;
use Illuminate\Database\Seeder;

class EventSongSeeder extends Seeder
{
    public function run(): void
    {
        Event::all()->each(function (Event $event) {
            EventSong::factory()
                ->count(rand(3, 6))
                ->create(['event_id' => $event->id]);
        });
    }
}
