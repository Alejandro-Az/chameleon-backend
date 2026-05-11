<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RsvpService
{
    /**
     * Determine if the given user owns the event.
     */
    public function isOwner(User $user, Event $event): bool
    {
        return $event->owner_id === $user->id;
    }

    /**
     * List all guests for an event (paginated).
     */
    public function listGuests(Event $event, int $perPage = 20): LengthAwarePaginator
    {
        return $event->guests()->orderBy('name')->paginate($perPage);
    }

    /**
     * Create a guest entry for an event (master action).
     */
    public function createGuest(Event $event, array $data): Guest
    {
        $data['invited_seats'] = $data['invited_seats'] ?? 1;

        return $event->guests()->create($data)->refresh();
    }

    /**
     * Update a guest's non-RSVP data (master action).
     */
    public function updateGuest(Guest $guest, array $data): Guest
    {
        $guest->update($data);

        return $guest->refresh();
    }

    /**
     * Delete a guest (master action).
     */
    public function deleteGuest(Guest $guest): void
    {
        $guest->delete();
    }

    /**
     * Find a guest by public_id scoped to an event.
     */
    public function findGuestForEvent(Event $event, string $publicId): Guest
    {
        return $event->guests()->where('public_id', $publicId)->firstOrFail();
    }

    /**
     * Process an RSVP submission from a guest (public action, identified by invitation_code).
     *
     * @throws ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    public function submitRsvp(Event $event, array $data): array
    {
        $guest = Guest::query()
            ->where('event_id', $event->id)
            ->where('invitation_code', $data['invitation_code'])
            ->first();

        if (! $guest) {
            return ['found' => false, 'guest' => null, 'first_response' => false];
        }

        $wasFirstResponse = ($guest->rsvp_status === Guest::RSVP_PENDING);

        // Enforce seat cap when confirming
        if (in_array($data['rsvp_status'], [Guest::RSVP_YES, Guest::RSVP_MAYBE], true)) {
            $maxSeats = (int) ($guest->invited_seats ?? 0);

            if ($maxSeats > 0) {
                $requested = (int) ($data['guests_confirmed'] ?? 1);

                if ($requested > $maxSeats) {
                    return [
                        'found'           => true,
                        'guest'           => null,
                        'first_response'  => false,
                        'seats_exceeded'  => true,
                        'max_seats'       => $maxSeats,
                    ];
                }
            }
        }

        $guest->rsvp_status = $data['rsvp_status'];

        $guest->guests_confirmed = ($data['rsvp_status'] === Guest::RSVP_NO)
            ? 0
            : (int) ($data['guests_confirmed'] ?? 1);

        $guest->rsvp_message        = $data['rsvp_message'] ?? null;
        if (array_key_exists('show_in_public_list', $data)) {
            $guest->show_in_public_list = (bool) $data['show_in_public_list'];
        }

        $dietTags = $data['dietary_tags'] ?? [];
        $dietTags = is_array($dietTags) ? array_values(array_unique($dietTags)) : [];
        $guest->dietary_tags  = $dietTags;
        $guest->dietary_notes = $data['dietary_notes'] ?? null;

        $guest->save();

        return [
            'found'          => true,
            'guest'          => $guest->refresh(),
            'first_response' => $wasFirstResponse,
            'seats_exceeded' => false,
        ];
    }
}
