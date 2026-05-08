<?php

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuthRegisterResponse',
    type: 'object',
    required: ['ok', 'data']
)]
final class AuthRegisterResponse
{
    #[OA\Property(property: 'ok', type: 'boolean', example: true)]
    public bool $ok;

    #[OA\Property(
        property: 'data',
        type: 'object',
        required: ['user', 'requires_email_verification'],
        properties: [
            new OA\Property(
                property: 'user',
                type: 'object',
                required: ['id', 'name', 'email', 'status'],
                properties: [
                    new OA\Property(property: 'id', type: 'string', example: '01JMAXRW1Y...'),
                    new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez'),
                    new OA\Property(property: 'email', type: 'string', example: 'nuevo.usuario@ejemplo.com'),
                    new OA\Property(property: 'status', type: 'string', example: 'active'),
                ]
            ),
            new OA\Property(property: 'requires_email_verification', type: 'boolean', example: false),
            new OA\Property(property: 'token_type', type: 'string', example: 'bearer', nullable: true, description: 'Solo presente si register_issue_token=true y status=active'),
            new OA\Property(property: 'access_token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJh...', nullable: true),
            new OA\Property(property: 'expires_in', type: 'integer', example: 3600, nullable: true),
        ]
    )]
    public object $data;
}
