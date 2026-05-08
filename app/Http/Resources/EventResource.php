<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug'     => $this->slug,
            'name'     => $this->name,
            'type'     => $this->type->value,
            'date'     => $this->date?->toDateString(),
            'status'   => $this->status,
            'template' => $this->whenLoaded('template', fn () => new TemplateResource($this->template)),
            'modules'  => $this->whenLoaded('moduleConfigs', fn () =>
                EventModuleConfigResource::collection($this->moduleConfigs)
            ),
            'owner'    => $this->whenLoaded('owner', fn () => [
                'id'   => $this->owner->public_id,
                'name' => $this->owner->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
