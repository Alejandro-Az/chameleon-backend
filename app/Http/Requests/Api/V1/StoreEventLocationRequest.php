<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'maps_url' => ['nullable', 'url', 'max:500'],
            'type' => ['nullable', 'string', 'max:50'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable', 'boolean'],
        ];
    }
}
