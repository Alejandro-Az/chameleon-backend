<?php

namespace App\Docs\V1\Admin;

use OpenApi\Attributes as OA;

/**
 * Documentación Swagger del endpoint de resumen del dashboard administrativo.
 *
 * El contenido de `data.metrics` y `data.recent_activity` varía según los permisos
 * granulares del usuario autenticado (admin.users.manage, admin.roles.manage,
 * admin.security.view, admin.audit.view). Solo se documentan los campos mínimos
 * garantizados por contrato (ver docs/CONTRACTS.md §9).
 */
final class AdminDashboardEndpoints
{
    #[OA\Get(
        path: '/api/v1/admin/dashboard/summary',
        tags: ['Admin - Dashboard'],
        summary: 'Resumen del dashboard administrativo',
        description: 'Retorna métricas del sistema y actividad reciente. El contenido de `data.metrics` y `data.recent_activity` depende de los permisos granulares del usuario autenticado: `admin.users.manage`, `admin.roles.manage`, `admin.security.view`, `admin.audit.view`. No requiere un permiso fijo — cualquier usuario activo y verificado puede llamar al endpoint; la respuesta se filtra automáticamente.',
        security: [['bearerAuth' => []]],
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
                            description: 'Contenido variable según permisos del usuario autenticado.',
                            properties: [
                                new OA\Property(
                                    property: 'metrics',
                                    type: 'object',
                                    description: 'Métricas del sistema. Cada clave aparece solo si el usuario tiene el permiso correspondiente.',
                                    properties: [
                                        new OA\Property(
                                            property: 'users',
                                            type: 'object',
                                            description: 'Presente si el usuario tiene `admin.users.manage`.',
                                            properties: [
                                                new OA\Property(property: 'total', type: 'integer', example: 42),
                                                new OA\Property(property: 'active', type: 'integer', example: 38),
                                                new OA\Property(property: 'suspended', type: 'integer', example: 4),
                                            ],
                                            nullable: true
                                        ),
                                        new OA\Property(
                                            property: 'roles',
                                            type: 'integer',
                                            example: 5,
                                            description: 'Presente si el usuario tiene `admin.roles.manage`.',
                                            nullable: true
                                        ),
                                        new OA\Property(
                                            property: 'active_sessions',
                                            type: 'integer',
                                            example: 12,
                                            description: 'Presente si el usuario tiene `admin.security.view` y la feature `admin_security` está habilitada.',
                                            nullable: true
                                        ),
                                        new OA\Property(
                                            property: 'failed_logins_24h',
                                            type: 'integer',
                                            example: 3,
                                            description: 'Presente si el usuario tiene `admin.security.view` y las features `admin_security` y `login_attempts` están habilitadas.',
                                            nullable: true
                                        ),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'recent_activity',
                                    type: 'object',
                                    description: 'Actividad reciente del sistema. Cada clave aparece solo si el usuario tiene el permiso y la feature correspondiente.',
                                    properties: [
                                        new OA\Property(
                                            property: 'login_attempts',
                                            type: 'array',
                                            items: new OA\Items(type: 'object'),
                                            description: 'Últimos 5 intentos de login. Presente si `admin.security.view` + features `admin_security` + `login_attempts`.',
                                            nullable: true
                                        ),
                                        new OA\Property(
                                            property: 'audit_logs',
                                            type: 'array',
                                            items: new OA\Items(type: 'object'),
                                            description: 'Últimos 5 audit logs. Presente si `admin.audit.view` + feature `audit`.',
                                            nullable: true
                                        ),
                                    ]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Usuario inactivo o email no verificado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function summary(): void {}
}
