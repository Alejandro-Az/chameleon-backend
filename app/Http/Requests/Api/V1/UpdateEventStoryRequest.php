<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventStoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'         => ['nullable', 'string', 'max:150'],
            'subtitle'      => ['nullable', 'string', 'max:255'],
            'body'          => ['nullable', 'string', 'max:5000'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_enabled'    => ['nullable', 'boolean'],
        ];
    }
}
