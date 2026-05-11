<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->public_id,
            'name'                => $this->name,
            'email'               => $this->email,
            'phone'               => $this->phone,
            'invitation_code'     => $this->invitation_code,
            'invited_seats'       => $this->invited_seats,
            'rsvp_status'         => $this->rsvp_status,
            'guests_confirmed'    => $this->guests_confirmed,
            'rsvp_message'        => $this->rsvp_message,
            'show_in_public_list' => (bool) $this->show_in_public_list,
            'dietary_tags'        => $this->dietary_tags ?? [],
            'dietary_notes'       => $this->dietary_notes,
            'seat_label'          => $this->seat_label,
            'checked_in_at'       => $this->checked_in_at?->toIso8601String(),
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),
        ];
    }
}
