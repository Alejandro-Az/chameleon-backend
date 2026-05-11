<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventRomanticPhrase;
use Illuminate\Database\Seeder;

class EventRomanticPhraseSeeder extends Seeder
{
    public function run(): void
    {
        Event::all()->each(function (Event $event) {
            EventRomanticPhrase::factory()
                ->count(rand(3, 5))
                ->sequence(function ($sequence) {
                    return ['display_order' => $sequence->index];
                })
                ->create(['event_id' => $event->id]);
        });
    }
}
