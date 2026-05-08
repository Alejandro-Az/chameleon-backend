<?php

namespace App\Docs\V1\Templates;

use OpenApi\Attributes as OA;

final class TemplatesEndpoints
{
    #[OA\Get(
        path: '/api/v1/templates',
        tags: ['Templates'],
        summary: 'Listar templates (público)',
        parameters: [
            new OA\Parameter(
                name: 'event_type',
                in: 'query',
                required: false,
                description: 'Filtrar por tipo de evento',
                schema: new OA\Schema(type: 'string', enum: ['wedding', 'xv', 'graduation', 'birthday', 'other'], example: 'wedding')
            ),
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
                                    new OA\Property(property: 'public_id', type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Tuscan Garden'),
                                    new OA\Property(property: 'event_type', type: 'string', enum: ['wedding', 'xv', 'graduation', 'birthday', 'other'], example: 'wedding'),
                                    new OA\Property(
                                        property: 'default_module_order',
                                        type: 'array',
                                        items: new OA\Items(type: 'string'),
                                        example: ['rsvp', 'schedule', 'gifts', 'gallery']
                                    ),
                                    new OA\Property(property: 'styles', type: 'object', example: ['primary_color' => '#e8d5b7', 'font' => 'Cormorant']),
                                ]
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/api/v1/templates',
        tags: ['Templates'],
        summary: 'Crear template (solo admin)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'event_type', 'default_module_order'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Rustic Chic'),
                    new OA\Property(property: 'event_type', type: 'string', enum: ['wedding', 'xv', 'graduation', 'birthday', 'other'], example: 'wedding'),
                    new OA\Property(
                        property: 'default_module_order',
                        type: 'array',
                        items: new OA\Items(type: 'string'),
                        example: ['rsvp', 'schedule', 'gifts', 'gallery']
                    ),
                    new OA\Property(property: 'styles', type: 'object', nullable: true, example: ['primary_color' => '#d4a373', 'font' => 'Playfair Display']),
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
                                new OA\Property(property: 'public_id', type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW'),
                                new OA\Property(property: 'name', type: 'string', example: 'Rustic Chic'),
                                new OA\Property(property: 'event_type', type: 'string', example: 'wedding'),
                                new OA\Property(
                                    property: 'default_module_order',
                                    type: 'array',
                                    items: new OA\Items(type: 'string'),
                                    example: ['rsvp', 'schedule', 'gifts', 'gallery']
                                ),
                                new OA\Property(property: 'styles', type: 'object', nullable: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validación', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso (requiere rol admin)', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(): void {}

    #[OA\Put(
        path: '/api/v1/templates/{public_id}',
        tags: ['Templates'],
        summary: 'Actualizar template (solo admin)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'public_id', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Rustic Chic V2'),
                    new OA\Property(
                        property: 'default_module_order',
                        type: 'array',
                        nullable: true,
                        items: new OA\Items(type: 'string'),
                        example: ['rsvp', 'gifts', 'schedule', 'gallery']
                    ),
                    new OA\Property(property: 'styles', type: 'object', nullable: true, example: ['primary_color' => '#c9a96e']),
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
                                new OA\Property(property: 'public_id', type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW'),
                                new OA\Property(property: 'name', type: 'string', example: 'Rustic Chic V2'),
                                new OA\Property(property: 'event_type', type: 'string', example: 'wedding'),
                                new OA\Property(
                                    property: 'default_module_order',
                                    type: 'array',
                                    items: new OA\Items(type: 'string'),
                                    example: ['rsvp', 'gifts', 'schedule', 'gallery']
                                ),
                                new OA\Property(property: 'styles', type: 'object', nullable: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validación', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso (requiere rol admin)', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(): void {}
}
