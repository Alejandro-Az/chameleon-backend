<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEventStoryRequest;
use App\Http\Requests\Api\V1\UpdateEventStoryRequest;
use App\Http\Resources\EventStoryResource;
use App\Models\Event;
use App\Services\EventStoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    public function __construct(private readonly EventStoryService $storyService) {}

    /**
     * @OA\Get(
     *     path="/api/v1/events/{slug}/story",
     *     tags={"Stories"},
     *     summary="Listar historias del evento",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string", example="boda-ana-y-luis")),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="string", example="01KHN2Y1XYWPBEPJGB1104GDZW"),
     *                 @OA\Property(property="title", type="string", nullable=true, example="Cómo nos conocimos"),
     *                 @OA\Property(property="subtitle", type="string", nullable=true, example="Una historia de amor"),
     *                 @OA\Property(property="body", type="string", example="Era una noche de verano..."),
     *                 @OA\Property(property="display_order", type="integer", example=0),
     *                 @OA\Property(property="is_enabled", type="boolean", example=true)
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permiso"),
     *     @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function index(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->storyService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenResponse();
        }

        return response()->json([
            'ok'   => true,
            'data' => EventStoryResource::collection($event->stories()->orderBy('display_order')->get()),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/events/{slug}/story",
     *     tags={"Stories"},
     *     summary="Crear historia en el evento",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string", example="boda-ana-y-luis")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"body"},
     *             @OA\Property(property="title", type="string", nullable=true, maxLength=150, example="Cómo nos conocimos"),
     *             @OA\Property(property="subtitle", type="string", nullable=true, maxLength=255, example="Una historia de amor"),
     *             @OA\Property(property="body", type="string", maxLength=5000, example="Era una noche de verano..."),
     *             @OA\Property(property="display_order", type="integer", nullable=true, example=0),
     *             @OA\Property(property="is_enabled", type="boolean", nullable=true, example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validación"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permiso"),
     *     @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function store(StoreEventStoryRequest $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->storyService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenResponse();
        }

        $story = $this->storyService->create($event, $request->validated());

        return response()->json(['ok' => true, 'data' => new EventStoryResource($story)], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/events/{slug}/story/{id}",
     *     tags={"Stories"},
     *     summary="Actualizar historia del evento",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string", example="boda-ana-y-luis")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", example="01KHN2Y1XYWPBEPJGB1104GDZW")),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", nullable=true, maxLength=150, example="Título actualizado"),
     *             @OA\Property(property="subtitle", type="string", nullable=true, maxLength=255, example="Subtítulo actualizado"),
     *             @OA\Property(property="body", type="string", nullable=true, maxLength=5000, example="Cuerpo actualizado"),
     *             @OA\Property(property="display_order", type="integer", nullable=true, example=1),
     *             @OA\Property(property="is_enabled", type="boolean", nullable=true, example=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=422, description="Validación"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permiso"),
     *     @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function update(UpdateEventStoryRequest $request, string $slug, string $id): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->storyService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenResponse();
        }

        $story = $this->storyService->findByPublicIdForEvent($event, $id);
        $story = $this->storyService->update($story, $request->validated());

        return response()->json(['ok' => true, 'data' => new EventStoryResource($story)]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/events/{slug}/story/{id}",
     *     tags={"Stories"},
     *     summary="Eliminar historia del evento",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string", example="boda-ana-y-luis")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", example="01KHN2Y1XYWPBEPJGB1104GDZW")),
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permiso"),
     *     @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function destroy(Request $request, string $slug, string $id): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->storyService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenResponse();
        }

        $story = $this->storyService->findByPublicIdForEvent($event, $id);
        $story->delete();

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
