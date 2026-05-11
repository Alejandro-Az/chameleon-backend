<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EventPhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->public_id,
            'type'          => $this->type,
            'caption'       => $this->caption,
            'status'        => $this->status,
            'display_order' => $this->display_order,
            'file_url'      => Storage::disk('public')->url($this->file_path),
            'thumbnail_url' => $this->thumbnail_path
                ? Storage::disk('public')->url($this->thumbnail_path)
                : null,
            'uploaded_by_guest' => $this->guest_id !== null,
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }
}
