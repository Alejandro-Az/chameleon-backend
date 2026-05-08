<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        // Allow clients to send role by public_id while keeping internal sync by role name.
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
        /** @var \App\Models\User $user */
        $user = $this->route('user');
        $userId = $user->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'username' => [
                'sometimes', 'nullable', 'string', 'max:80',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'password' => ['sometimes', 'string', 'max:72', \Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()],
            'status' => ['sometimes', 'string', 'in:active,pending,suspended'],
            'timezone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'locale' => ['sometimes', 'nullable', 'string', 'max:10'],
            'meta' => ['sometimes', 'nullable', 'array'],
            'role' => ['sometimes', 'nullable', 'string', 'exists:roles,name'],
            'role_id' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $allowed = [
                'name', 'email', 'username', 'password', 'status',
                'timezone', 'locale', 'meta', 'role', 'role_id',
            ];

            $hasAnyAllowedField = !empty(array_intersect(array_keys($this->all()), $allowed));

            if (!$hasAnyAllowedField) {
                $validator->errors()->add('payload', 'Debes enviar al menos un campo válido para actualizar.');
            }

            if ($this->filled('role_id') && !$this->filled('role')) {
                $validator->errors()->add('role_id', 'El role_id enviado no corresponde a un rol válido.');
            }
        });
    }
}
