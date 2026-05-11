<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreRomanticPhraseRequest;
use App\Http\Requests\Api\V1\UpdateRomanticPhraseRequest;
use App\Http\Resources\RomanticPhraseResource;
use App\Models\Event;
use App\Services\RomanticPhraseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class RomanticPhraseController extends Controller
{
    public function __construct(private readonly RomanticPhraseService $phraseService) {}

    #[OA\Get(
        path: '/api/v1/events/{slug}/romantic-phrases',
        tags: ['Romantic Phrases'],
        summary: 'Listar frases románticas de un evento',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'boda-ana-y-luis')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', example: '01HXYZ...'),
                                    new OA\Property(property: 'phrase', type: 'string', example: 'Eres mi hoy y todo mi mañana.'),
                                    new OA\Property(property: 'author', type: 'string', nullable: true, example: 'Pablo Neruda'),
                                    new OA\Property(property: 'display_order', type: 'integer', example: 0),
                                    new OA\Property(property: 'is_enabled', type: 'boolean', example: true),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso sobre el evento', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Evento no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->phraseService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        return response()->json([
            'ok'   => true,
            'data' => RomanticPhraseResource::collection($event->romanticPhrases()->orderBy('display_order')->get()),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/events/{slug}/romantic-phrases',
        tags: ['Romantic Phrases'],
        summary: 'Crear frase romántica para un evento',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'boda-ana-y-luis')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['phrase'],
                properties: [
                    new OA\Property(property: 'phrase', type: 'string', maxLength: 500, example: 'Eres mi hoy y todo mi mañana.'),
                    new OA\Property(property: 'author', type: 'string', maxLength: 150, nullable: true, example: 'Pablo Neruda'),
                    new OA\Property(property: 'display_order', type: 'integer', minimum: 0, example: 0),
                    new OA\Property(property: 'is_enabled', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Creado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', example: '01HXYZ...'),
                                new OA\Property(property: 'phrase', type: 'string', example: 'Eres mi hoy y todo mi mañana.'),
                                new OA\Property(property: 'author', type: 'string', nullable: true, example: 'Pablo Neruda'),
                                new OA\Property(property: 'display_order', type: 'integer', example: 0),
                                new OA\Property(property: 'is_enabled', type: 'boolean', example: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso sobre el evento', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validación fallida', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreRomanticPhraseRequest $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->phraseService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $phrase = $this->phraseService->create($event, $request->validated());

        return response()->json(['ok' => true, 'data' => new RomanticPhraseResource($phrase)], 201);
    }

    #[OA\Put(
        path: '/api/v1/events/{slug}/romantic-phrases/{id}',
        tags: ['Romantic Phrases'],
        summary: 'Actualizar frase romántica',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'boda-ana-y-luis')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: '01HXYZ...')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'phrase', type: 'string', maxLength: 500, example: 'Frase actualizada.'),
                    new OA\Property(property: 'author', type: 'string', maxLength: 150, nullable: true, example: 'Anónimo'),
                    new OA\Property(property: 'display_order', type: 'integer', minimum: 0, example: 1),
                    new OA\Property(property: 'is_enabled', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', example: '01HXYZ...'),
                                new OA\Property(property: 'phrase', type: 'string', example: 'Frase actualizada.'),
                                new OA\Property(property: 'author', type: 'string', nullable: true, example: 'Anónimo'),
                                new OA\Property(property: 'display_order', type: 'integer', example: 1),
                                new OA\Property(property: 'is_enabled', type: 'boolean', example: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso sobre el evento', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Frase no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validación fallida', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateRomanticPhraseRequest $request, string $slug, string $id): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->phraseService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $phrase = $this->phraseService->findByPublicIdForEvent($event, $id);
        $phrase = $this->phraseService->update($phrase, $request->validated());

        return response()->json(['ok' => true, 'data' => new RomanticPhraseResource($phrase)]);
    }

    #[OA\Delete(
        path: '/api/v1/events/{slug}/romantic-phrases/{id}',
        tags: ['Romantic Phrases'],
        summary: 'Eliminar frase romántica',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'boda-ana-y-luis')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: '01HXYZ...')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'data', nullable: true, example: null),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso sobre el evento', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Frase no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Request $request, string $slug, string $id): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->phraseService->isOwner($request->user('api'), $event)) {
            return $this->forbiddenEventResponse();
        }

        $phrase = $this->phraseService->findByPublicIdForEvent($event, $id);
        $phrase->delete();

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
