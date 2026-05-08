<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize email before validation (lowercase + trim).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->input('email'))),
            ]);
        }
    }

    public function rules(): array
    {
        $passwordRule = Password::min(12)->mixedCase()->numbers();

        $rules = [
            'email'                 => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', $passwordRule, 'max:72', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
            'profile'               => ['sometimes', 'array'],
            'profile.phone'         => ['nullable', 'string', 'max:30'],
            'profile.company'       => ['nullable', 'string', 'max:120'],
        ];

        if (config('kaan.auth.register_require_name', true)) {
            $rules['name'] = ['required', 'string', 'max:120'];
        } else {
            $rules['name'] = ['nullable', 'string', 'max:120'];
        }

        return $rules;
    }
}
