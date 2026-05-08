<?php

namespace App\Docs\V1\Events;

use OpenApi\Attributes as OA;

final class EventsEndpoints
{
    #[OA\Get(
        path: '/api/v1/events/{slug}',
        tags: ['Events'],
        summary: 'Ver detalle de un evento (público)',
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
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'slug', type: 'string', example: 'boda-ana-y-luis'),
                                new OA\Property(property: 'name', type: 'string', example: 'Boda Ana y Luis'),
                                new OA\Property(property: 'type', type: 'string', enum: ['wedding', 'xv', 'graduation', 'birthday', 'other'], example: 'wedding'),
                                new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-12-15'),
                                new OA\Property(property: 'hero_path', type: 'string', nullable: true, example: 'events/boda-ana-y-luis/hero.jpg'),
                                new OA\Property(property: 'status', type: 'string', example: 'published'),
                                new OA\Property(
                                    property: 'template',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'public_id', type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW'),
                                        new OA\Property(property: 'name', type: 'string', example: 'Tuscan Garden'),
                                        new OA\Property(property: 'event_type', type: 'string', example: 'wedding'),
                                    ]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(): void {}

    #[OA\Get(
        path: '/api/v1/events',
        tags: ['Events'],
        summary: 'Listar eventos del usuario autenticado',
        security: [['bearerAuth' => []]],
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
                                    new OA\Property(property: 'slug', type: 'string', example: 'boda-ana-y-luis'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Boda Ana y Luis'),
                                    new OA\Property(property: 'type', type: 'string', enum: ['wedding', 'xv', 'graduation', 'birthday', 'other'], example: 'wedding'),
                                    new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-12-15'),
                                    new OA\Property(property: 'status', type: 'string', example: 'draft'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/api/v1/events',
        tags: ['Events'],
        summary: 'Crear evento',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'type', 'date'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Boda Ana y Luis'),
                    new OA\Property(property: 'type', type: 'string', enum: ['wedding', 'xv', 'graduation', 'birthday', 'other'], example: 'wedding'),
                    new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-12-15'),
                    new OA\Property(property: 'template_id', type: 'integer', nullable: true, example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'slug', type: 'string', example: 'boda-ana-y-luis'),
                                new OA\Property(property: 'name', type: 'string', example: 'Boda Ana y Luis'),
                                new OA\Property(property: 'type', type: 'string', example: 'wedding'),
                                new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-12-15'),
                                new OA\Property(property: 'status', type: 'string', example: 'draft'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validación', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(): void {}

    #[OA\Put(
        path: '/api/v1/events/{slug}',
        tags: ['Events'],
        summary: 'Actualizar evento',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'boda-ana-y-luis')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Boda Ana y Luis Actualizada'),
                    new OA\Property(property: 'date', type: 'string', format: 'date', nullable: true, example: '2026-12-20'),
                    new OA\Property(property: 'status', type: 'string', nullable: true, enum: ['draft', 'published', 'archived'], example: 'published'),
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
                                new OA\Property(property: 'slug', type: 'string', example: 'boda-ana-y-luis'),
                                new OA\Property(property: 'name', type: 'string', example: 'Boda Ana y Luis Actualizada'),
                                new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-12-20'),
                                new OA\Property(property: 'status', type: 'string', example: 'published'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validación', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(): void {}

    #[OA\Delete(
        path: '/api/v1/events/{slug}',
        tags: ['Events'],
        summary: 'Eliminar evento',
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
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(): void {}

    #[OA\Get(
        path: '/api/v1/events/{slug}/modules',
        tags: ['Events'],
        summary: 'Obtener módulos del evento',
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
                                    new OA\Property(property: 'module_key', type: 'string', example: 'rsvp'),
                                    new OA\Property(property: 'enabled', type: 'boolean', example: true),
                                    new OA\Property(property: 'order', type: 'integer', example: 1),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function getModules(): void {}

    #[OA\Put(
        path: '/api/v1/events/{slug}/modules',
        tags: ['Events'],
        summary: 'Actualizar módulos del evento',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'boda-ana-y-luis')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['modules'],
                properties: [
                    new OA\Property(
                        property: 'modules',
                        type: 'array',
                        items: new OA\Items(
                            required: ['module_key', 'enabled', 'order'],
                            properties: [
                                new OA\Property(
                                    property: 'module_key',
                                    type: 'string',
                                    enum: ['rsvp', 'gifts', 'songs', 'schedule', 'story', 'dress_code', 'gallery', 'romantic_phrases', 'attendance', 'location'],
                                    example: 'rsvp'
                                ),
                                new OA\Property(property: 'enabled', type: 'boolean', example: true),
                                new OA\Property(property: 'order', type: 'integer', example: 1),
                            ]
                        )
                    ),
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
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'module_key', type: 'string', example: 'rsvp'),
                                    new OA\Property(property: 'enabled', type: 'boolean', example: true),
                                    new OA\Property(property: 'order', type: 'integer', example: 1),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validación', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function updateModules(): void {}
}
