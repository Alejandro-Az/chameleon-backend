<?php

namespace App\Docs\V1\Admin;

use OpenApi\Attributes as OA;

final class AdminAuditLogsEndpoints
{
    #[OA\Get(
        path: '/api/v1/admin/audit-logs',
        tags: ['Admin Audit Logs'],
        summary: 'Listar Audit Logs del sistema',
        description: 'Retorna un listado paginado de los logs de auditoria del sistema. Requiere permiso `admin.audit.view`.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user', description: 'Filtrar por ULID público del usuario autor del log', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'action', description: 'Filtrar de manera exacta por nombre de la acción (e.g `auth.login`)', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'from', description: 'Fecha de inicio del filtro', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', description: 'Fecha de fin del filtro', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'q', description: 'Búsqueda de texto en el nombre de la acción o en el email y el nombre de los usuarios', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', description: 'Elementos por página (max. 1000)', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15))
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedAuditLogs')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'No autorizado (falta permiso)', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function index(): void {}
}
