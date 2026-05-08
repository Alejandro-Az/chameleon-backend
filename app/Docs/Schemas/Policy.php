<?php

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Policy', type: 'object', required: ['key','meta'])]
final class Policy
{
    #[OA\Property(property: 'key', type: 'string', example: 'api.pagination.max_per_page')]
    public string $key;

    #[OA\Property(property: 'value', nullable: true, oneOf: [
        new OA\Schema(type: 'boolean'),
        new OA\Schema(type: 'integer'),
        new OA\Schema(type: 'string'),
        new OA\Schema(type: 'array', items: new OA\Items(type: 'string')),
        new OA\Schema(type: 'object'),
    ])]
    public mixed $value;

    #[OA\Property(property: 'meta', type: 'object')]
    public object $meta;
}
