<?php

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ErrorRegistrationDisabled',
    type: 'object',
    required: ['ok', 'error']
)]
final class ErrorRegistrationDisabled
{
    #[OA\Property(property: 'ok', type: 'boolean', example: false)]
    public bool $ok;

    #[OA\Property(
        property: 'error',
        type: 'object',
        required: ['code', 'message', 'details'],
        properties: [
            new OA\Property(property: 'code', type: 'string', example: 'REGISTRATION_DISABLED'),
            new OA\Property(property: 'message', type: 'string', example: 'El registro público no está habilitado.'),
            new OA\Property(property: 'details', type: 'object', nullable: true, example: null),
        ]
    )]
    public object $error;
}
