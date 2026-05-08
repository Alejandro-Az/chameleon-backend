<?php

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginatedAuthSessions',
    title: 'Paginated Auth Sessions',
    description: 'Response format for paginated auth sessions',
    allOf: [
        new OA\Schema(
            properties: [
                new OA\Property(property: 'ok', type: 'boolean', example: true),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/AuthSession')
                        )
                    ],
                    type: 'object'
                )
            ]
        )
    ]
)]
class PaginatedAuthSessions {}
