<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->public_id,
            'name'              => $this->name,
            'description'       => $this->description,
            'store_label'       => $this->store_label,
            'url'               => $this->url,
            'quantity'          => $this->quantity,
            'quantity_reserved' => $this->quantity_reserved,
            'available_units'   => $this->available_units,
            'status'            => $this->status,
            'display_order'     => $this->display_order,
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
        ];
    }
}
