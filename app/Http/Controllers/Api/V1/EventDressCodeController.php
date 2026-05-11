<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEventDressCodeRequest;
use App\Http\Requests\Api\V1\UpdateEventDressCodeRequest;
use App\Http\Resources\EventDressCodeResource;
use App\Models\Event;
use App\Services\EventDressCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventDressCodeController extends Controller
{
    public function __construct(private readonly EventDressCodeService $dressCodeService) {}

    public function index(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->dressCodeService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        return response()->json([
            'ok' => true,
            'data' => EventDressCodeResource::collection($event->dressCodes()->orderBy('display_order')->get()),
        ]);
    }

    public function store(StoreEventDressCodeRequest $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->dressCodeService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $dressCode = $this->dressCodeService->create($event, $request->validated());

        return response()->json([
            'ok' => true,
            'data' => new EventDressCodeResource($dressCode),
        ], 201);
    }

    public function update(UpdateEventDressCodeRequest $request, string $slug, string $id): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->dressCodeService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $dressCode = $this->dressCodeService->findByPublicIdForEvent($event, $id);
        $dressCode = $this->dressCodeService->update($dressCode, $request->validated());

        return response()->json([
            'ok' => true,
            'data' => new EventDressCodeResource($dressCode),
        ]);
    }

    public function destroy(Request $request, string $slug, string $id): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->dressCodeService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $dressCode = $this->dressCodeService->findByPublicIdForEvent($event, $id);
        $dressCode->delete();

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
