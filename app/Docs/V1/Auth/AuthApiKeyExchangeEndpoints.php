<?php

namespace App\Docs\V1\Auth;

use OpenApi\Attributes as OA;

final class AuthApiKeyExchangeEndpoints
{
    #[OA\Post(
        path: '/api/v1/auth/api-keys/exchange',
        tags: ['Auth'],
        summary: 'API Key Exchange (M2M) — convierte X-API-Key en JWT + auth_session',
        parameters: [
            new OA\Parameter(
                name: 'X-API-Key',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'kk_live_abc123...'),
                description: 'Secreto one-time generado por el Admin Panel. Nunca se almacena en claro.'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK (misma forma que login)',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthLoginResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'API key inválida / ausente / revocada / expirada',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Service account inactivo o email no verificado (si aplica)',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 429,
                description: 'Demasiados intentos (incluye Retry-After)',
                headers: [
                    new OA\Header(
                        header: 'Retry-After',
                        description: 'Segundos recomendados de espera antes de reintentar.',
                        schema: new OA\Schema(type: 'integer', example: 60)
                    ),
                ],
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function exchange(): void {}
}
