<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'string', 'exists:appointment_services,public_id'],
            'starts_at' => ['required', 'date'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
