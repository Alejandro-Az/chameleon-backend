<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\User;

class EventScheduleService
{
    public function isOwner(User $user, Event $event): bool
    {
        return $event->owner_id === $user->id;
    }

    public function create(Event $event, array $data): EventSchedule
    {
        $data['display_order'] = $data['display_order'] ?? 0;
        $data['is_enabled'] = $data['is_enabled'] ?? true;

        return $event->schedules()->create($data);
    }

    public function update(EventSchedule $schedule, array $data): EventSchedule
    {
        $schedule->update($data);

        return $schedule->refresh();
    }

    public function findByPublicIdForEvent(Event $event, string $publicId): EventSchedule
    {
        return $event->schedules()->where('public_id', $publicId)->firstOrFail();
    }
}
