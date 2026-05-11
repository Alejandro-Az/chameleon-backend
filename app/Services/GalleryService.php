<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GalleryService
{
    public function isOwner(User $user, Event $event): bool
    {
        return $event->owner_id === $user->id;
    }

    public function list(Event $event): LengthAwarePaginator
    {
        return $event->photos()
            ->approved()
            ->ofType(EventPhoto::TYPE_GALLERY)
            ->orderBy('display_order')
            ->paginate(24);
    }

    public function upload(Event $event, UploadedFile $file, array $data): EventPhoto
    {
        $type = $data['type'] ?? EventPhoto::TYPE_GALLERY;

        $directory = "events/{$event->id}/photos/originals";
        $filename  = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath  = $file->storeAs($directory, $filename, 'public');

        $displayOrder = $data['display_order'] ?? $this->nextDisplayOrder($event, $type);

        return $event->photos()->create([
            'type'          => $type,
            'file_path'     => $filePath,
            'thumbnail_path'=> null,
            'caption'       => $data['caption'] ?? null,
            'status'        => EventPhoto::STATUS_APPROVED,
            'display_order' => $displayOrder,
        ]);
    }

    public function findByPublicId(Event $event, string $publicId): EventPhoto
    {
        return $event->photos()->where('public_id', $publicId)->firstOrFail();
    }

    public function delete(EventPhoto $photo): void
    {
        Storage::disk('public')->delete($photo->file_path);

        if ($photo->thumbnail_path) {
            Storage::disk('public')->delete($photo->thumbnail_path);
        }

        $photo->delete();
    }

    private function nextDisplayOrder(Event $event, string $type): int
    {
        return (int) $event->photos()
            ->where('type', $type)
            ->max('display_order') + 1;
    }
}
