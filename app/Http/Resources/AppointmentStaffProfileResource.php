<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentStaffProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'is_active' => $this->is_active,
            'can_receive_auto_assignment' => $this->can_receive_auto_assignment,
            'notes' => $this->notes,
            'user' => [
                'id' => $this->user?->public_id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'status' => $this->user?->status,
            ],
            'weekday_rules' => $this->whenLoaded('weekdayRules', fn () => $this->weekdayRules->map(fn ($rule) => [
                'weekday' => $rule->weekday,
                'is_active' => $rule->is_active,
                'start_time' => $rule->start_time,
                'end_time' => $rule->end_time,
            ])->values()),
            'exceptions' => $this->whenLoaded('exceptions', fn () => $this->exceptions->map(fn ($exception) => [
                'id' => $exception->public_id,
                'name' => $exception->name,
                'date' => $exception->exception_date?->toDateString(),
                'type' => $exception->type,
                'start_time' => $exception->start_time,
                'end_time' => $exception->end_time,
                'is_active' => $exception->is_active,
            ])->values()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
