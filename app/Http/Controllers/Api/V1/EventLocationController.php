<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEventLocationRequest;
use App\Http\Requests\Api\V1\UpdateEventLocationRequest;
use App\Http\Resources\EventLocationResource;
use App\Models\Event;
use App\Services\EventLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventLocationController extends Controller
{
    public function __construct(private readonly EventLocationService $locationService) {}

    public function index(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->locationService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        return response()->json([
            'ok' => true,
            'data' => EventLocationResource::collection($event->locations()->orderBy('display_order')->get()),
        ]);
    }

    public function store(StoreEventLocationRequest $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->locationService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $location = $this->locationService->create($event, $request->validated());

        return response()->json([
            'ok' => true,
            'data' => new EventLocationResource($location),
        ], 201);
    }

    public function update(UpdateEventLocationRequest $request, string $slug, string $id): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->locationService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $location = $this->locationService->findByPublicIdForEvent($event, $id);
        $location = $this->locationService->update($location, $request->validated());

        return response()->json([
            'ok' => true,
            'data' => new EventLocationResource($location),
        ]);
    }

    public function destroy(Request $request, string $slug, string $id): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->locationService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $location = $this->locationService->findByPublicIdForEvent($event, $id);
        $location->delete();

        return response()->json(['ok' => true, 'data' => null]);
    }

    private function forbiddenEventResponse(): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'error' => [
                'code' => 'AUTH_FORBIDDEN',
                'message' => 'No tienes permiso sobre este evento.',
                'details' => null,
            ],
        ], 403);
    }
}
