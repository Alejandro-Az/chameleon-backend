<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventModuleConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'module_key' => $this->module_key->value,
            'enabled'    => $this->enabled,
            'order'      => $this->order,
        ];
    }
}
