<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        if ($this->filled('role')) {
            $roleInput = (string) $this->input('role');
            $roleName = Role::query()->where('public_id', $roleInput)->value('name');
            if ($roleName) {
                $this->merge(['role' => $roleName]);
            }
        }

        if ($this->filled('role_id')) {
            $roleName = Role::query()->where('public_id', (string) $this->input('role_id'))->value('name');
            if ($roleName) {
                $this->merge(['role' => $roleName]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'username' => ['nullable','string','max:80','unique:users,username'],
            'password' => ['required', 'string', 'max:72', \Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()],
            'status' => ['nullable','string','in:active,pending,suspended'],
            'timezone' => ['nullable','string','max:50'],
            'locale' => ['nullable','string','max:10'],
            'meta' => ['nullable','array'],
            'role' => ['nullable','string', 'exists:roles,name'], // ej: admin|user
            'role_id' => ['nullable','string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->filled('role_id') && !$this->filled('role')) {
                $validator->errors()->add('role_id', 'El role_id enviado no corresponde a un rol válido.');
            }
        });
    }
}
