<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiKeyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->public_id,
            'name'         => $this->name,
            'prefix'       => $this->prefix,
            'scopes'       => $this->scopes,
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'expires_at'   => $this->expires_at?->toIso8601String(),
            'revoked_at'   => $this->revoked_at?->toIso8601String(),
            'created_at'   => $this->created_at?->toIso8601String(),
        ];
    }
}
