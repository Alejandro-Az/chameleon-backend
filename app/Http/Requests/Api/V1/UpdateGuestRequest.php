<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'string', 'max:200'],
            'email'         => ['sometimes', 'nullable', 'email', 'max:200'],
            'phone'         => ['sometimes', 'nullable', 'string', 'max:50'],
            'invited_seats' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'seat_label'    => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
