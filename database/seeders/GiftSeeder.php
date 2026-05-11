<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Gift;
use Illuminate\Database\Seeder;

class GiftSeeder extends Seeder
{
    public function run(): void
    {
        Event::all()->each(function (Event $event) {
            Gift::factory()
                ->count(rand(4, 8))
                ->sequence(fn ($seq) => ['display_order' => $seq->index])
                ->create(['event_id' => $event->id]);
        });
    }
}
