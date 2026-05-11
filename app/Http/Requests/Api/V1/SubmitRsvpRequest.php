<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitRsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        $allowedDietTags = [
            'vegano',
            'vegetariano',
            'sin_gluten',
            'diabetico',
            'sin_lactosa',
            'alergia_nueces',
        ];

        return [
            'invitation_code'     => ['required', 'string', 'max:100'],
            'rsvp_status'         => ['required', Rule::in(['yes', 'no', 'maybe'])],
            'guests_confirmed'    => [
                'nullable',
                'integer',
                'min:1',
                'max:20',
                Rule::requiredIf(fn () => in_array($this->input('rsvp_status'), ['yes', 'maybe'], true)),
            ],
            'rsvp_message'        => ['nullable', 'string', 'max:1000'],
            'show_in_public_list' => ['sometimes', 'boolean'],
            'dietary_tags'        => ['nullable', 'array'],
            'dietary_tags.*'      => ['string', Rule::in($allowedDietTags)],
            'dietary_notes'       => ['nullable', 'string', 'max:1000'],
        ];
    }
}
