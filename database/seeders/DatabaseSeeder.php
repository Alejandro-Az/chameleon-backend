<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(TemplatesSeeder::class);

        if ((bool) config('kaan.features.appointments', false)) {
            $this->call(AppointmentModuleSeeder::class);
        }

        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
            $this->call(EventScheduleSeeder::class);
            $this->call(EventLocationSeeder::class);
            $this->call(EventDressCodeSeeder::class);
            $this->call(GiftSeeder::class);
        }
    }
}