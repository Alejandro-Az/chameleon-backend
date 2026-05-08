<?php

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginatedAuditLogs',
    title: 'Paginated Audit Logs',
    description: 'Response format for paginated audit logs',
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
                            items: new OA\Items(ref: '#/components/schemas/AuditLog')
                        )
                    ],
                    type: 'object'
                )
            ]
        )
    ]
)]
class PaginatedAuditLogs {}
