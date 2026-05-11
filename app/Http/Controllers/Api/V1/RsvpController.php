<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreGuestRequest;
use App\Http\Requests\Api\V1\SubmitRsvpRequest;
use App\Http\Requests\Api\V1\UpdateGuestRequest;
use App\Http\Resources\GuestResource;
use App\Models\Event;
use App\Services\RsvpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RsvpController extends Controller
{
    public function __construct(private readonly RsvpService $rsvpService) {}

    /**
     * @OA\Get(
     *     path="/api/v1/events/{slug}/guests",
     *     tags={"RSVP"},
     *     summary="Listar invitados de un evento",
    *     description="Devuelve la lista de invitados. Solo el propietario del evento puede acceder.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de invitados",
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

        if (! $this->rsvpService->isOwner($request->user('api'), $event)) {
            return $this->forbidden();
        }

        $guests = $this->rsvpService->listGuests($event);

        return response()->json(['ok' => true, 'data' => GuestResource::collection($guests)->response()->getData(true)['data']]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/events/{slug}/guests",
     *     tags={"RSVP"},
     *     summary="Crear invitado en un evento",
     *     description="El propietario del evento crea un nuevo invitado con su código de invitación.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","invitation_code"},
     *             @OA\Property(property="name", type="string", example="Ana García"),
     *             @OA\Property(property="email", type="string", format="email", example="ana@ejemplo.com"),
     *             @OA\Property(property="phone", type="string", example="+52 55 1234 5678"),
     *             @OA\Property(property="invitation_code", type="string", example="ANA2024"),
     *             @OA\Property(property="invited_seats", type="integer", example=2),
     *             @OA\Property(property="seat_label", type="string", example="Mesa 3")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Invitado creado",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/GuestResource")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(StoreGuestRequest $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->rsvpService->isOwner($request->user('api'), $event)) {
            return $this->forbidden();
        }

        $guest = $this->rsvpService->createGuest($event, $request->validated());

        return response()->json(['ok' => true, 'data' => new GuestResource($guest)], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/events/{slug}/guests/{id}",
     *     tags={"RSVP"},
     *     summary="Actualizar datos de un invitado",
     *     description="El propietario del evento actualiza nombre, email, asientos, etc. No modifica el estado RSVP.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, description="public_id del invitado", @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="invited_seats", type="integer"),
     *             @OA\Property(property="seat_label", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Invitado actualizado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Invitado no encontrado")
     * )
     */
    public function update(UpdateGuestRequest $request, string $slug, string $id): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->rsvpService->isOwner($request->user('api'), $event)) {
            return $this->forbidden();
        }

        $guest = $this->rsvpService->findGuestForEvent($event, $id);
        $guest = $this->rsvpService->updateGuest($guest, $request->validated());

        return response()->json(['ok' => true, 'data' => new GuestResource($guest)]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/events/{slug}/guests/{id}",
     *     tags={"RSVP"},
     *     summary="Eliminar un invitado",
     *     description="El propietario del evento elimina (soft-delete) un invitado.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, description="public_id del invitado", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Invitado eliminado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Invitado no encontrado")
     * )
     */
    public function destroy(Request $request, string $slug, string $id): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->rsvpService->isOwner($request->user('api'), $event)) {
            return $this->forbidden();
        }

        $guest = $this->rsvpService->findGuestForEvent($event, $id);
        $this->rsvpService->deleteGuest($guest);

        return response()->json(['ok' => true, 'data' => null]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/events/{slug}/rsvp",
     *     tags={"RSVP"},
     *     summary="Enviar o actualizar confirmación de asistencia",
     *     description="Endpoint público. El invitado envía su RSVP identificado por invitation_code.",
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"invitation_code","rsvp_status"},
     *             @OA\Property(property="invitation_code", type="string", example="ANA2024"),
     *             @OA\Property(property="rsvp_status", type="string", enum={"yes","no","maybe"}, example="yes"),
     *             @OA\Property(property="guests_confirmed", type="integer", example=2),
     *             @OA\Property(property="rsvp_message", type="string", example="Ahí estaremos!"),
     *             @OA\Property(property="show_in_public_list", type="boolean", example=true),
     *             @OA\Property(property="dietary_tags", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="dietary_notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="RSVP registrado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/GuestResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Código de invitación no encontrado"),
     *     @OA\Response(response=422, description="Error de validación o límite de asientos excedido")
     * )
     */
    public function submit(SubmitRsvpRequest $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        $result = $this->rsvpService->submitRsvp($event, $request->validated());

        if (! $result['found']) {
            return response()->json([
                'ok'    => false,
                'error' => [
                    'code'    => 'RSVP_INVITATION_NOT_FOUND',
                    'message' => 'No se encontró una invitación con ese código para este evento.',
                    'details' => null,
                ],
            ], 404);
        }

        if (! empty($result['seats_exceeded'])) {
            return response()->json([
                'ok'    => false,
                'error' => [
                    'code'    => 'RSVP_SEATS_EXCEEDED',
                    'message' => "Con esta invitación puede confirmar hasta {$result['max_seats']} persona(s).",
                    'details' => ['max_seats' => $result['max_seats']],
                ],
            ], 422);
        }

        return response()->json(['ok' => true, 'data' => new GuestResource($result['guest'])]);
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
