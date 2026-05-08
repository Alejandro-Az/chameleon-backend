<?php

namespace App\Docs\V1\Auth;

use OpenApi\Attributes as OA;

final class AuthRegisterEndpoints
{
    #[OA\Post(
        path: '/api/v1/auth/register',
        tags: ['Auth'],
        summary: 'Registro público de usuario',
        description: 'Crea una cuenta de usuario. Requiere que `KAAN_AUTH_ALLOW_PUBLIC_REGISTRATION=true` esté habilitado. Aplica rate-limit dual (IP + email).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AuthRegisterRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuario registrado exitosamente',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthRegisterResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Registro deshabilitado',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorRegistrationDisabled')
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 429,
                description: 'Demasiados intentos de registro',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function register(): void {}
}
