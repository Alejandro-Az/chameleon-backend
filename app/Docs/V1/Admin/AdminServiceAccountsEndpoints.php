<?php

namespace App\Docs\V1\Admin;

use OpenApi\Attributes as OA;

final class AdminServiceAccountsEndpoints
{
    #[OA\Get(
        path: '/api/v1/admin/service-accounts',
        tags: ['Admin - Service Accounts'],
        summary: 'Listar service accounts',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page (hard cap 1000; real max governed by policy api.pagination.max_per_page)', schema: new OA\Schema(type: 'integer', example: 15, minimum: 1, maximum: 1000)),
            new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'ERP')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'active')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'ok', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/PaginatedServiceAccounts'),
                ])
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/api/v1/admin/service-accounts',
        tags: ['Admin - Service Accounts'],
        summary: 'Crear service account',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Integración - ERP Sync'),
                    new OA\Property(property: 'email', type: 'string', nullable: true, example: 'svc-erp@service.local'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Cuenta para sincronización ERP nightly job'),
                    new OA\Property(property: 'role', type: 'string', nullable: true, example: 'user'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'ok', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/ServiceAccount'),
                ])
            ),
            new OA\Response(response: 422, description: 'Validación', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(): void {}

    #[OA\Get(
        path: '/api/v1/admin/service-accounts/{serviceAccount}',
        tags: ['Admin - Service Accounts'],
        summary: 'Ver service account',
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
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'ok', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/ServiceAccount'),
                ])
            ),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(): void {}

    #[OA\Put(
        path: '/api/v1/admin/service-accounts/{serviceAccount}',
        tags: ['Admin - Service Accounts'],
        summary: 'Actualizar service account (completo)',
        description: 'Alias de PATCH para el mismo método update. Laravel apiResource registra ambos verbos apuntando al mismo controller action.',
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
                properties: [
                    new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Integración - ERP Sync (Updated)'),
                    new OA\Property(property: 'status', type: 'string', nullable: true, example: 'suspended', description: 'active|pending|suspended'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Cuenta usada por cron job nocturno'),
                    new OA\Property(property: 'role', type: 'string', nullable: true, example: 'user'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'ok', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/ServiceAccount'),
                ])
            ),
            new OA\Response(response: 422, description: 'Validación', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function updatePut(): void {}

    #[OA\Patch(
        path: '/api/v1/admin/service-accounts/{serviceAccount}',
        tags: ['Admin - Service Accounts'],
        summary: 'Actualizar service account',
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
                properties: [
                    new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Integración - ERP Sync (Updated)'),
                    new OA\Property(property: 'status', type: 'string', nullable: true, example: 'suspended', description: 'active|pending|suspended'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Cuenta usada por cron job nocturno'),
                    new OA\Property(property: 'role', type: 'string', nullable: true, example: 'user'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'ok', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/ServiceAccount'),
                ])
            ),
            new OA\Response(response: 422, description: 'Validación', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(): void {}
}
