<?php

namespace App\Docs\V1\Admin;

use OpenApi\Attributes as OA;

final class AdminPermissionsEndpoints
{
    #[OA\Get(
        path: '/api/v1/admin/permissions',
        tags: ['Admin - Permissions'],
        summary: 'Listar permisos',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page (hard cap 1000; real max governed by policy api.pagination.max_per_page)', schema: new OA\Schema(type: 'integer', example: 15, minimum: 1, maximum: 1000)),
            new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'users')),
            new OA\Parameter(name: 'guard_name', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'api')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(): void {}
}
