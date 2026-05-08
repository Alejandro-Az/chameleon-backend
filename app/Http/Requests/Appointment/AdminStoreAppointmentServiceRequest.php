<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class AdminStoreAppointmentServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'is_active' => ['sometimes', 'boolean'],
            'staff_ids' => ['sometimes', 'array'],
            'staff_ids.*' => ['string', 'exists:users,public_id'],
        ];
    }
}
