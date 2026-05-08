<?php

namespace App\Services\Appointments;

use App\Models\AppointmentException;
use App\Models\AppointmentSetting;
use App\Models\AppointmentWeekdayRule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentDefaultsService
{
    public function ensureDefaults(bool $reset = false): AppointmentSetting
    {
        return DB::transaction(function () use ($reset) {
            if ($reset) {
                AppointmentWeekdayRule::query()->delete();
                AppointmentException::query()->delete();
            }

            $settings = AppointmentSetting::query()->updateOrCreate(
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

            return $settings->fresh();
        });
    }

    public function defaultHolidayDates(): array
    {
        $years = [Carbon::now('America/Mexico_City')->year, Carbon::now('America/Mexico_City')->year + 1];
        $holidays = [];

        foreach ($years as $year) {
            $holidays[] = ['name' => "A�o Nuevo {$year}", 'date' => Carbon::create($year, 1, 1, 0, 0, 0, 'America/Mexico_City')->toDateString()];
            $holidays[] = ['name' => "D�a de la Constituci�n {$year}", 'date' => $this->firstMondayOfMonth($year, 2)->toDateString()];
            $holidays[] = ['name' => "Natalicio de Benito Ju�rez {$year}", 'date' => $this->thirdMondayOfMonth($year, 3)->toDateString()];
            $holidays[] = ['name' => "D�a del Trabajo {$year}", 'date' => Carbon::create($year, 5, 1, 0, 0, 0, 'America/Mexico_City')->toDateString()];
            $holidays[] = ['name' => "D�a de la Independencia {$year}", 'date' => Carbon::create($year, 9, 16, 0, 0, 0, 'America/Mexico_City')->toDateString()];
            $holidays[] = ['name' => "D�a de la Revoluci�n {$year}", 'date' => $this->thirdMondayOfMonth($year, 11)->toDateString()];
            $holidays[] = ['name' => "Cambio de Poder Ejecutivo {$year}", 'date' => Carbon::create($year, 10, 1, 0, 0, 0, 'America/Mexico_City')->toDateString()];
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
