<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreSongRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // endpoint público — validación de invitado en Service
    }

    public function rules(): array
    {
        return [
            'invitation_code'    => ['required', 'string'],
            'title'              => ['required', 'string', 'max:150'],
            'artist'             => ['nullable', 'string', 'max:150'],
            'url'                => ['nullable', 'url', 'max:255'],
            'message_for_couple' => ['nullable', 'string', 'max:500'],
            'show_author'        => ['sometimes', 'boolean'],
        ];
    }
}
