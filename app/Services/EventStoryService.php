<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventStory;
use App\Models\User;

class EventStoryService
{
    public function isOwner(User $user, Event $event): bool
    {
        return $event->owner_id === $user->id;
    }

    public function create(Event $event, array $data): EventStory
    {
        $data['display_order'] = $data['display_order'] ?? 0;
        $data['is_enabled']    = $data['is_enabled'] ?? true;

        return $event->stories()->create($data);
    }

    public function update(EventStory $story, array $data): EventStory
    {
        $story->update($data);

        return $story->refresh();
    }

    public function findByPublicIdForEvent(Event $event, string $publicId): EventStory
    {
        return $event->stories()->where('public_id', $publicId)->firstOrFail();
    }
}
