<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventStory;
use Illuminate\Database\Seeder;

class EventStorySeeder extends Seeder
{
    public function run(): void
    {
        Event::all()->each(function (Event $event) {
            EventStory::factory()
                ->count(rand(1, 3))
                ->sequence(function ($sequence) {
                    return ['display_order' => $sequence->index];
                })
                ->create(['event_id' => $event->id]);
        });
    }
}
