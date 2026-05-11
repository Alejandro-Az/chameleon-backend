<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RomanticPhraseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->public_id,
            'phrase'        => $this->phrase,
            'author'        => $this->author,
            'display_order' => $this->display_order,
            'is_enabled'    => $this->is_enabled,
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
