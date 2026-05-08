<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'max:200'],
            'date'        => ['sometimes', 'nullable', 'date', 'after:today'],
            'status'      => ['sometimes', Rule::in(['draft', 'published'])],
            'template_id' => ['sometimes', 'nullable', 'string', Rule::exists('templates', 'public_id')],
        ];
    }
}
