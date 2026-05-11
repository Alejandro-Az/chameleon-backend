<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventDressCode;
use App\Models\User;

class EventDressCodeService
{
    public function isOwner(User $user, Event $event): bool
    {
        return $event->owner_id === $user->id;
    }

    public function create(Event $event, array $data): EventDressCode
    {
        $data['display_order'] = $data['display_order'] ?? 0;
        $data['is_enabled'] = $data['is_enabled'] ?? true;

        return $event->dressCodes()->create($data);
    }

    public function update(EventDressCode $dressCode, array $data): EventDressCode
    {
        $dressCode->update($data);

        return $dressCode->refresh();
    }

    public function findByPublicIdForEvent(Event $event, string $publicId): EventDressCode
    {
        return $event->dressCodes()->where('public_id', $publicId)->firstOrFail();
    }
}
