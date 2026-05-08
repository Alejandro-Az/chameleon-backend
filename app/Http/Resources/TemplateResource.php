<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->public_id,
            'name'                 => $this->name,
            'event_type'           => $this->event_type->value,
            'default_module_order' => $this->default_module_order,
            'styles'               => $this->styles,
            'created_at'           => $this->created_at?->toIso8601String(),
        ];
    }
}
