<?php

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ApiKey',
    type: 'object',
    required: ['id', 'name', 'prefix', 'scopes']
)]
final class ApiKey
{
    #[OA\Property(property: 'id', description: 'ULID público de la API key', type: 'string', example: '01J3Q90Z2YYQZ2D8JH2KX8ZQ0R')]
    public string $id;

    #[OA\Property(property: 'name', type: 'string', example: 'ERP Sync Key')]
    public string $name;

    #[OA\Property(property: 'prefix', type: 'string', example: 'kk_live_AbC12')]
    public string $prefix;

    #[OA\Property(
        property: 'scopes',
        description: 'Scopes almacenados (v1: no se enforzan)',
        type: 'array',
        items: new OA\Items(type: 'string'),
        example: ['read:users', 'write:orders']
    )]
    public array $scopes;

    #[OA\Property(property: 'last_used_at', type: 'string', format: 'date-time', nullable: true, example: '2026-03-03T18:55:00Z')]
    public ?string $last_used_at;

    #[OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true, example: '2026-06-01T00:00:00Z')]
    public ?string $expires_at;

    #[OA\Property(property: 'revoked_at', type: 'string', format: 'date-time', nullable: true, example: null)]
    public ?string $revoked_at;

    #[OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-03-03T18:50:00Z')]
    public string $created_at;
}
