<?php

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'AuthLoginResponse', type: 'object', required: ['ok', 'data'])]
final class AuthLoginResponse
{
    #[OA\Property(property: 'ok', type: 'boolean', example: true)]
    public bool $ok;

    #[OA\Property(property: 'data', type: 'object', required: ['token_type', 'access_token', 'expires_in', 'user'])]
    #[OA\Property(property: 'token_type', type: 'string', example: 'bearer')]
    #[OA\Property(property: 'access_token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJh...')]
    #[OA\Property(property: 'expires_in', type: 'integer', example: 3600)]
    #[OA\Property(property: 'user', ref: '#/components/schemas/User')]
    public object $data;
}
