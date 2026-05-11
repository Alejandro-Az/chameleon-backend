<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventLocation;
use Illuminate\Database\Seeder;

class EventLocationSeeder extends Seeder
{
    public function run(): void
    {
        Event::all()->each(function (Event $event) {
            EventLocation::factory()
                ->count(rand(1, 3))
                ->sequence(function ($sequence) {
                    return ['display_order' => $sequence->index];
                })
                ->create(['event_id' => $event->id]);
        });
    }
}
