<?php

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuthSession',
    title: 'Auth Session',
    description: "User's active authentication session (device log)",
    properties: [
        new OA\Property(property: 'id', type: 'string', example: '01H0ABCDEF1234567890XYZABC'),
        new OA\Property(property: 'ip_address', type: 'string', example: '192.168.1.1'),
        new OA\Property(property: 'user_agent', type: 'string', example: 'Mozilla/5.0...'),
        new OA\Property(property: 'last_seen_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'revoked_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'is_current', type: 'boolean', example: true),
    ]
)]
class AuthSession {}
