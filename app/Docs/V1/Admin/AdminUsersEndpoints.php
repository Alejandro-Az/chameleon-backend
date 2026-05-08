<?php

namespace App\Docs\V1\Admin;

use OpenApi\Attributes as OA;

final class AdminUsersEndpoints
{
    #[OA\Get(
        path: '/api/v1/admin/users',
        tags: ['Admin - Users'],
        summary: 'Listar usuarios',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page (hard cap 1000; real max governed by policy api.pagination.max_per_page)', schema: new OA\Schema(type: 'integer', example: 15, minimum: 1, maximum: 1000)),
            new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'admin')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'active')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/PaginatedUsers'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/api/v1/admin/users',
        tags: ['Admin - Users'],
        summary: 'Crear usuario',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Nuevo Usuario'),
                    new OA\Property(property: 'email', type: 'string', example: 'nuevo@test.com'),
                    new OA\Property(property: 'username', type: 'string', nullable: true, example: 'nuevo'),
                    new OA\Property(property: 'password', type: 'string', description: 'Mínimo 12 caracteres, al menos una mayúscula, una minúscula y un número.', example: 'Secret123456'),
                    new OA\Property(property: 'status', type: 'string', example: 'active', description: 'active|pending|suspended'),
                    new OA\Property(property: 'timezone', type: 'string', nullable: true, example: 'America/Mexico_City'),
                    new OA\Property(property: 'locale', type: 'string', example: 'es'),
                    new OA\Property(property: 'meta', type: 'object', nullable: true),
                    new OA\Property(property: 'role', type: 'string', nullable: true, example: 'admin'),
                    new OA\Property(property: 'role_id', type: 'string', nullable: true, example: '01K30YQ8VQ3A5NQ2GG7T4KJ8VR', description: 'public_id (ULID) del rol; alternativa a role por nombre'),
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
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validación', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(): void {}

    #[OA\Get(
        path: '/api/v1/admin/users/{user}',
        tags: ['Admin - Users'],
        summary: 'Ver usuario',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW'),
                description: 'public_id (ULID)'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(): void {}

    #[OA\Put(
        path: '/api/v1/admin/users/{user}',
        tags: ['Admin - Users'],
        summary: 'Actualizar usuario',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW'),
                description: 'public_id (ULID)'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Admin Updated'),
                    new OA\Property(property: 'email', type: 'string', nullable: true, example: 'admin2@test.com'),
                    new OA\Property(property: 'username', type: 'string', nullable: true, example: 'admin2'),
                    new OA\Property(property: 'password', type: 'string', nullable: true, description: 'Mínimo 12 caracteres, al menos una mayúscula, una minúscula y un número.', example: 'NewSecret123456'),
                    new OA\Property(property: 'status', type: 'string', nullable: true, example: 'active'),
                    new OA\Property(property: 'timezone', type: 'string', nullable: true, example: 'America/Mexico_City'),
                    new OA\Property(property: 'locale', type: 'string', nullable: true, example: 'es'),
                    new OA\Property(property: 'meta', type: 'object', nullable: true),
                    new OA\Property(property: 'role', type: 'string', nullable: true, example: 'admin'),
                    new OA\Property(property: 'role_id', type: 'string', nullable: true, example: '01K30YQ8VQ3A5NQ2GG7T4KJ8VR', description: 'public_id (ULID) del rol; alternativa a role por nombre'),
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
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
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

    #[OA\Patch(
        path: '/api/v1/admin/users/{user}',
        tags: ['Admin - Users'],
        summary: 'Actualizar usuario (parcial)',
        description: 'Alias de PUT para el mismo método update. Laravel apiResource registra ambos verbos apuntando al mismo controller action.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW'),
                description: 'public_id (ULID)'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Admin Updated'),
                    new OA\Property(property: 'email', type: 'string', nullable: true, example: 'admin2@test.com'),
                    new OA\Property(property: 'username', type: 'string', nullable: true, example: 'admin2'),
                    new OA\Property(property: 'password', type: 'string', nullable: true, description: 'Mínimo 12 caracteres, al menos una mayúscula, una minúscula y un número.', example: 'NewSecret123456'),
                    new OA\Property(property: 'status', type: 'string', nullable: true, example: 'active'),
                    new OA\Property(property: 'timezone', type: 'string', nullable: true, example: 'America/Mexico_City'),
                    new OA\Property(property: 'locale', type: 'string', nullable: true, example: 'es'),
                    new OA\Property(property: 'meta', type: 'object', nullable: true),
                    new OA\Property(property: 'role', type: 'string', nullable: true, example: 'admin'),
                    new OA\Property(property: 'role_id', type: 'string', nullable: true, example: '01K30YQ8VQ3A5NQ2GG7T4KJ8VR', description: 'public_id (ULID) del rol; alternativa a role por nombre'),
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
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validación', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function patchUpdate(): void {}

    #[OA\Delete(
        path: '/api/v1/admin/users/{user}',
        tags: ['Admin - Users'],
        summary: 'Eliminar usuario (soft delete)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW'),
                description: 'public_id (ULID)'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'ok', type: 'boolean', example: true),
                ])
            ),
            new OA\Response(response: 404, description: 'No encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permiso', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(): void {}
}
