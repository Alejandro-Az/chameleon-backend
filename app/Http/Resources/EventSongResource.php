<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventSongResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->public_id,
            'title'              => $this->title,
            'artist'             => $this->artist,
            'url'                => $this->url,
            'message_for_couple' => $this->message_for_couple,
            'show_author'        => $this->show_author,
            'suggested_by_name'  => ($this->show_author && $this->suggestedBy)
                ? $this->suggestedBy->name
                : null,
            'votes_count'        => $this->votes_count,
            'status'             => $this->status,
            'created_at'         => $this->created_at?->toIso8601String(),
        ];
    }
}
