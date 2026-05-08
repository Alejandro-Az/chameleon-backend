<?php

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginAttemptSchema',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 42),
        new OA\Property(property: 'login_masked', type: 'string', example: 'j***n@example.com'),
        new OA\Property(property: 'login_raw', type: 'string', nullable: true, example: null, description: 'Only visible when expose_raw_identifier_to_admin is enabled'),
        new OA\Property(property: 'status', type: 'string', enum: ['success', 'failed', 'blocked']),
        new OA\Property(property: 'ip_address', type: 'string', example: '192.168.1.100'),
        new OA\Property(property: 'user_agent', type: 'string', nullable: true, example: 'Mozilla/5.0 ...'),
        new OA\Property(property: 'blocked_until', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
class LoginAttemptSchema {}
