<?php
// app/Http/Controllers/Api/V1/EventController.php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEventRequest;
use App\Http\Requests\Api\V1\UpdateEventRequest;
use App\Http\Requests\Api\V1\UpdateModulesRequest;
use App\Http\Resources\EventModuleConfigResource;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Template;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(private readonly EventService $eventService) {}

    /**
     * @OA\Get(
     *     path="/api/v1/events/{slug}",
     *     tags={"Eventos"},
     *     summary="Ver evento público por slug",
     *     description="Acceso público sin autenticación.",
     *     @OA\Parameter(name="slug", in="path", required=true,
     *         @OA\Schema(type="string", example="boda-garcia-2026")),
     *     @OA\Response(
     *         response=200,
     *         description="Datos del evento",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="slug", type="string"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="type", type="string", enum={"wedding","xv","graduation","birthday","other"}),
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="hero_path", type="string", nullable=true),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="template", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Evento no encontrado")
     * )
     */
    public function show(string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)
            ->with(['template', 'moduleConfigs'])
            ->firstOrFail();

        return response()->json([
            'ok'   => true,
            'data' => new EventResource($event),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/events",
     *     tags={"Eventos"},
     *     summary="Listar mis eventos",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de eventos del usuario autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="slug", type="string"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="type", type="string"),
     *                     @OA\Property(property="date", type="string", format="date"),
     *                     @OA\Property(property="status", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $events = Event::where('owner_id', $request->user('api')->id)
            ->with('template')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'ok'   => true,
            'data' => EventResource::collection($events),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/events",
     *     tags={"Eventos"},
     *     summary="Crear evento",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","type","date"},
     *             @OA\Property(property="name", type="string", example="Boda García-López"),
     *             @OA\Property(property="type", type="string",
     *                 enum={"wedding","xv","graduation","birthday","other"},
     *                 example="wedding"),
     *             @OA\Property(property="date", type="string", format="date", example="2026-12-15"),
     *             @OA\Property(property="template_id", type="integer", nullable=true, example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Evento creado",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="slug", type="string", example="boda-garcia-lopez-2026"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="status", type="string", example="draft")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validación fallida"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['template_id'])) {
            $template = Template::where('public_id', $data['template_id'])->first();
            $data['template_id'] = $template?->id;
        }

        $event = $this->eventService->create($request->user('api'), $data);
        $event->load(['template', 'moduleConfigs']);

        return response()->json([
            'ok'   => true,
            'data' => new EventResource($event),
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/events/{slug}",
     *     tags={"Eventos"},
     *     summary="Actualizar evento",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="slug", in="path", required=true,
     *         @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="date", type="string", format="date"),
     *             @OA\Property(property="status", type="string",
     *                 enum={"draft","published","archived"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Evento actualizado"),
     *     @OA\Response(response=403, description="Sin permiso — no eres el organizador"),
     *     @OA\Response(response=404, description="Evento no encontrado"),
     *     @OA\Response(response=422, description="Validación fallida")
     * )
     */
    public function update(UpdateEventRequest $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->eventService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $data = $request->validated();

        if (array_key_exists('template_id', $data) && $data['template_id'] !== null) {
            $template = Template::where('public_id', $data['template_id'])->first();
            $data['template_id'] = $template?->id;
        }

        $event->update($data);

        return response()->json([
            'ok'   => true,
            'data' => new EventResource($event->fresh(['template', 'moduleConfigs'])),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/events/{slug}",
     *     tags={"Eventos"},
     *     summary="Eliminar evento",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="slug", in="path", required=true,
     *         @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Evento eliminado"),
     *     @OA\Response(response=403, description="Sin permiso"),
     *     @OA\Response(response=404, description="Evento no encontrado")
     * )
     */
    public function destroy(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->eventService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $event->delete();

        return response()->json(['ok' => true, 'data' => null]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/events/{slug}/modules",
     *     tags={"Eventos"},
     *     summary="Obtener configuración de módulos del evento",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Módulos del evento",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="module_key", type="string", example="rsvp"),
     *                     @OA\Property(property="enabled", type="boolean"),
     *                     @OA\Property(property="order", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Evento no encontrado")
     * )
     */
    public function getModules(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->eventService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        return response()->json([
            'ok'   => true,
            'data' => EventModuleConfigResource::collection($event->moduleConfigs),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/events/{slug}/modules",
     *     tags={"Eventos"},
     *     summary="Actualizar módulos del evento",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="modules", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="module_key", type="string",
     *                         enum={"rsvp","gifts","songs","schedule","story","dress_code",
     *                               "gallery","romantic_phrases","attendance","location"}),
     *                     @OA\Property(property="enabled", type="boolean"),
     *                     @OA\Property(property="order", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Módulos actualizados"),
     *     @OA\Response(response=403, description="Sin permiso"),
     *     @OA\Response(response=422, description="Validación fallida")
     * )
     */
    public function updateModules(UpdateModulesRequest $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->eventService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $this->eventService->updateModules($event, $request->validated('modules'));

        return response()->json([
            'ok'   => true,
            'data' => EventModuleConfigResource::collection($event->moduleConfigs()->get()),
        ]);
    }

    private function forbiddenEventResponse(): JsonResponse
    {
        return response()->json([
            'ok'    => false,
            'error' => ['code' => 'AUTH_FORBIDDEN', 'message' => 'No tienes permiso sobre este evento.', 'details' => null],
        ], 403);
    }
}
