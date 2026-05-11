<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreRomanticPhraseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phrase'        => ['required', 'string', 'max:500'],
            'author'        => ['nullable', 'string', 'max:150'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_enabled'    => ['nullable', 'boolean'],
        ];
    }
}
