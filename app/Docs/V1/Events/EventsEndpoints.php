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
                                new OA\Property(property: 'type', type: 'string', enum: ['wedding', 'quinceanera', 'graduation', 'birthday', 'party', 'other'], example: 'wedding'),
                                new OA\Property(property: 'date', type: 'string', format: 'date', nullable: true, example: '2026-12-15'),
                                new OA\Property(property: 'hero_path', type: 'string', nullable: true, example: 'events/boda-ana-y-luis/hero.jpg'),
                                new OA\Property(property: 'status', type: 'string', example: 'published'),
                                new OA\Property(
                                    property: 'template',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW'),
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
                                    new OA\Property(property: 'type', type: 'string', enum: ['wedding', 'quinceanera', 'graduation', 'birthday', 'party', 'other'], example: 'wedding'),
                                    new OA\Property(property: 'date', type: 'string', format: 'date', nullable: true, example: '2026-12-15'),
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
                required: ['name', 'type'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Boda Ana y Luis'),
                    new OA\Property(property: 'type', type: 'string', enum: ['wedding', 'quinceanera', 'graduation', 'birthday', 'party', 'other'], example: 'wedding'),
                    new OA\Property(property: 'date', type: 'string', format: 'date', nullable: true, example: '2026-12-15'),
                    new OA\Property(property: 'template_id', type: 'string', nullable: true, example: '01KHN2Y1XYWPBEPJGB1104GDZW'),
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
                                new OA\Property(property: 'date', type: 'string', format: 'date', nullable: true, example: '2026-12-15'),
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
                    new OA\Property(property: 'status', type: 'string', nullable: true, enum: ['draft', 'published'], example: 'published'),
                    new OA\Property(property: 'template_id', type: 'string', nullable: true, example: '01KHN2Y1XYWPBEPJGB1104GDZW'),
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
                                new OA\Property(property: 'date', type: 'string', format: 'date', nullable: true, example: '2026-12-20'),
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
                        new OA\Property(property: 'data', nullable: true, example: null),
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

    #[OA\Get(
        path: '/api/v1/events/{slug}/schedules',
        tags: ['Events'],
        summary: 'Listar actividades (schedule) del evento',
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
                                    new OA\Property(property: 'id', type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW'),
                                    new OA\Property(property: 'title', type: 'string', example: 'Ceremonia'),
                                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Ceremonia religiosa'),
                                    new OA\Property(property: 'starts_at', type: 'string', example: '2026-12-15 17:00:00'),
                                    new OA\Property(property: 'ends_at', type: 'string', nullable: true, example: '2026-12-15 18:00:00'),
                                    new OA\Property(property: 'location_label', type: 'string', nullable: true, example: 'Parroquia San Miguel'),
                                    new OA\Property(property: 'location_type', type: 'string', nullable: true, example: 'ceremony'),
                                    new OA\Property(property: 'display_order', type: 'integer', example: 0),
                                    new OA\Property(property: 'is_enabled', type: 'boolean', example: true),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function listSchedules(): void {}

    #[OA\Post(
        path: '/api/v1/events/{slug}/schedules',
        tags: ['Events'],
        summary: 'Crear actividad (schedule) en el evento',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'boda-ana-y-luis')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'starts_at'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 150, example: 'Ceremonia'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 500, example: 'Ceremonia religiosa'),
                    new OA\Property(property: 'starts_at', type: 'string', example: '2026-12-15 17:00:00'),
                    new OA\Property(property: 'ends_at', type: 'string', nullable: true, example: '2026-12-15 18:00:00'),
                    new OA\Property(property: 'location_label', type: 'string', nullable: true, maxLength: 150, example: 'Parroquia San Miguel'),
                    new OA\Property(property: 'location_type', type: 'string', nullable: true, maxLength: 50, example: 'ceremony'),
                    new OA\Property(property: 'display_order', type: 'integer', nullable: true, example: 0),
                    new OA\Property(property: 'is_enabled', type: 'boolean', nullable: true, example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 422, description: 'Validación', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function storeSchedule(): void {}

    #[OA\Put(
        path: '/api/v1/events/{slug}/schedules/{id}',
        tags: ['Events'],
        summary: 'Actualizar actividad (schedule) del evento',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'boda-ana-y-luis')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', nullable: true, maxLength: 150, example: 'Recepción'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 500, example: 'Recepción formal'),
                    new OA\Property(property: 'starts_at', type: 'string', nullable: true, example: '2026-12-15 19:00:00'),
                    new OA\Property(property: 'ends_at', type: 'string', nullable: true, example: '2026-12-15 21:00:00'),
                    new OA\Property(property: 'location_label', type: 'string', nullable: true, maxLength: 150, example: 'Salón principal'),
                    new OA\Property(property: 'location_type', type: 'string', nullable: true, maxLength: 50, example: 'reception'),
                    new OA\Property(property: 'display_order', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'is_enabled', type: 'boolean', nullable: true, example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 422, description: 'Validación', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function updateSchedule(): void {}

    #[OA\Delete(
        path: '/api/v1/events/{slug}/schedules/{id}',
        tags: ['Events'],
        summary: 'Eliminar actividad (schedule) del evento',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'boda-ana-y-luis')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function deleteSchedule(): void {}

    #[OA\Get(
        path: '/api/v1/events/{slug}/locations',
        tags: ['Events'],
        summary: 'Listar ubicaciones del evento',
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
                                    new OA\Property(property: 'id', type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Salón principal'),
                                    new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Av. Principal 123'),
                                    new OA\Property(property: 'maps_url', type: 'string', nullable: true, example: 'https://maps.example.com/location'),
                                    new OA\Property(property: 'type', type: 'string', nullable: true, example: 'reception'),
                                    new OA\Property(property: 'display_order', type: 'integer', example: 0),
                                    new OA\Property(property: 'is_enabled', type: 'boolean', example: true),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function listLocations(): void {}

    #[OA\Post(
        path: '/api/v1/events/{slug}/locations',
        tags: ['Events'],
        summary: 'Crear ubicación del evento',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'boda-ana-y-luis')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 150, example: 'Salón principal'),
                    new OA\Property(property: 'address', type: 'string', nullable: true, maxLength: 255, example: 'Av. Principal 123'),
                    new OA\Property(property: 'maps_url', type: 'string', nullable: true, maxLength: 500, example: 'https://maps.example.com/location'),
                    new OA\Property(property: 'type', type: 'string', nullable: true, maxLength: 50, example: 'reception'),
                    new OA\Property(property: 'display_order', type: 'integer', nullable: true, example: 0),
                    new OA\Property(property: 'is_enabled', type: 'boolean', nullable: true, example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 422, description: 'Validación', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function storeLocation(): void {}

    #[OA\Put(
        path: '/api/v1/events/{slug}/locations/{id}',
        tags: ['Events'],
        summary: 'Actualizar ubicación del evento',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'boda-ana-y-luis')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', nullable: true, maxLength: 150, example: 'Salón principal'),
                    new OA\Property(property: 'address', type: 'string', nullable: true, maxLength: 255, example: 'Av. Principal 123'),
                    new OA\Property(property: 'maps_url', type: 'string', nullable: true, maxLength: 500, example: 'https://maps.example.com/location'),
                    new OA\Property(property: 'type', type: 'string', nullable: true, maxLength: 50, example: 'reception'),
                    new OA\Property(property: 'display_order', type: 'integer', nullable: true, example: 0),
                    new OA\Property(property: 'is_enabled', type: 'boolean', nullable: true, example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 422, description: 'Validación', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function updateLocation(): void {}

    #[OA\Delete(
        path: '/api/v1/events/{slug}/locations/{id}',
        tags: ['Events'],
        summary: 'Eliminar ubicación del evento',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'boda-ana-y-luis')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function deleteLocation(): void {}

    #[OA\Get(
        path: '/api/v1/events/{slug}/dress-codes',
        tags: ['Events'],
        summary: 'Listar códigos de vestimenta del evento',
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
                                    new OA\Property(property: 'id', type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW'),
                                    new OA\Property(property: 'title', type: 'string', example: 'Formal elegante'),
                                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Vestimenta formal para la ceremonia'),
                                    new OA\Property(property: 'examples', type: 'string', nullable: true, example: 'Traje oscuro, vestido largo'),
                                    new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Evitar tenis y mezclilla'),
                                    new OA\Property(property: 'display_order', type: 'integer', example: 0),
                                    new OA\Property(property: 'is_enabled', type: 'boolean', example: true),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function listDressCodes(): void {}

    #[OA\Post(
        path: '/api/v1/events/{slug}/dress-codes',
        tags: ['Events'],
        summary: 'Crear código de vestimenta del evento',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'boda-ana-y-luis')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 150, example: 'Formal elegante'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 500, example: 'Vestimenta formal para la ceremonia'),
                    new OA\Property(property: 'examples', type: 'string', nullable: true, maxLength: 1000, example: 'Traje oscuro, vestido largo'),
                    new OA\Property(property: 'notes', type: 'string', nullable: true, maxLength: 1000, example: 'Evitar tenis y mezclilla'),
                    new OA\Property(property: 'display_order', type: 'integer', nullable: true, example: 0),
                    new OA\Property(property: 'is_enabled', type: 'boolean', nullable: true, example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 422, description: 'Validación', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function storeDressCode(): void {}

    #[OA\Put(
        path: '/api/v1/events/{slug}/dress-codes/{id}',
        tags: ['Events'],
        summary: 'Actualizar código de vestimenta del evento',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'boda-ana-y-luis')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', nullable: true, maxLength: 150, example: 'Formal elegante'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 500, example: 'Vestimenta formal para la ceremonia'),
                    new OA\Property(property: 'examples', type: 'string', nullable: true, maxLength: 1000, example: 'Traje oscuro, vestido largo'),
                    new OA\Property(property: 'notes', type: 'string', nullable: true, maxLength: 1000, example: 'Evitar tenis y mezclilla'),
                    new OA\Property(property: 'display_order', type: 'integer', nullable: true, example: 0),
                    new OA\Property(property: 'is_enabled', type: 'boolean', nullable: true, example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 422, description: 'Validación', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function updateDressCode(): void {}

    #[OA\Delete(
        path: '/api/v1/events/{slug}/dress-codes/{id}',
        tags: ['Events'],
        summary: 'Eliminar código de vestimenta del evento',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'boda-ana-y-luis')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function deleteDressCode(): void {}
}
