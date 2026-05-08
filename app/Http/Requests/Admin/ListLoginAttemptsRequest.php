<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ListLoginAttemptsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Handled by perm middleware
    }

    public function rules(): array
    {
        return [
            'q'        => ['nullable', 'string', 'max:255'],
            'ip'       => ['nullable', 'ip'],
            'status'   => ['nullable', 'string', 'in:success,failed,blocked'],
            'from'     => ['nullable', 'date'],
            'to'       => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
