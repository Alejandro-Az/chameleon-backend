<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;

class AttendanceService
{
    public function isOwner(User $user, Event $event): bool
    {
        return $event->owner_id === $user->id;
    }

    public function listCheckedIn(Event $event): \Illuminate\Database\Eloquent\Collection
    {
        return $event->guests()->whereNotNull('checked_in_at')->get();
    }

    public function findGuestForEvent(Event $event, string $publicId): Guest
    {
        return $event->guests()->where('public_id', $publicId)->firstOrFail();
    }

    /**
     * Stamp checked_in_at on a guest.
     *
     * @throws \RuntimeException when guest is already checked in
     */
    public function checkIn(Guest $guest): Guest
    {
        if ($guest->checked_in_at !== null) {
            throw new \RuntimeException('ATTENDANCE_ALREADY_CHECKED_IN');
        }

        $guest->update(['checked_in_at' => now()]);

        return $guest->refresh();
    }

    /**
     * Clear checked_in_at on a guest.
     *
     * @throws \RuntimeException when guest is not checked in
     */
    public function revertCheckIn(Guest $guest): Guest
    {
        if ($guest->checked_in_at === null) {
            throw new \RuntimeException('ATTENDANCE_NOT_CHECKED_IN');
        }

        $guest->update(['checked_in_at' => null]);

        return $guest->refresh();
    }
}
