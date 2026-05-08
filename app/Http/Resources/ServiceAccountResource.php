<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->public_id,
            'type'       => $this->type,
            'name'       => $this->name,
            'email'      => $this->email,
            'status'     => $this->status,
            'meta'       => $this->meta,
            'roles'      => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
