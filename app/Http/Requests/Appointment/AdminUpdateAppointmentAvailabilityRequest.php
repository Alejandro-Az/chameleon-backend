<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateAppointmentAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_timezone' => ['sometimes', 'timezone'],
            'slot_resolution_minutes' => ['sometimes', 'integer', 'min:5', 'max:120'],
            'holidays_auto_block' => ['sometimes', 'boolean'],
            'self_service_cancel_hours' => ['sometimes', 'integer', 'min:0', 'max:168'],
            'self_service_reschedule_hours' => ['sometimes', 'integer', 'min:0', 'max:168'],
            'auto_confirm_new_appointments' => ['sometimes', 'boolean'],
            'weekday_rules' => ['sometimes', 'array', 'size:7'],
            'weekday_rules.*.weekday' => ['required_with:weekday_rules', 'integer', 'between:1,7'],
            'weekday_rules.*.is_active' => ['required_with:weekday_rules', 'boolean'],
            'weekday_rules.*.start_time' => ['nullable', 'date_format:H:i'],
            'weekday_rules.*.end_time' => ['nullable', 'date_format:H:i', 'after:weekday_rules.*.start_time'],
        ];
    }
}
