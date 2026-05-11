<?php

namespace App\Services;

use App\Enums\ModuleKey;
use App\Models\Event;
use App\Models\EventModuleConfig;
use App\Models\EventSong;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class SongService
{
    public function listForEvent(Event $event): Collection
    {
        return $event->songs()->where('status', EventSong::STATUS_APPROVED)->get();
    }

    /**
     * Suggest a song for an event.
     *
     * Returns the created EventSong, or a string error code on business failure.
     *
     * @return EventSong|string
     */
    public function suggest(Event $event, array $data): EventSong|string
    {
        $guest = Guest::where('event_id', $event->id)
            ->where('invitation_code', $data['invitation_code'])
            ->first();

        if (! $guest) {
            return 'INVALID_INVITATION';
        }

        $title  = trim($data['title']);
        $artist = $data['artist'] ?? null;

        $duplicate = EventSong::where('event_id', $event->id)
            ->where('title', $title)
            ->where('artist', $artist)
            ->where('status', '!=', EventSong::STATUS_REJECTED)
            ->first();

        if ($duplicate) {
            return 'DUPLICATE_SONG';
        }

        $autoApprove = EventModuleConfig::where('event_id', $event->id)
            ->where('module_key', ModuleKey::Songs->value)
            ->value('auto_approve');

        // Si no existe configuración del módulo, se aprueba por defecto.
        $status = ($autoApprove === false)
            ? EventSong::STATUS_PENDING
            : EventSong::STATUS_APPROVED;

        return EventSong::create([
            'event_id'              => $event->id,
            'suggested_by_guest_id' => $guest->id,
            'title'                 => $title,
            'artist'                => $artist,
            'url'                   => $data['url'] ?? null,
            'message_for_couple'    => $data['message_for_couple'] ?? null,
            'show_author'           => $data['show_author'] ?? true,
            'status'                => $status,
            'votes_count'           => 0,
        ]);
    }

    public function delete(Event $event, string $publicId): void
    {
        $song = EventSong::where('event_id', $event->id)
            ->where('public_id', $publicId)
            ->firstOrFail();

        $song->delete();
    }

    public function isOwner(User $user, Event $event): bool
    {
        return $event->owner_id === $user->id;
    }
}
