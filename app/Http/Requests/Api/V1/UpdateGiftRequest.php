<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'required', 'string', 'max:150'],
            'description'   => ['nullable', 'string', 'max:500'],
            'store_label'   => ['nullable', 'string', 'max:100'],
            'url'           => ['nullable', 'url', 'max:500'],
            'quantity'      => ['sometimes', 'required', 'integer', 'min:1', 'max:999'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'status'        => ['nullable', 'string', 'in:pending,reserved,purchased'],
        ];
    }
}
