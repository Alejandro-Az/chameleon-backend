<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Database\Seeder;

class GuestSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::first();

        if (! $event) {
            $this->command->warn('GuestSeeder: no events found, skipping.');
            return;
        }

        Guest::factory()->count(5)->create(['event_id' => $event->id]);
        Guest::factory()->confirmed()->count(3)->create(['event_id' => $event->id]);
        Guest::factory()->declined()->count(2)->create(['event_id' => $event->id]);
    }
}
