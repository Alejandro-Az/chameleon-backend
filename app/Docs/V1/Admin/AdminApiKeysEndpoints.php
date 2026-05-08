<?php

namespace App\Docs\V1\Admin;

use OpenApi\Attributes as OA;

final class AdminApiKeysEndpoints
{
    #[OA\Get(
        path: '/api/v1/admin/service-accounts/{serviceAccount}/api-keys',
        tags: ['Admin - API Keys'],
        summary: 'Listar API keys de un service account',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'serviceAccount',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: '01J3Q8XJQZ1W2W1V9D3B2C7K8N'),
                description: 'public_id (ULID) del service account'
            ),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page (hard cap 1000; real max governed by policy api.pagination.max_per_page)', schema: new OA\Schema(type: 'integer', example: 15, minimum: 1, maximum: 1000)),
            new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'ERP')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'active', description: 'active|revoked|expired')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'ok', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/PaginatedApiKeys'),
                ])
            ),
            new OA\Response(response: 404, description: 'Service account no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/api/v1/admin/service-accounts/{serviceAccount}/api-keys',
        tags: ['Admin - API Keys'],
        summary: 'Crear API key para un service account (retorna secreto one-time)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'serviceAccount',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: '01J3Q8XJQZ1W2W1V9D3B2C7K8N'),
                description: 'public_id (ULID) del service account'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'ERP Sync Key'),
                    new OA\Property(property: 'scopes', type: 'array', items: new OA\Items(type: 'string'), nullable: true, example: ['read:users', 'write:orders']),
                    new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true, example: '2026-06-01T00:00:00Z'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created (retorna secreto one-time)',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'ok', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'api_key', ref: '#/components/schemas/ApiKey'),
                            new OA\Property(property: 'secret', type: 'string', description: 'Secreto one-time (no se puede recuperar después)', example: 'kk_live_f2J9kYx...'),
                        ]
                    ),
                ])
            ),
            new OA\Response(response: 422, description: 'Validación', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Service account no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(): void {}

    #[OA\Post(
        path: '/api/v1/admin/api-keys/{apiKey}/rotate',
        tags: ['Admin - API Keys'],
        summary: 'Rotar API key (revoca anterior y retorna nuevo secreto one-time)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'apiKey',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: '01J3Q90Z2YYQZ2D8JH2KX8ZQ0R'),
                description: 'public_id (ULID) de la API key'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK (retorna nuevo secreto one-time)',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'ok', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'api_key', ref: '#/components/schemas/ApiKey'),
                            new OA\Property(property: 'secret', type: 'string', description: 'Secreto one-time (no se puede recuperar después)', example: 'kk_live_9KxL1p...'),
                        ]
                    ),
                ])
            ),
            new OA\Response(response: 404, description: 'API key no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function rotate(): void {}

    #[OA\Delete(
        path: '/api/v1/admin/api-keys/{apiKey}',
        tags: ['Admin - API Keys'],
        summary: 'Revocar API key (idempotente)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'apiKey',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: '01J3Q90Z2YYQZ2D8JH2KX8ZQ0R'),
                description: 'public_id (ULID) de la API key'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'ok', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'message', type: 'string', example: 'API key revocada correctamente.'),
                        ]
                    ),
                ])
            ),
            new OA\Response(response: 404, description: 'API key no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(): void {}
}
