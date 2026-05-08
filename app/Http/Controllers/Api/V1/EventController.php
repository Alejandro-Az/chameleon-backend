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

    public function destroy(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->eventService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $event->delete();

        return response()->json(['ok' => true, 'data' => null]);
    }

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
