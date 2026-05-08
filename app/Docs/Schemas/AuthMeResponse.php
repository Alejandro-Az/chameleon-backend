<?php

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'AuthMeResponse', type: 'object', required: ['ok', 'data'])]
final class AuthMeResponse
{
    #[OA\Property(property: 'ok', type: 'boolean', example: true)]
    public bool $ok;

    #[OA\Property(property: 'data', ref: '#/components/schemas/User')]
    public object $data;
}
