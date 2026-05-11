<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEventPhotoRequest;
use App\Http\Resources\EventPhotoResource;
use App\Models\Event;
use App\Services\GalleryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function __construct(private readonly GalleryService $galleryService) {}

    /**
     * @OA\Get(
     *     path="/api/v1/events/{slug}/gallery",
     *     tags={"Gallery"},
     *     summary="List approved gallery photos for an event",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="OK", @OA\JsonContent(
     *         @OA\Property(property="ok", type="boolean", example=true),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Event not found")
     * )
     */
    public function index(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->galleryService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenResponse();
        }

        $photos = $this->galleryService->list($event);

        return response()->json([
            'ok'   => true,
            'data' => EventPhotoResource::collection($photos)->response()->getData(true),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/events/{slug}/gallery",
     *     tags={"Gallery"},
     *     summary="Upload a photo to the event gallery",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(required={"photo"},
     *                 @OA\Property(property="photo", type="string", format="binary"),
     *                 @OA\Property(property="type", type="string", enum={"gallery","hero","dress_code","story"}),
     *                 @OA\Property(property="caption", type="string", maxLength=255),
     *                 @OA\Property(property="display_order", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(
     *         @OA\Property(property="ok", type="boolean", example=true),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreEventPhotoRequest $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->galleryService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenResponse();
        }

        $photo = $this->galleryService->upload($event, $request->file('photo'), $request->validated());

        return response()->json(['ok' => true, 'data' => new EventPhotoResource($photo)], 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/events/{slug}/gallery/{photo}",
     *     tags={"Gallery"},
     *     summary="Delete a photo from the event gallery",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="photo", in="path", required=true, description="Photo public_id (ULID)", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(
     *         @OA\Property(property="ok", type="boolean", example=true),
     *         @OA\Property(property="data", nullable=true, example=null)
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Photo not found")
     * )
     */
    public function destroy(Request $request, string $slug, string $photoId): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->galleryService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenResponse();
        }

        $photo = $this->galleryService->findByPublicId($event, $photoId);
        $this->galleryService->delete($photo);

        return response()->json(['ok' => true, 'data' => null]);
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
