<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $eventId = Event::where('slug', $this->route('slug'))->value('id');

        return [
            'name'             => ['required', 'string', 'max:200'],
            'email'            => ['nullable', 'email', 'max:200'],
            'phone'            => ['nullable', 'string', 'max:50'],
            'invitation_code'  => ['required', 'string', 'max:100', Rule::unique('guests', 'invitation_code')->where('event_id', $eventId)],
            'invited_seats'    => ['sometimes', 'integer', 'min:1', 'max:50'],
            'seat_label'       => ['nullable', 'string', 'max:100'],
        ];
    }
}
