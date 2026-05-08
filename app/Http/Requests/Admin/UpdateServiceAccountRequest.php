<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceAccountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes','string','max:255'],
            'status'      => ['sometimes','string','in:active,pending,suspended'],
            'description' => ['sometimes','nullable','string','max:500'],
            'role'        => ['sometimes','nullable','string','exists:roles,name'],
        ];
    }
}
