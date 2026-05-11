<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Gift;
use App\Models\User;

class GiftService
{
    public function isOwner(User $user, Event $event): bool
    {
        return $event->owner_id === $user->id;
    }

    public function list(Event $event): \Illuminate\Database\Eloquent\Collection
    {
        return $event->gifts()->orderBy('display_order')->orderBy('id')->get();
    }

    public function create(Event $event, array $data): Gift
    {
        $data['display_order']     = $data['display_order'] ?? 0;
        $data['status']            = Gift::STATUS_PENDING;
        $data['quantity_reserved'] = 0;

        return $event->gifts()->create($data);
    }

    public function update(Gift $gift, array $data): Gift
    {
        $gift->update($data);

        return $gift->refresh();
    }

    public function findByPublicIdForEvent(Event $event, string $publicId): Gift
    {
        return $event->gifts()->where('public_id', $publicId)->firstOrFail();
    }

    public function delete(Gift $gift): void
    {
        $gift->delete();
    }
}
