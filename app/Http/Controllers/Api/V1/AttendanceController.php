<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\GuestResource;
use App\Models\Event;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Attendance", description="Check-in de invitados a eventos")
 */
class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    /**
     * @OA\Get(
     *     path="/api/v1/events/{slug}/attendance",
     *     tags={"Attendance"},
     *     summary="Listar invitados con check-in registrado",
     *     description="Devuelve todos los invitados que ya realizaron check-in. Solo el propietario del evento puede acceder.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de invitados con check-in",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/GuestResource"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Evento no encontrado")
     * )
     */
    public function index(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();
        if (! $this->attendanceService->isOwner($request->user('api'), $event)) {
            return $this->forbidden();
        }
        $guests = $this->attendanceService->listCheckedIn($event);
        return response()->json(['ok' => true, 'data' => GuestResource::collection($guests)->resolve()]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/events/{slug}/attendance/{guest}",
     *     tags={"Attendance"},
     *     summary="Registrar check-in de un invitado",
     *     description="Marca checked_in_at con la hora actual. Solo el propietario del evento puede hacerlo.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="guest", in="path", required=true, description="public_id del invitado", @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Check-in registrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/GuestResource")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Invitado no encontrado"),
     *     @OA\Response(
     *         response=422,
     *         description="El invitado ya tiene check-in registrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="ATTENDANCE_ALREADY_CHECKED_IN"),
     *                 @OA\Property(property="message", type="string"),
     *                 @OA\Property(property="details", type="object", nullable=true)
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request, string $slug, string $guest): JsonResponse
    {
        $event    = Event::where('slug', $slug)->firstOrFail();
        if (! $this->attendanceService->isOwner($request->user('api'), $event)) {
            return $this->forbidden();
        }
        $guestModel = $this->attendanceService->findGuestForEvent($event, $guest);
        return $this->performAction(fn () => $this->attendanceService->checkIn($guestModel));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/events/{slug}/attendance/{guest}",
     *     tags={"Attendance"},
     *     summary="Revertir check-in de un invitado",
     *     description="Limpia checked_in_at. Solo el propietario del evento puede hacerlo.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="guest", in="path", required=true, description="public_id del invitado", @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Check-in revertido",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/GuestResource")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Invitado no encontrado"),
     *     @OA\Response(
     *         response=422,
     *         description="El invitado no tiene check-in registrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="ATTENDANCE_NOT_CHECKED_IN"),
     *                 @OA\Property(property="message", type="string"),
     *                 @OA\Property(property="details", type="object", nullable=true)
     *             )
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, string $slug, string $guest): JsonResponse
    {
        $event    = Event::where('slug', $slug)->firstOrFail();
        if (! $this->attendanceService->isOwner($request->user('api'), $event)) {
            return $this->forbidden();
        }
        $guestModel = $this->attendanceService->findGuestForEvent($event, $guest);
        return $this->performAction(fn () => $this->attendanceService->revertCheckIn($guestModel));
    }

    private function performAction(callable $action): JsonResponse
    {
        try {
            $guest = $action();
            return response()->json(['ok' => true, 'data' => new GuestResource($guest)]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'ok'    => false,
                'error' => [
                    'code'    => $e->getMessage(),
                    'message' => $this->errorMessage($e->getMessage()),
                    'details' => null,
                ],
            ], 422);
        }
    }

    private function errorMessage(string $code): string
    {
        return match ($code) {
            'ATTENDANCE_ALREADY_CHECKED_IN' => 'El invitado ya tiene check-in registrado.',
            'ATTENDANCE_NOT_CHECKED_IN'     => 'El invitado no tiene check-in registrado.',
            default                         => 'Error de asistencia.',
        };
    }

    private function forbidden(): JsonResponse
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
