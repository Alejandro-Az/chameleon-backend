<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class AdminStoreAppointmentRequest extends FormRequest
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
            'user_id' => ['nullable', 'string', 'exists:users,public_id'],
            'guest_name' => ['required_without:user_id', 'nullable', 'string', 'max:255'],
            'guest_phone' => ['required_without:user_id', 'nullable', 'string', 'max:50'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'assigned_employee_id' => ['nullable', 'string', 'exists:users,public_id'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
