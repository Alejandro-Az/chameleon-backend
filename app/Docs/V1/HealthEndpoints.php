<?php

namespace App\Docs\V1;

use OpenApi\Attributes as OA;

final class HealthEndpoints
{
    #[OA\Get(
        path: '/api/v1/health',
        tags: ['Health'],
        summary: 'Health check',
        description: 'Returns backend health status and Policy Center runtime status.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'app', type: 'string', example: 'Kaan Core Backend'),
                                new OA\Property(property: 'version', type: 'string', example: '0.2.0-alpha'),
                                new OA\Property(property: 'db', type: 'boolean', example: true),
                                new OA\Property(property: 'policy_center_enabled', type: 'boolean', example: true),
                                new OA\Property(property: 'policy_center_degraded', type: 'boolean', example: false),
                                new OA\Property(property: 'service_accounts_enabled', type: 'boolean', example: false, description: 'True when KAAN_FEATURE_API_KEYS=true'),
                                new OA\Property(property: 'time', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function show(): void {}
}
