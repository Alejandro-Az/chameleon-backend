<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:200'],
            'type'        => ['required', Rule::enum(EventType::class)],
            'template_id' => ['nullable', 'string', Rule::exists('templates', 'public_id')],
            'date'        => ['nullable', 'date', 'after:today'],
        ];
    }
}
