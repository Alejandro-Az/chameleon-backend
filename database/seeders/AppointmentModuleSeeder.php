<?php

namespace Database\Seeders;

use App\Models\AppointmentException;
use App\Models\AppointmentSetting;
use App\Models\AppointmentWeekdayRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AppointmentModuleSeeder extends Seeder
{
    public function run(): void
    {
        AppointmentSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'business_timezone' => 'America/Mexico_City',
                'slot_resolution_minutes' => 30,
                'holidays_auto_block' => true,
                'self_service_cancel_hours' => 24,
                'self_service_reschedule_hours' => 24,
                'auto_confirm_new_appointments' => true,
            ],
        );

        $rules = [
            1 => ['active' => true, 'start' => '09:00:00', 'end' => '18:00:00'],
            2 => ['active' => true, 'start' => '09:00:00', 'end' => '18:00:00'],
            3 => ['active' => true, 'start' => '09:00:00', 'end' => '18:00:00'],
            4 => ['active' => true, 'start' => '09:00:00', 'end' => '18:00:00'],
            5 => ['active' => true, 'start' => '09:00:00', 'end' => '18:00:00'],
            6 => ['active' => false, 'start' => null, 'end' => null],
            7 => ['active' => false, 'start' => null, 'end' => null],
        ];

        foreach ($rules as $weekday => $rule) {
            AppointmentWeekdayRule::query()->updateOrCreate(
                ['weekday' => $weekday],
                [
                    'is_active' => $rule['active'],
                    'start_time' => $rule['start'],
                    'end_time' => $rule['end'],
                ],
            );
        }

        foreach ($this->defaultHolidayDates() as $holiday) {
            AppointmentException::query()->updateOrCreate(
                ['name' => $holiday['name'], 'exception_date' => $holiday['date'], 'type' => AppointmentException::TYPE_HOLIDAY],
                [
                    'start_time' => null,
                    'end_time' => null,
                    'is_active' => true,
                ],
            );
        }
    }

    private function defaultHolidayDates(): array
    {
        $years = [Carbon::now('America/Mexico_City')->year, Carbon::now('America/Mexico_City')->year + 1];
        $holidays = [];

        foreach ($years as $year) {
            $holidays[] = ['name' => "Ano Nuevo {$year}", 'date' => Carbon::create($year, 1, 1, 0, 0, 0, 'America/Mexico_City')->toDateString()];
            $holidays[] = ['name' => "Dia de la Constitucion {$year}", 'date' => $this->firstMondayOfMonth($year, 2)->toDateString()];
            $holidays[] = ['name' => "Natalicio de Benito Juarez {$year}", 'date' => $this->thirdMondayOfMonth($year, 3)->toDateString()];
            $holidays[] = ['name' => "Dia del Trabajo {$year}", 'date' => Carbon::create($year, 5, 1, 0, 0, 0, 'America/Mexico_City')->toDateString()];
            $holidays[] = ['name' => "Dia de la Independencia {$year}", 'date' => Carbon::create($year, 9, 16, 0, 0, 0, 'America/Mexico_City')->toDateString()];
            $holidays[] = ['name' => "Dia de la Revolucion {$year}", 'date' => $this->thirdMondayOfMonth($year, 11)->toDateString()];

            if ($year % 6 === 0) {
                $holidays[] = ['name' => "Cambio de Poder Ejecutivo {$year}", 'date' => Carbon::create($year, 10, 1, 0, 0, 0, 'America/Mexico_City')->toDateString()];
            }

            $holidays[] = ['name' => "Navidad {$year}", 'date' => Carbon::create($year, 12, 25, 0, 0, 0, 'America/Mexico_City')->toDateString()];
        }

        return $holidays;
    }

    private function firstMondayOfMonth(int $year, int $month): Carbon
    {
        $date = Carbon::create($year, $month, 1, 0, 0, 0, 'America/Mexico_City');

        if ($date->dayOfWeekIso === Carbon::MONDAY) {
            return $date;
        }

        return $date->next(Carbon::MONDAY);
    }

    private function thirdMondayOfMonth(int $year, int $month): Carbon
    {
        return $this->firstMondayOfMonth($year, $month)->copy()->addWeeks(2);
    }
}
