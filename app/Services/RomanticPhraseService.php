<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventRomanticPhrase;
use App\Models\User;

class RomanticPhraseService
{
    public function isOwner(User $user, Event $event): bool
    {
        return $event->owner_id === $user->id;
    }

    public function create(Event $event, array $data): EventRomanticPhrase
    {
        $data['display_order'] = $data['display_order'] ?? 0;
        $data['is_enabled']    = $data['is_enabled'] ?? true;

        return $event->romanticPhrases()->create($data);
    }

    public function update(EventRomanticPhrase $phrase, array $data): EventRomanticPhrase
    {
        $phrase->update($data);

        return $phrase->refresh();
    }

    public function findByPublicIdForEvent(Event $event, string $publicId): EventRomanticPhrase
    {
        return $event->romanticPhrases()->where('public_id', $publicId)->firstOrFail();
    }
}
