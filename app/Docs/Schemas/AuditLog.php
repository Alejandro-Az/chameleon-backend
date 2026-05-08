<?php

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuditLog',
    title: 'Audit Log',
    description: 'A single audit log record of a platform event.',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1042),
        new OA\Property(property: 'action', type: 'string', example: 'auth.login'),
        new OA\Property(
            property: 'user',
            description: 'El actor (usuario) que provocó la acción, si aplica.',
            type: 'object',
            properties: [
                new OA\Property(property: 'id', description: 'ULID público del usuario', type: 'string', example: '01HJY...', nullable: true),
                new OA\Property(property: 'name', type: 'string', example: 'Admin User', nullable: true),
                new OA\Property(property: 'email', type: 'string', example: 'admin@example.com', nullable: true),
            ],
            nullable: true
        ),
        new OA\Property(
            property: 'model',
            description: 'Metadata del modelo sobre el cual se hizo la acción.',
            type: 'object',
            properties: [
                new OA\Property(property: 'type', type: 'string', example: 'App\\Models\\User', nullable: true),
                new OA\Property(property: 'id', type: 'integer', example: 45, nullable: true),
            ],
            nullable: true
        ),
        new OA\Property(
            property: 'details',
            description: 'Payload con metadata adicional estructurada del evento. Se higieniza de manera recursiva por motivos de seguridad reescribiendo llaves detectadas como "secretas" a "[REDACTED]".',
            type: 'object',
            example: '{"ip": "127.0.0.1", "browser": "Chrome"}'
        ),
        new OA\Property(property: 'ip_address', type: 'string', example: '192.168.1.1', nullable: true),
        new OA\Property(property: 'user_agent', type: 'string', example: 'Mozilla/5.0 ...', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-02-24T12:00:00Z'),
    ]
)]
class AuditLog {}
