<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventSchedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // Crear 3-5 schedules por cada evento existente
        Event::all()->each(function (Event $event) {
            EventSchedule::factory()
                ->count(rand(3, 5))
                ->sequence(function ($sequence) {
                    return ['display_order' => $sequence->index];
                })
                ->create(['event_id' => $event->id]);
        });
    }
}
