<?php

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    type: 'object',
    required: ['id', 'name', 'email', 'status']
)]
final class User
{
    #[OA\Property(property: 'id', description: 'ULID público del usuario', type: 'string', example: '01KHN2Y1XYWPBEPJGB1104GDZW')]
    public string $id;

    #[OA\Property(property: 'name', type: 'string', example: 'Admin')]
    public string $name;

    #[OA\Property(property: 'email', type: 'string', example: 'admin@demo.kaanforge.test')]
    public string $email;

    #[OA\Property(property: 'username', type: 'string', nullable: true, example: 'admin')]
    public ?string $username;

    #[OA\Property(property: 'status', type: 'string', example: 'active', description: 'active|pending|suspended')]
    public string $status;

    #[OA\Property(property: 'timezone', type: 'string', nullable: true, example: 'America/Mexico_City')]
    public ?string $timezone;

    #[OA\Property(property: 'locale', type: 'string', example: 'es')]
    public string $locale;

    #[OA\Property(property: 'meta', type: 'object', nullable: true)]
    public ?object $meta;

    #[OA\Property(property: 'roles', description: 'Roles asignados al usuario', type: 'array', items: new OA\Items(type: 'string'), example: ['admin', 'editor'])] 
    public array $roles;

    #[OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), example: ['users.create', 'users.read', 'users.update', 'users.delete'])]
    public array $permissions;

}
