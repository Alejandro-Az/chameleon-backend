<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceAccountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => ['required','string','max:255'],
            'email'       => ['nullable','email','max:255','unique:users,email'],
            'description' => ['nullable','string','max:500'],
            'role'        => ['nullable','string','exists:roles,name'],
        ];
    }
}
