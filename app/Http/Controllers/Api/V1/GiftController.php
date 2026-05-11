<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreGiftRequest;
use App\Http\Requests\Api\V1\UpdateGiftRequest;
use App\Http\Resources\GiftResource;
use App\Models\Event;
use App\Services\GiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GiftController extends Controller
{
    public function __construct(private readonly GiftService $giftService) {}

    public function index(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->giftService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        return response()->json([
            'ok'   => true,
            'data' => GiftResource::collection($this->giftService->list($event)),
        ]);
    }

    public function store(StoreGiftRequest $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->giftService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $gift = $this->giftService->create($event, $request->validated());

        return response()->json(['ok' => true, 'data' => new GiftResource($gift)], 201);
    }

    public function update(UpdateGiftRequest $request, string $slug, string $id): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->giftService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $gift = $this->giftService->findByPublicIdForEvent($event, $id);
        $gift = $this->giftService->update($gift, $request->validated());

        return response()->json(['ok' => true, 'data' => new GiftResource($gift)]);
    }

    public function destroy(Request $request, string $slug, string $id): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->giftService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $gift = $this->giftService->findByPublicIdForEvent($event, $id);
        $this->giftService->delete($gift);

        return response()->json(['ok' => true, 'data' => null]);
    }

    private function forbiddenEventResponse(): JsonResponse
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
