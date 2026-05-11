<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'starts_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'ends_at' => ['nullable', 'date_format:Y-m-d H:i:s', 'after_or_equal:starts_at'],
            'location_label' => ['nullable', 'string', 'max:150'],
            'location_type' => ['nullable', 'string', 'max:50'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable', 'boolean'],
        ];
    }
}
