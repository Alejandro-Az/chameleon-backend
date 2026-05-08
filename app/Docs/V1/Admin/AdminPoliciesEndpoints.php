<?php

namespace App\Docs\V1\Admin;

use OpenApi\Attributes as OA;

final class AdminPoliciesEndpoints
{
    #[OA\Get(
        path: '/api/v1/admin/policies',
        tags: ['Admin - Policies'],
        summary: 'List policies (Policy Center)',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                headers: [
                    new OA\Header(header: 'ETag', schema: new OA\Schema(type: 'string'), description: 'W/"policy-v{n}"'),
                ],
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'ok', type: 'boolean', example: true),
                    new OA\Property(property: 'data', type: 'object'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(): void {}

    #[OA\Get(
        path: '/api/v1/admin/policies/{key}',
        tags: ['Admin - Policies'],
        summary: 'Get policy by key',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'key', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'api.pagination.max_per_page')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent()),
            new OA\Response(response: 404, description: 'POLICY_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(): void {}

    #[OA\Patch(
        path: '/api/v1/admin/policies',
        tags: ['Admin - Policies'],
        summary: 'Batch update policies (atomic)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['policies'],
                properties: [
                    new OA\Property(property: 'policies', type: 'object', example: [
                        'api.pagination.default_per_page' => 15,
                        'api.pagination.max_per_page' => 100,
                    ]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent()),
            new OA\Response(response: 422, description: 'VALIDATION_ERROR / POLICY_CONFLICT', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'POLICY_READ_ONLY / AUTH_FORBIDDEN', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'POLICY_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(): void {}
}
