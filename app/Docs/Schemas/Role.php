<?php

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Role',
    type: 'object',
    required: ['id', 'name', 'guard_name']
)]
final class Role
{
    #[OA\Property(property: 'id', description: 'ULID publico del rol', type: 'string', example: '01K30YQ8VQ3A5NQ2GG7T4KJ8VR')]
    public string $id;

    #[OA\Property(property: 'name', type: 'string', example: 'admin')]
    public string $name;

    #[OA\Property(property: 'guard_name', type: 'string', example: 'api')]
    public string $guard_name;

    #[OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), example: ['admin.users.manage', 'admin.roles.manage'])]
    public array $permissions;

    #[OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true)]
    public ?string $created_at;

    #[OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true)]
    public ?string $updated_at;
}
