<?php

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    required: ['ok', 'error']
)]
final class ErrorResponse
{
    #[OA\Property(property: 'ok', type: 'boolean', example: false)]
    public bool $ok;

    #[OA\Property(property: 'error', type: 'object', required: ['code', 'message', 'details'])]
    #[OA\Property(
        property: 'code',
        type: 'string',
        description: "Código de error estandarizado para manejo en Frontend.\n\nCódigos comunes:\n- `AUTH_INVALID`: Credenciales incorrectas.\n- `AUTH_USER_INACTIVE`: Usuario suspendido o inactivo.\n- `AUTH_TOO_MANY_ATTEMPTS`: Límite de intentos (rate limiting login).\n- `AUTH_TOO_MANY_REQUESTS`: Límite de intentos (rate limiting registro).\n- `AUTH_ACCOUNT_LOCKED`: Cuenta bloqueada temporalmente por fallos excesivos.\n- `AUTH_TOKEN_EXPIRED`: El token JWT ha expirado.\n- `AUTH_TOKEN_REVOKED`: El token fue revocado o está en blacklist.\n- `AUTH_TOKEN_INVALID`: Token malformado o firma incorrecta.\n- `AUTH_SESSION_NOT_FOUND`: Sesión no existe en base de datos (deslogueo forzado).\n- `AUTH_SESSION_EXPIRED`: Sesión expirada por tiempo de inactividad.\n- `AUTH_FORBIDDEN`: No tiene permisos para esta acción.\n- `REGISTRATION_DISABLED`: El registro público no está habilitado.\n- `VALIDATION_ERROR`: Error de validación de campos (ver `details`).",
        example: 'AUTH_FORBIDDEN'
    )]
    #[OA\Property(
        property: 'message',
        type: 'string',
        example: 'No tienes permiso para realizar esta acción.'
    )]
    #[OA\Property(
        property: 'details',
        type: 'object',
        nullable: true
    )]
    public object $error;
}
