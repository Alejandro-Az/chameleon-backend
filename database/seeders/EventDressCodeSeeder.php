<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventDressCode;
use Illuminate\Database\Seeder;

class EventDressCodeSeeder extends Seeder
{
    public function run(): void
    {
        Event::all()->each(function (Event $event) {
            EventDressCode::factory()
                ->count(rand(1, 2))
                ->sequence(function ($sequence) {
                    return ['display_order' => $sequence->index];
                })
                ->create(['event_id' => $event->id]);
        });
    }
}
