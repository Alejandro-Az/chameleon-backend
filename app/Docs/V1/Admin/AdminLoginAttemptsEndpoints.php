<?php

namespace App\Docs\V1\Admin;

use OpenApi\Attributes as OA;

interface AdminLoginAttemptsEndpoints
{
    #[OA\Get(
        path: '/api/v1/admin/security/login-attempts',
        summary: 'List login attempts',
        description: 'Returns a paginated list of login attempts for admin observability. Login identifiers are masked by default to minimize PII exposure.',
        tags: ['Admin Security'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'q',
                description: 'Search by login identifier (email/username). Matched against raw value in DB.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 255)
            ),
            new OA\Parameter(
                name: 'ip',
                description: 'Filter by IP address',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'ip')
            ),
            new OA\Parameter(
                name: 'status',
                description: 'Filter by attempt status: success, failed, blocked',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['success', 'failed', 'blocked'])
            ),
            new OA\Parameter(
                name: 'from',
                description: 'Filter from date (inclusive)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'to',
                description: 'Filter to date (inclusive)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Number of items per page (1-1000, default 15)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 1000)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of login attempts',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedLoginAttempts')
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden — missing admin.security.view permission', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index();
}
