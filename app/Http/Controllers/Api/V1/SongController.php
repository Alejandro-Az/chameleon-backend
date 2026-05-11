<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSongRequest;
use App\Http\Resources\EventSongResource;
use App\Models\Event;
use App\Services\SongService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SongController extends Controller
{
    public function __construct(private readonly SongService $songService) {}

    public function index(string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        return response()->json([
            'ok'   => true,
            'data' => EventSongResource::collection($this->songService->listForEvent($event)),
        ]);
    }

    public function store(StoreSongRequest $request, string $slug): JsonResponse
    {
        $event  = Event::where('slug', $slug)->firstOrFail();
        $result = $this->songService->suggest($event, $request->validated());

        if (is_string($result)) {
            return $this->businessError($result);
        }

        return response()->json(['ok' => true, 'data' => new EventSongResource($result)], 201);
    }

    public function destroy(Request $request, string $slug, string $song): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->songService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenResponse();
        }

        $this->songService->delete($event, $song);

        return response()->json(['ok' => true, 'data' => null]);
    }

    private function businessError(string $code): JsonResponse
    {
        $messages = [
            'INVALID_INVITATION' => 'No pudimos identificar su invitación. Use el enlace personal que recibió.',
            'DUPLICATE_SONG'     => 'Esa canción ya está en la lista.',
        ];

        return response()->json([
            'ok'    => false,
            'error' => [
                'code'    => $code,
                'message' => $messages[$code] ?? $code,
                'details' => null,
            ],
        ], 422);
    }

    private function forbiddenResponse(): JsonResponse
    {
        return response()->json([
            'ok'    => false,
            'error' => [
                'code'    => 'AUTH_FORBIDDEN',
                'message' => 'No tienes permiso sobre este evento.',
                'details' => null,
            ],
        ], 403);
    }
}
