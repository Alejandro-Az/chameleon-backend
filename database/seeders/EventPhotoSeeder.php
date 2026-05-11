<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventPhoto;
use Illuminate\Database\Seeder;

class EventPhotoSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::first();

        if (! $event) {
            $this->command->warn('EventPhotoSeeder: no events found, skipping.');
            return;
        }

        EventPhoto::factory()->count(5)->create([
            'event_id' => $event->id,
            'type'     => EventPhoto::TYPE_GALLERY,
            'status'   => EventPhoto::STATUS_APPROVED,
        ]);
    }
}
