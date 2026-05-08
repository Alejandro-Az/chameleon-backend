# Changelog — Kaan Core Backend

Todas las modificaciones importantes de este proyecto se documentan en este archivo. El formato se basa en [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [0.2.2-alpha] - 2026-05-08

Cierre de drift documental para el módulo Camaleon (Auth + Events + Templates) y sincronización de estado de avance.

### Cambiado
- Swagger de Events alineado con runtime:
    - `type` actualizado a `wedding | quinceanera | graduation | birthday | party | other`.
    - `date` documentado como `nullable` en create/update/responses.
    - `template_id` documentado como string ULID (`public_id`), no entero.
    - `status` de update alineado a `draft | published`.
    - response de delete documentado con `data: null`.
- Swagger de Templates alineado con runtime:
    - payload expuesto con `id` (identificador público), no `public_id`.
    - `event_type` actualizado a enums vigentes.
    - estructura real de `styles` documentada.
    - `styles` marcado como requerido en create.
- `CONTRACTS.md` (raíz) sincronizado con contratos reales consumibles por frontend para:
    - Auth (`login`, `refresh`, `me`, `logout`)
    - Events (público + CRUD + modules)
    - Templates (público + admin create/update)
- `ESTADO.md` actualizado al avance real de Plan 1 y rutas actuales (`/api/v1/events/{slug}`).

### Verificado
- `php artisan l5-swagger:generate` OK.
- `php artisan test` OK (`217 passed`).

## [0.2.1-alpha] - 2026-03-16

Consolidación contractual del módulo de Roles y formalización de políticas documentales de release.

### ⚠️ Breaking Changes

| Antes | Después | Acción requerida |
|-------|---------|-----------------|
| `/api/v1/admin/roles/{id}` aceptaba compatibilidad temporal con PK numérica interna | Solo acepta `public_id` (ULID string) | Verificar que todos los consumidores usen `data.id` público |
| `/api/v1/auth/sessions/{id}` aceptaba compatibilidad temporal con PK numérica interna | Solo acepta `public_id` (ULID string) | Verificar que todos los consumidores usen `data.id` público |

### Añadido
- Política formal de gobernanza de cambios de contrato en `docs/CONTRACTS.md`.
- Plan de rollback por release en `docs/PRODUCTION-READINESS.md`.
- Cobertura de tests de roles para:
  - `GET /admin/roles/{role}` por `public_id`
  - `PATCH /admin/roles/{role}` por `public_id`
  - rechazo explícito de ID numérico legado

### Cambiado
- Contrato de roles consolidado como público estricto (`public_id`) end-to-end.
- Documentación Swagger de roles alineada con rutas reales (`PUT` y `PATCH` en update, parámetro `role` tipo string).

## [0.2.0-alpha] - 2026-03-05

Sprint de endurecimiento arquitectónico. Resuelve 11 hallazgos de auditoría interna, consolida la emisión JWT, elimina anti-patterns de `env()`, y fortalece las fronteras de dominio entre usuarios humanos y service accounts.

### ⚠️ Breaking Changes

| Antes | Después | Acción requerida |
|-------|---------|-----------------|
| `KAAN_FEATURE_AUTH_SESSIONS` | *(eliminado)* | `auth_sessions` es ahora core capability. Eliminar la variable del `.env`. |
| `KAAN_FEATURE_RBAC` | *(eliminado)* | `rbac` es ahora core capability. Eliminar la variable del `.env`. |
| `KAAN_FEATURE_SECURITY_CENTER` | `KAAN_FEATURE_ADMIN_SECURITY` | Renombrar en `.env`. |
| `ADMIN_EMAIL` | `KAAN_ADMIN_EMAIL` | Renombrar en `.env`. |
| `ADMIN_PASSWORD` | `KAAN_ADMIN_PASSWORD` | Renombrar en `.env`. |
| `AUTH_LOGIN_FIELD` | `KAAN_AUTH_LOGIN_FIELD` | Renombrar en `.env`. |
| Route binding `{user}` acepta ID numérico | Solo `public_id` (ULID) | Verificar que no haya consumidores usando IDs numéricos. |
| Route binding `{user}` con `withTrashed()` | Sin `withTrashed()` | Usuarios soft-deleted ya no se resuelven por binding. |
| `/admin/users` devolvía service accounts | Solo usuarios `human` | Usar `/admin/service-accounts` para gestionar service accounts. |

> **Operación post-deploy obligatoria:** Si usas `config:cache` o `route:cache`, ejecutar `php artisan config:clear && php artisan route:clear && php artisan config:cache && php artisan route:cache`.

### Añadido
- **Core Capabilities:** `auth_sessions` y `rbac` son ahora capacidades core del kernel, siempre activas y no desactivables.
- **Paginación centralizada** (`kaan.pagination.default`, `kaan.pagination.max`): Todos los endpoints de listado ahora aplican `min(max(..., 1), max)` server-side.
- **Frontera human/service:** `AdminUserController` ahora rechaza service accounts en `show`, `update`, y `destroy` con `RESOURCE_NOT_FOUND`.
- **JWT Issuance unificada:** Toda emisión de tokens (login, refresh, exchange) usa `JwtSessionIssuer::issue()`.
- **Redaction keys compartidas:** `AuditLogger::SENSITIVE_KEYS` es la fuente única de verdad para sanitización (write-time y read-time).
- **4 tests nuevos** de frontera human/service en `AdminUsersTest`.
- **Config centralizada:** `kaan.admin.bootstrap_email`, `kaan.admin.bootstrap_password`, `kaan.auth.login_field`, `kaan.pagination.*`.

### Cambiado
- `AdminUserController@index` ahora filtra por `->human()` scope exclusivamente.
- `AdminServiceAccountController@store` ahora usa `forceFill()` para `type` y `email_verified_at` (fix de bug de mass assignment).
- `AdminLoginAttemptController` ahora usa `whereDate` para filtros de fecha (consistencia con `AdminAuditLogController`).
- `AuthController@login` y `@refresh` migrados a `JwtSessionIssuer::issue()`.
- `AuthApiKeyController@exchange` migrado a `JwtSessionIssuer::issue()` con soporte `api_key_id`.
- `AuthSession::revokeAllForUser()` ahora es incondicional (sin feature flag check).
- `AdminApiKeyController@destroy` — revocación quirúrgica de sesiones ahora incondicional.
- `AppServiceProvider` — route binding `{user}` solo por `public_id`, sin fallback numérico ni `withTrashed()`.
- `KaanInstallCommand` — RBAC seed siempre activo, usa `config()` sin `env()`.
- `RegisterController` — asignación de rol siempre activa (sin condicional).
- `ReadinessChecker` — usa `config()` sin `env()` para admin email/password.
- Tests actualizados: `KernelContractRoutesTest` (admin_security), `AuthRegisterTest` (sin condicionales rbac/auth_sessions).

### Arreglado
- **Bug funcional:** `email_verified_at` no se persistía en service accounts porque no estaba en `$fillable`. Corregido con `forceFill()`.
- **Inconsistencia de filtros:** `AdminLoginAttemptController` usaba `where` con timestamps, ahora usa `whereDate` para incluir registros del final del día.
- **Anti-pattern `env()`:** Eliminadas todas las llamadas a `env()` fuera de `config/` en código de producción.
- **Redaction duplicada:** `AuditLogResource` ya no mantiene su propia lista de keys sensibles; usa `AuditLogger::SENSITIVE_KEYS`.

## [0.1.0-alpha] - 2026-03-03

Esta es la primera versión estable del backend (Alpha), diseñada como un núcleo sólido de identidad y seguridad para cualquier ecosistema SaaS o Panel Admin.

### Añadido
- **Kernel de Preparación para Producción (Readiness)**:
    - Servicio centralizado `ReadinessChecker` para validación de dependencias.
    - `php artisan kaan:health`: Comando de monitoreo con códigos de salida (0/1) para integraciones CI/CD.
    - Bandera `--strict` en los chequeos de salud para elevar todas las advertencias a fallos críticos.
    - `php artisan kaan:install`: Configuración automatizada con bloqueo de seguridad en entornos de producción.
- **Latido del Programador (Scheduler Heartbeat)**:
    - Mecanismo de latido en `routes/console.php` con soporte `onOneServer()` para entornos en clúster (AWS/K8s/Balanceadores de Carga).
    - Umbral personalizable a través de `KAAN_SCHEDULER_HEARTBEAT_MAX_AGE_MINUTES`.
- **Seguridad y Observabilidad**:
    - Política de contraseñas obligatoria para administradores (mínimo 12 caracteres, mayúsculas y minúsculas, números, símbolos).
    - Limitador de Peticiones (Rate Limiting) con soporte para la cabecera `Retry-After` mejorando la UX del frontend.
    - Security Center: Endpoint para el monitoreo administrativo de intentos de inicio de sesión.
    - Registros de Auditoría: Módulo de seguimiento de actividad forense.
    - Limpieza (pruning) automática de registros y sesiones a través del programador de tareas (scheduler).
- **Reestructuración de Documentación**:
    - `docs/PRODUCTION-READINESS.md`: Lista de verificación obligatoria para despliegues.
    - `docs/frontend-integration.md`: Guía paso a paso para consumidores en React/Vue.
    - `docs/CONTRACTS.md`: Fuente canónica de estructuras de respuesta de la API y códigos de error.
    - `docs/security-center.md`: Análisis profundo de las capas de auditoría y protección.

### Cambiado
- **Arquitectura API First**: 
    - La ruta raíz `/` ahora devuelve una respuesta JSON de identidad en lugar de una vista por defecto de Laravel.
    - Sobres de respuesta estandarizados a `{ ok: boolean, data?: any, error?: any }`.
- **Flujo de Autenticación**: 
    - `POST /auth/refresh` movido a un alcance protegido para prevenir la elusión de sesiones.
    - `AuthController` actualizado para asegurar que la revocación de sesiones esté sincronizada con la base de datos.
- **Estructura del Proyecto**:
    - Actualizado a Laravel 12.
    - La documentación ahora está centralizada en un Hub dentro de `README.md`.
    - `DOCUMENTATION.md` mejorado con contratos de rendimiento explícitos y secciones de índice.

### Arreglado
- **Bypass del Token de Refresco**: Se resolvió una vulnerabilidad donde un token revocado aún podía generar una nueva sesión a través del endpoint de refresco.
- **Obsolescencias de PHPUnit**: Se actualizaron todas las anotaciones de pruebas desde comentarios de bloque a atributos de PHP 8.
- **Manejo de Configuración**: Se eliminaron las llamadas directas a `env()` codificadas (hardcoded) en los comandos, moviéndolas a un sistema unificado en `config/kaan.php`.

### Eliminado
- **Archivos Base (Scaffolding) sin Uso**: 
    - Se limpiaron las dependencias de node/npm (`package.json`, `vite.config.js`).
    - Se eliminaron `resources/js`, `resources/css` y `public/build` (modo backend API puro).
    - Se borraron los archivos JSON redundantes de Swagger y la documentación obsoleta (`MEJORAS_CORE.md`, `FRONTEND_PROMPT.md`).

---
> 💡 **Nota de versión:** Este tag marca el "Baseline de Desarrollo". Cualquier cambio estructural a partir de aquí debe realizarse mediante migraciones inmutables y controladores versionados.
