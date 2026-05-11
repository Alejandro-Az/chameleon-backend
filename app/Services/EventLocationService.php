<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventLocation;
use App\Models\User;

class EventLocationService
{
    public function isOwner(User $user, Event $event): bool
    {
        return $event->owner_id === $user->id;
    }

    public function create(Event $event, array $data): EventLocation
    {
        $data['display_order'] = $data['display_order'] ?? 0;
        $data['is_enabled'] = $data['is_enabled'] ?? true;

        return $event->locations()->create($data);
    }

    public function update(EventLocation $location, array $data): EventLocation
    {
        $location->update($data);

        return $location->refresh();
    }

    public function findByPublicIdForEvent(Event $event, string $publicId): EventLocation
    {
        return $event->locations()->where('public_id', $publicId)->firstOrFail();
    }
}
