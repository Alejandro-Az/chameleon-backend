<?php

namespace App\Services\Appointments;

use App\Exceptions\Appointments\AppointmentSlotUnavailableException;
use App\Models\Appointment;
use App\Models\AppointmentException;
use App\Models\AppointmentService;
use App\Models\AppointmentSetting;
use App\Models\AppointmentStaffException;
use App\Models\AppointmentWeekdayRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AppointmentAvailabilityService
{
    public function __construct(
        protected AppointmentDefaultsService $defaultsService,
    ) {
    }

    public function settings(): AppointmentSetting
    {
        return AppointmentSetting::query()->find(1) ?? $this->defaultsService->ensureDefaults();
    }

    public function availableSlots(AppointmentService $service, Carbon $date, ?User $preferredEmployee = null, bool $exposeEmployees = false): array
    {
        if (! $service->is_active) {
            return [];
        }

        $settings = $this->settings();
        $timezone = $settings->business_timezone;
        $date = $date->copy()->setTimezone($timezone)->startOfDay();
        $duration = $service->duration_minutes;
        $resolution = max(5, (int) $settings->slot_resolution_minutes);

        $employees = $this->eligibleEmployees($service, $preferredEmployee);
        if ($employees->isEmpty()) {
            return [];
        }

        $slots = [];

        foreach ($employees as $employee) {
            $intervals = $this->employeeIntervalsForDate($employee, $date, $settings);
            foreach ($intervals as $interval) {
                for ($minute = $interval['start']; $minute + $duration <= $interval['end']; $minute += $resolution) {
                    $start = $date->copy()->addMinutes($minute);
                    $end = $start->copy()->addMinutes($duration);

                    if (! $this->isSlotFreeForEmployee($employee, $start, $end)) {
                        continue;
                    }

                    $key = $start->format('c');
                    $slots[$key] ??= [
                        'starts_at' => $start->toIso8601String(),
                        'ends_at' => $end->toIso8601String(),
                        'available_employee_ids' => [],
                    ];
                    $slots[$key]['available_employee_ids'][] = $employee->public_id;
                }
            }
        }

        ksort($slots);

        return array_values(array_map(function (array $slot) use ($exposeEmployees) {
            $slot['available_count'] = count($slot['available_employee_ids']);
            if (! $exposeEmployees) {
                unset($slot['available_employee_ids']);
            }
            return $slot;
        }, $slots));
    }

    public function resolveSlot(AppointmentService $service, Carbon $requestedStart, ?User $preferredEmployee = null): array
    {
        $settings = $this->settings();
        $timezone = $settings->business_timezone;
        $requestedStart = $requestedStart->copy()->setTimezone($timezone)->second(0);
        $duration = $service->duration_minutes;
        $resolution = max(5, (int) $settings->slot_resolution_minutes);

        if ((int) $requestedStart->format('i') % $resolution !== 0) {
            throw new AppointmentSlotUnavailableException(['reason' => 'invalid_slot_resolution']);
        }

        $employees = $this->eligibleEmployees($service, $preferredEmployee);
        if ($employees->isEmpty()) {
            throw new AppointmentSlotUnavailableException(['reason' => 'employee_unavailable']);
        }

        $globalIntervals = $this->globalIntervalsForDate($requestedStart->copy()->startOfDay(), $settings);
        if ($globalIntervals === []) {
            throw new AppointmentSlotUnavailableException(['reason' => 'global_blocked']);
        }

        $requestedEnd = $requestedStart->copy()->addMinutes($duration);
        $matches = collect();

        foreach ($employees as $employee) {
            $intervals = $this->employeeIntervalsForDate($employee, $requestedStart->copy()->startOfDay(), $settings);
            $fits = collect($intervals)->contains(function (array $interval) use ($requestedStart, $requestedEnd) {
                $startMinute = ((int) $requestedStart->format('G') * 60) + (int) $requestedStart->format('i');
                $endMinute = ((int) $requestedEnd->format('G') * 60) + (int) $requestedEnd->format('i');
                return $startMinute >= $interval['start'] && $endMinute <= $interval['end'];
            });

            if (! $fits) {
                continue;
            }

            if (! $this->isSlotFreeForEmployee($employee, $requestedStart, $requestedEnd)) {
                continue;
            }

            $matches->push($employee);
        }

        if ($matches->isEmpty()) {
            throw new AppointmentSlotUnavailableException(['reason' => $preferredEmployee ? 'employee_unavailable' : 'slot_taken']);
        }

        $employee = $matches
            ->map(function (User $user) use ($requestedStart) {
                $count = Appointment::query()
                    ->where('assigned_employee_user_id', $user->id)
                    ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
                    ->whereBetween('starts_at', [
                        $requestedStart->copy()->startOfDay()->setTimezone('UTC'),
                        $requestedStart->copy()->endOfDay()->setTimezone('UTC'),
                    ])
                    ->count();

                return ['user' => $user, 'count' => $count];
            })
            ->sortBy([['count', 'asc'], ['user.public_id', 'asc']])
            ->first()['user'];

        return [
            'employee' => $employee,
            'starts_at_utc' => $requestedStart->copy()->setTimezone('UTC'),
            'ends_at_utc' => $requestedEnd->copy()->setTimezone('UTC'),
            'starts_at_local' => $requestedStart,
            'ends_at_local' => $requestedEnd,
        ];
    }

    public function globalIntervalsForDate(Carbon $date, ?AppointmentSetting $settings = null): array
    {
        $settings ??= $this->settings();
        $rule = AppointmentWeekdayRule::query()->where('weekday', $date->isoWeekday())->first();
        $intervals = $this->ruleIntervals($rule);
        $exceptions = AppointmentException::query()
            ->whereDate('exception_date', $date->toDateString())
            ->where('is_active', true)
            ->get();

        return $this->applyGlobalExceptions($intervals, $exceptions, (bool) $settings->holidays_auto_block);
    }

    public function employeeIntervalsForDate(User $employee, Carbon $date, ?AppointmentSetting $settings = null): array
    {
        $settings ??= $this->settings();
        $global = $this->globalIntervalsForDate($date, $settings);
        if ($global === []) {
            return [];
        }

        $profile = $employee->appointmentStaffProfile;
        if (! $profile || ! $profile->is_active) {
            return [];
        }

        $staffRule = $profile->weekdayRules()->where('weekday', $date->isoWeekday())->first();
        $base = $staffRule ? $this->ruleIntervals($staffRule) : $global;
        $exceptions = $profile->exceptions()->whereDate('exception_date', $date->toDateString())->where('is_active', true)->get();
        $staffIntervals = $this->applyStaffExceptions($base, $exceptions);

        return $this->intersectIntervals($global, $staffIntervals);
    }

    private function eligibleEmployees(AppointmentService $service, ?User $preferredEmployee = null): EloquentCollection
    {
        $query = User::query()
            ->human()
            ->where('status', 'active')
            ->whereHas('appointmentStaffProfile', fn ($q) => $q->where('is_active', true))
            ->whereHas('appointmentServices', fn ($q) => $q->where('appointment_services.id', $service->id))
            ->with(['appointmentStaffProfile.weekdayRules', 'appointmentStaffProfile.exceptions']);

        if ($preferredEmployee) {
            $query->whereKey($preferredEmployee->id);
        }

        return $query->get();
    }

    private function isSlotFreeForEmployee(User $employee, Carbon $startLocal, Carbon $endLocal): bool
    {
        return ! Appointment::query()
            ->where('assigned_employee_user_id', $employee->id)
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
            ->where('starts_at', '<', $endLocal->copy()->setTimezone('UTC'))
            ->where('ends_at', '>', $startLocal->copy()->setTimezone('UTC'))
            ->exists();
    }

    private function ruleIntervals($rule): array
    {
        if (! $rule || ! $rule->is_active || ! $rule->start_time || ! $rule->end_time) {
            return [];
        }

        return [[
            'start' => $this->timeToMinutes($rule->start_time),
            'end' => $this->timeToMinutes($rule->end_time),
        ]];
    }

    private function applyGlobalExceptions(array $intervals, Collection $exceptions, bool $holidaysAutoBlock): array
    {
        foreach ($exceptions as $exception) {
            if ($exception->type === AppointmentException::TYPE_HOLIDAY && ! $holidaysAutoBlock) {
                continue;
            }

            if (in_array($exception->type, [AppointmentException::TYPE_HOLIDAY, AppointmentException::TYPE_CLOSED_BLOCK], true)) {
                $intervals = $this->subtractException($intervals, $exception->start_time, $exception->end_time);
            }
        }

        foreach ($exceptions->where('type', AppointmentException::TYPE_OPEN_EXCEPTION) as $exception) {
            if ($exception->start_time && $exception->end_time) {
                $intervals[] = [
                    'start' => $this->timeToMinutes($exception->start_time),
                    'end' => $this->timeToMinutes($exception->end_time),
                ];
            }
        }

        return $this->mergeIntervals($intervals);
    }

    private function applyStaffExceptions(array $intervals, Collection $exceptions): array
    {
        foreach ($exceptions as $exception) {
            if ($exception->type === AppointmentStaffException::TYPE_CLOSED_BLOCK) {
                $intervals = $this->subtractException($intervals, $exception->start_time, $exception->end_time);
            }
        }

        foreach ($exceptions->where('type', AppointmentStaffException::TYPE_OPEN_EXCEPTION) as $exception) {
            if ($exception->start_time && $exception->end_time) {
                $intervals[] = [
                    'start' => $this->timeToMinutes($exception->start_time),
                    'end' => $this->timeToMinutes($exception->end_time),
                ];
            }
        }

        return $this->mergeIntervals($intervals);
    }

    private function subtractException(array $intervals, ?string $startTime, ?string $endTime): array
    {
        if ($startTime === null || $endTime === null) {
            return [];
        }

        $start = $this->timeToMinutes($startTime);
        $end = $this->timeToMinutes($endTime);
        $next = [];

        foreach ($intervals as $interval) {
            if ($end <= $interval['start'] || $start >= $interval['end']) {
                $next[] = $interval;
                continue;
            }

            if ($start > $interval['start']) {
                $next[] = ['start' => $interval['start'], 'end' => $start];
            }

            if ($end < $interval['end']) {
                $next[] = ['start' => $end, 'end' => $interval['end']];
            }
        }

        return $this->mergeIntervals($next);
    }

    private function intersectIntervals(array $left, array $right): array
    {
        $result = [];
        foreach ($left as $a) {
            foreach ($right as $b) {
                $start = max($a['start'], $b['start']);
                $end = min($a['end'], $b['end']);
                if ($start < $end) {
                    $result[] = ['start' => $start, 'end' => $end];
                }
            }
        }
        return $this->mergeIntervals($result);
    }

    private function mergeIntervals(array $intervals): array
    {
        usort($intervals, fn ($a, $b) => $a['start'] <=> $b['start']);
        $merged = [];
        foreach ($intervals as $interval) {
            if ($merged === [] || $interval['start'] > $merged[count($merged) - 1]['end']) {
                $merged[] = $interval;
                continue;
            }
            $merged[count($merged) - 1]['end'] = max($merged[count($merged) - 1]['end'], $interval['end']);
        }
        return array_values(array_filter($merged, fn ($interval) => $interval['start'] < $interval['end']));
    }

    private function timeToMinutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', substr($time, 0, 5)));
        return ($hour * 60) + $minute;
    }
}
