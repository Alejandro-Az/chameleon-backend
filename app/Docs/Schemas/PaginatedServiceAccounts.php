<?php

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginatedServiceAccounts',
    type: 'object',
    required: ['data', 'links', 'meta']
)]
final class PaginatedServiceAccounts
{
    #[OA\Property(
        property: 'data',
        type: 'array',
        items: new OA\Items(ref: '#/components/schemas/ServiceAccount')
    )]
    public array $data;

    #[OA\Property(property: 'links', type: 'object')]
    public object $links;

    #[OA\Property(property: 'meta', type: 'object')]
    public object $meta;
}
