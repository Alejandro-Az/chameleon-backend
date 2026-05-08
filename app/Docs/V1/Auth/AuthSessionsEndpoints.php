<?php

namespace App\Docs\V1\Auth;

use OpenApi\Attributes as OA;

interface AuthSessionsEndpoints
{
    #[OA\Get(
        path: '/api/v1/auth/sessions',
        summary: 'List user sessions',
        description: 'Returns a paginated list of sessions (devices) for the authenticated user.',
        tags: ['Auth Sessions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'include_revoked',
                description: 'Include revoked sessions in the response',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Number of sessions per page (min 1, max 1000)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 1000)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedAuthSessions')
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Email no verificado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function index();

    #[OA\Delete(
        path: '/api/v1/auth/sessions/{session}',
        summary: 'Revoke a specific session',
        description: 'Revokes a specific session belonging to the authenticated user.',
        tags: ['Auth Sessions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Public ULID identifier of the session to revoke',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Session revoked successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'revoked', type: 'boolean', example: true),
                            new OA\Property(property: 'was_already_revoked', type: 'boolean', example: false),
                        ])
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Email no verificado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Session not found')
        ]
    )]
    public function destroy();

    #[OA\Post(
        path: '/api/v1/auth/sessions/revoke-others',
        summary: 'Revoke other sessions',
        description: 'Revokes all sessions for the authenticated user except the current one.',
        tags: ['Auth Sessions'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Other sessions revoked successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'revoked_count', type: 'integer', example: 2),
                        ])
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Email no verificado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function revokeOthers();

    #[OA\Post(
        path: '/api/v1/auth/sessions/revoke-all',
        summary: 'Revoke all sessions',
        description: 'Revokes all sessions including the current one, terminating the current token.',
        tags: ['Auth Sessions'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'All sessions revoked successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'revoked_count', type: 'integer', example: 3),
                        ])
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Email no verificado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function revokeAll();
}
