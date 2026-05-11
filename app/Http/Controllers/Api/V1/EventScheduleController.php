<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEventScheduleRequest;
use App\Http\Requests\Api\V1\UpdateEventScheduleRequest;
use App\Http\Resources\EventScheduleResource;
use App\Models\Event;
use App\Services\EventScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventScheduleController extends Controller
{
    public function __construct(private readonly EventScheduleService $scheduleService) {}

    public function index(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->scheduleService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        return response()->json([
            'ok' => true,
            'data' => EventScheduleResource::collection($event->schedules()->orderBy('display_order')->get()),
        ]);
    }

    public function store(StoreEventScheduleRequest $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->scheduleService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $schedule = $this->scheduleService->create($event, $request->validated());

        return response()->json([
            'ok' => true,
            'data' => new EventScheduleResource($schedule),
        ], 201);
    }

    public function update(UpdateEventScheduleRequest $request, string $slug, string $id): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->scheduleService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $schedule = $this->scheduleService->findByPublicIdForEvent($event, $id);
        $schedule = $this->scheduleService->update($schedule, $request->validated());

        return response()->json([
            'ok' => true,
            'data' => new EventScheduleResource($schedule),
        ]);
    }

    public function destroy(Request $request, string $slug, string $id): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->scheduleService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $schedule = $this->scheduleService->findByPublicIdForEvent($event, $id);
        $schedule->delete();

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
