<?php

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ServiceAccount',
    type: 'object',
    required: ['id', 'type', 'name', 'email', 'status']
)]
final class ServiceAccount
{
    #[OA\Property(property: 'id', description: 'ULID público del service account', type: 'string', example: '01J3Q8XJQZ1W2W1V9D3B2C7K8N')]
    public string $id;

    #[OA\Property(property: 'type', description: 'Clasificación del usuario', type: 'string', example: 'service')]
    public string $type;

    #[OA\Property(property: 'name', type: 'string', example: 'Integración - ERP Sync')]
    public string $name;

    #[OA\Property(property: 'email', type: 'string', example: 'svc-01J3Q8XJQZ1W2W1V9D3B2C7K8N@service.local')]
    public string $email;

    #[OA\Property(property: 'status', type: 'string', example: 'active', description: 'active|pending|suspended')]
    public string $status;

    #[OA\Property(property: 'meta', type: 'object', nullable: true, description: 'Metadata libre (ej: description)')]
    public ?object $meta;

    #[OA\Property(
        property: 'roles',
        description: 'Roles asignados al service account',
        type: 'array',
        items: new OA\Items(type: 'string'),
        example: ['user']
    )]
    public array $roles;

    #[OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-03-03T18:30:00Z')]
    public string $created_at;

    #[OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-03-03T18:45:00Z')]
    public string $updated_at;
}
