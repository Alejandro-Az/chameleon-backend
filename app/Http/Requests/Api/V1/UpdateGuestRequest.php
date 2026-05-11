<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $eventId = Event::where('slug', $this->route('slug'))->value('id');
        $guestId = Guest::where('public_id', $this->route('guest'))->value('id');

        return [
            'name'             => ['sometimes', 'string', 'max:200'],
            'email'            => ['sometimes', 'nullable', 'email', 'max:200'],
            'phone'            => ['sometimes', 'nullable', 'string', 'max:50'],
            'invitation_code'  => ['sometimes', 'string', 'max:100', Rule::unique('guests', 'invitation_code')->where('event_id', $eventId)->ignore($guestId)],
            'invited_seats'    => ['sometimes', 'integer', 'min:1', 'max:50'],
            'seat_label'      => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
