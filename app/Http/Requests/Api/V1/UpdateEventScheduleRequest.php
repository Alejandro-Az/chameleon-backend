<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'starts_at' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'ends_at' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s', 'after_or_equal:starts_at'],
            'location_label' => ['sometimes', 'nullable', 'string', 'max:150'],
            'location_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'display_order' => ['sometimes', 'integer', 'min:0'],
            'is_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
