<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'status' => $this->status,
            'source' => $this->source,
            'starts_at' => $this->starts_at?->copy()->setTimezone($this->business_timezone)->toIso8601String(),
            'ends_at' => $this->ends_at?->copy()->setTimezone($this->business_timezone)->toIso8601String(),
            'business_timezone' => $this->business_timezone,
            'customer_notes' => $this->customer_notes,
            'internal_notes' => $this->when($request->user('api')?->can('appointments.bookings.manage') || $request->user('api')?->can('appointments.bookings.view_all'), $this->internal_notes),
            'cancellation_reason' => $this->cancellation_reason,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'no_show_marked_at' => $this->no_show_marked_at?->toIso8601String(),
            'service' => $this->whenLoaded('service', fn () => [
                'id' => $this->service?->public_id,
                'name' => $this->service?->name,
                'duration_minutes' => $this->service?->duration_minutes,
            ]),
            'assigned_employee' => $this->whenLoaded('assignedEmployee', fn () => [
                'id' => $this->assignedEmployee?->public_id,
                'name' => $this->assignedEmployee?->name,
                'email' => $this->assignedEmployee?->email,
            ]),
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->public_id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : null),
            'guest' => [
                'name' => $this->guest_name,
                'phone' => $this->guest_phone,
                'email' => $this->guest_email,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
