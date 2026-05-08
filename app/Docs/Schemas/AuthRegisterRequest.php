<?php

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuthRegisterRequest',
    type: 'object',
    required: ['email', 'password', 'password_confirmation', 'name']
)]
final class AuthRegisterRequest
{
    #[OA\Property(property: 'email', type: 'string', format: 'email', example: 'nuevo.usuario@ejemplo.com')]
    public string $email;

    #[OA\Property(property: 'password', type: 'string', format: 'password', minLength: 12, maxLength: 72, description: 'Mínimo 12 caracteres, al menos una mayúscula, una minúscula y un número.', example: 'MiClave123Segura')]
    public string $password;

    #[OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'MiClave123Segura')]
    public string $password_confirmation;

    #[OA\Property(property: 'name', type: 'string', maxLength: 120, example: 'Juan Pérez')]
    public string $name;

    #[OA\Property(
        property: 'profile',
        type: 'object',
        properties: [
            new OA\Property(property: 'phone', type: 'string', maxLength: 30, nullable: true, example: '+52 222 123 4567'),
            new OA\Property(property: 'company', type: 'string', maxLength: 120, nullable: true, example: 'Kaan Forge Solutions'),
        ],
        nullable: true
    )]
    public ?object $profile;
}
