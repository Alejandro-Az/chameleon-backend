# Guía de Integración Frontend — Kaan Core (v0.2.0-alpha)

> Este documento resume los endpoints y contratos del **Identity Kernel** para consumo desde cualquier frontend (React, Vue, Mobile, etc.).
> Para el catálogo completo de contratos, ver [`CONTRACTS.md`](CONTRACTS.md).

## Reglas Generales

- **Base URL:** `https://tu-dominio.com/api/v1`
- **Headers requeridos:** `Accept: application/json` + `Authorization: Bearer <access_token>` (endpoints protegidos)

### Contrato de Respuesta (Envelope)

```json
// Éxito
{ "ok": true, "data": { ... } }

// Error (details SIEMPRE presente, puede ser null)
{
  "ok": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Texto legible.",
    "details": null
  }
}
```

> **Invariante:** `error.details` siempre está presente en la respuesta de error (nunca se omite la key). Si no hay detalles adicionales, su valor es `null`. En `VALIDATION_ERROR` (422), `details` es un mapa `{ campo: [mensajes...] }`.

Documento especifico del modulo de citas:
- Ver `docs/appointments-frontend-integration.md` para endpoints, permisos, payloads, enums y reglas de slots/autoasignacion.

---

## 1) Autenticación y Onboarding

### Login
`POST /auth/login`
- **Body:** `{ "login": "email_o_username", "password": "..." }`
- **Response (200):** `data.access_token`, `data.token_type`, `data.expires_in`, `data.user`
- **Errores posibles:** `AUTH_INVALID` (401), `AUTH_USER_INACTIVE` (403), `AUTH_EMAIL_NOT_VERIFIED` (403), `AUTH_TOO_MANY_ATTEMPTS` (429), `AUTH_ACCOUNT_LOCKED` (429)

### Refresh
`POST /auth/refresh`
- **Header:** enviar el token actual (aunque esté próximo a expirar).
- **Response (200):** nuevo `access_token` + `expires_in`.
- **Errores posibles:** `AUTH_TOKEN_INVALID` (401), `AUTH_TOKEN_REVOKED` (401), `AUTH_SESSION_NOT_FOUND` (401), `AUTH_SESSION_EXPIRED` (401), `AUTH_JWT_ERROR` (401), `AUTH_USER_INACTIVE` (403), `AUTH_EMAIL_NOT_VERIFIED` (403)

### Registro Público
`POST /auth/register` (requiere `KAAN_AUTH_ALLOW_PUBLIC_REGISTRATION=true`)
- **Body:** `name`, `email`, `password`, `password_confirmation`, `profile` (opcional: `{ "phone": "...", "company": "..." }`)
- **Política de contraseña:** mínimo 12 caracteres, al menos una mayúscula, una minúscula y un número.
- **Response (201):** `data.user` + `data.access_token` (si status=active y flag issue_token=true)
- **Errores posibles:** `REGISTRATION_DISABLED` (403), `VALIDATION_ERROR` (422), `AUTH_TOO_MANY_REQUESTS` (429)

### Verificación de Email
- **Link firmado:** `GET /auth/verify-email/{id}/{hash}` → redirige al frontend con query params (`verified=1/0`).
  - *Si envías `Accept: application/json`, devuelve `200 { ok:true, data:{ verified, message } }` (sin redirect).*
- **Estado (Check):** `GET /auth/email/verification-status` (requiere auth).
- **Resend:** `POST /auth/email/verification-notification` (requiere auth).
- **Errores posibles:** `AUTH_VERIFICATION_INVALID` (403), `AUTH_TOO_MANY_REQUESTS` (429)

### Password Reset
- **Forgot:** `POST /auth/forgot-password` (Body: `{ "email": "..." }`). Siempre devuelve 200 (no revela si existe).
  - **Errores posibles:** `VALIDATION_ERROR` (422), `AUTH_TOO_MANY_ATTEMPTS` (429 + Retry-After)
- **Reset:** `POST /auth/reset-password` (Body: `email`, `token`, `password`, `password_confirmation`).
  - **Política de contraseña:** mínimo 12 caracteres, al menos una mayúscula, una minúscula y un número.
  - Revoca TODAS las sesiones activas del usuario automáticamente.
  - **Errores posibles:** `VALIDATION_ERROR` (422), `AUTH_PASSWORD_RESET_INVALID` (422), `AUTH_TOO_MANY_ATTEMPTS` (429 + Retry-After)

---

## 2) Self-Service (Cuenta del Usuario)

### Logout
`POST /auth/logout`
- Revoca el token actual y marca la sesión como cerrada en DB.
- **Response (200):** `{ "ok": true, "data": { "message": "Sesión cerrada correctamente." } }`
- **Acción recomendada:** limpiar `access_token` local y redirigir a Login.

### Perfil ("Me")
`GET /auth/me`
- Retorna `data.id`, `data.name`, `data.email`, `data.roles[]`, `data.permissions[]`.
- **Tip:** Usa `permissions` para mostrar/ocultar secciones del UI (nunca basarse en el nombre del rol).

### Gestión de Sesiones (Security Hub)
Base: `/auth/sessions`
- `GET /` — Lista dispositivos activos (IP, User Agent, Last Seen).
- `DELETE /{id}` — Revoca una sesión (idempotente; devuelve `was_already_revoked`). **Nota:** `id` expuesto por este endpoint es un ULID string (identificador público), no el PK numérico interno; el endpoint acepta únicamente ese ID público.
- `POST /revoke-others` — Cierra todas excepto la actual.
- `POST /revoke-all` — Cierra absolutamente todo (incluye la actual).

---

## 3) Control Plane (Administración)

> **Regla de Oro:** basa tu UI en los permisos del usuario (`admin.users.manage`, `admin.audit.view`, etc.) y no en el nombre del rol.

- **Usuarios:** `GET/POST /admin/users`, `GET/PATCH/DELETE /admin/users/{ulid}`. Soporta `?q=`, `?status=`, paginación.
  - Para asignar rol en creación (`POST /admin/users`), enviar `role` (name) o `role_id` (public_id ULID del rol).
  - Para cambio de rol en `PUT/PATCH /admin/users/{ulid}`, enviar `role` (name) o `role_id` (public_id ULID del rol).
- **Roles:** `GET/POST /admin/roles`, `GET/PUT/PATCH/DELETE /admin/roles/{id}` donde `id` es ULID público del rol.
- **Permisos:** `GET /admin/permissions`.
- **Auditoría:** `GET /admin/audit-logs` (permiso `admin.audit.view`). Soporta `?user_id=`, `?action=`, `?from=`, `?to=`.
- **Seguridad:** `GET /admin/security/login-attempts` (permiso `admin.security.view`). Soporta `?q=`, `?ip=`, `?status=`, `?from=`, `?to=`.

### Dashboard
`GET /admin/dashboard/summary`
- No requiere permiso específico — filtra la respuesta según los permisos del usuario autenticado.
- **Response (200):** `data.metrics` + `data.recent_activity`. Los campos presentes dependen de los permisos:

| Campo | Permiso requerido |
|---|---|
| `metrics.users` (total, active, suspended) | `admin.users.manage` |
| `metrics.roles` | `admin.roles.manage` |
| `metrics.active_sessions` | `admin.security.view` + feature `admin_security` |
| `metrics.failed_logins_24h` | `admin.security.view` + feature `login_attempts` |
| `recent_activity.login_attempts` | `admin.security.view` + feature `login_attempts` |
| `recent_activity.audit_logs` | `admin.audit.view` + feature `audit` |

- **Tip:** Renderiza solo las secciones cuyas keys existen en `data.metrics` — no asumas que todas están presentes.

### Service Accounts y API Keys
Base: `/admin/service-accounts`
- `GET /admin/service-accounts` — Lista service accounts (permiso `admin.service_accounts.manage`). Soporta paginación estándar.
- `POST /admin/service-accounts` — Crea un service account.
- `GET /admin/service-accounts/{ulid}` — Detalle de un service account.
- `PUT/PATCH /admin/service-accounts/{ulid}` — Actualiza un service account.

API Keys de un service account (permiso `admin.api_keys.manage`):
- `GET /admin/service-accounts/{ulid}/api-keys` — Lista las API keys del service account.
- `POST /admin/service-accounts/{ulid}/api-keys` — Genera una nueva API key. **La clave solo se muestra una vez en la respuesta** — el frontend debe pedirle al usuario que la guarde.
- `POST /admin/api-keys/{ulid}/rotate` — Rota una API key (invalida la anterior, genera una nueva).
- `DELETE /admin/api-keys/{ulid}` — Revoca una API key permanentemente.

### Policy Center (M5)
Base: `/admin/policies`
- `GET /admin/policies` (permiso `admin.policies.view`): lista policies agrupadas y devuelve `ETag: W/"policy-v{n}"`.
- `GET /admin/policies/{key}` (permiso `admin.policies.view`): detalle por policy.
- `PATCH /admin/policies` (permiso `admin.policies.manage`): actualización batch atómica.

Notas de consumo:
- Si una policy es sensible (`meta.sensitive=true`), la API devuelve `value: null` y `meta.redacted=true`.
- El payload de `GET /admin/policies` incluye `data.degraded=true` cuando el sistema cae en modo degradado y opera con defaults.
- Si `KAAN_FEATURE_POLICY_CENTER=false`, los endpoints responden `404 RESOURCE_NOT_FOUND` (ruta no registrada).

### Paginación estándar
Todos los listados devuelven:
```json
{ "ok": true, "data": { "data": [...], "links": {...}, "meta": { "current_page": 1, "per_page": 15, "total": 42 } } }
```
Parámetros: `?page=N&per_page=N` (default 15).

Regla de límites:
- Hard cap técnico: `per_page <= 1000`.
- Máximo real en runtime: gobernado por Policy Center (`api.pagination.max_per_page`).

### Health y observabilidad mínima
`GET /health`
- Endpoint público para monitoreo básico.
- Incluye:
  - `data.db` (estado de conectividad DB)
  - `data.policy_center_enabled` (si el módulo está activo por feature flag)
  - `data.policy_center_degraded` (si Policy Center no pudo cargar snapshot y opera en fallback)
  - `data.service_accounts_enabled` (si el módulo de Service Accounts / API Keys está activo — `KAAN_FEATURE_API_KEYS=true`)
  - `data.appointments_enabled` (si el modulo de appointments esta activo — `KAAN_FEATURE_APPOINTMENTS=true`)
- No expone detalles sensibles de policies (keys, valores, versiones internas).

---

## 4) Eventos y Templates (Camaleon)

### Eventos

Endpoints:
- `GET /events/{slug}` (público)
- `GET /events` (auth)
- `POST /events` (auth)
- `PUT /events/{slug}` (auth, owner)
- `DELETE /events/{slug}` (auth, owner)
- `GET /events/{slug}/modules` (auth, owner)
- `PUT /events/{slug}/modules` (auth, owner)

Reglas de payload importantes:
- `type` (event): `wedding | quinceanera | graduation | birthday | party | other`
- `date`: `nullable`; si se envía, debe ser fecha futura
- `template_id`: string ULID del template (`public_id` interno)
- `status` en update: `draft | published`

Shape mínimo de evento en respuestas:

```json
{
  "slug": "boda-ana-y-luis",
  "name": "Boda Ana y Luis",
  "type": "wedding",
  "date": "2026-12-15",
  "status": "draft",
  "template": {
    "id": "01KHN2Y1XYWPBEPJGB1104GDZW",
    "name": "Tuscan Garden",
    "event_type": "wedding"
  }
}
```

Notas de consumo:
- En `DELETE /events/{slug}` la API responde `200` con `data: null`.
- Para módulos de evento, `module_key` permitido: `rsvp | gifts | songs | schedule | story | dress_code | gallery | romantic_phrases | attendance | location`.

### Templates

Endpoints:
- `GET /templates` (público)
- `POST /templates` (auth + `role:admin`)
- `PUT /templates/{id}` (auth + `role:admin`)

Reglas de payload importantes:
- `id` expuesto al frontend corresponde al `public_id` del template.
- `event_type`: `wedding | quinceanera | graduation | birthday | party | other`
- `styles` requerido en create (`POST /templates`):
  - `primary_color` (`#RRGGBB`)
  - `accent_color` (`#RRGGBB`)
  - `font_serif`
  - `font_sans`
  - `bg_image_url` opcional

---

## 5) Interceptores y Manejo de Errores

### Errores que debes manejar siempre (must-handle)

| Code | HTTP | Acción Recomendada |
|---|---|---|
| `AUTH_UNAUTHENTICATED` | 401 | Limpiar token → redirigir a Login |
| `AUTH_TOKEN_EXPIRED` | 401 | Intentar `POST /auth/refresh`. Si falla → Login |
| `AUTH_TOKEN_REVOKED` | 401 | Sesión cerrada remotamente → Login |
| `AUTH_SESSION_NOT_FOUND` | 401 | Fail-closed → Login |
| `AUTH_SESSION_EXPIRED` | 401 | Sesión DB expirada → Login |
| `AUTH_USER_INACTIVE` | 403 | Mostrar "Cuenta Suspendida" |
| `AUTH_EMAIL_NOT_VERIFIED` | 403 | Redirigir a pantalla "Verifica tu email" |
| `AUTH_FORBIDDEN` | 403 | Mostrar "Sin permisos" |
| `VALIDATION_ERROR` | 422 | Mapear `error.details` a los inputs del formulario |
| `POLICY_CONFLICT` | 422 | Mostrar conflicto de reglas cruzadas y no asumir retry automático |
| `POLICY_READ_ONLY` | 403 | Bloquear edición en UI para esa policy |
| `POLICY_NOT_FOUND` | 404 | Invalidar cache local de catálogo y refrescar listado |
| `RATE_LIMIT_EXCEEDED` | 429 | Rate limit global o administrativo → leer `Retry-After`, mostrar countdown. **Puede aparecer en cualquier endpoint.** |
| `AUTH_TOO_MANY_ATTEMPTS` | 429 | Leer header `Retry-After`, mostrar countdown (Login) |
| `AUTH_TOO_MANY_REQUESTS` | 429 | Límite de peticiones (p.ej. registro público, reenvío verificación) → leer `Retry-After` |
| `AUTH_ACCOUNT_LOCKED` | 429 | Bloqueo persistente DB → mostrar "Cuenta bloqueada", leer `Retry-After` |
| `NOT_FOUND` | 404 | Recurso no existe → mostrar "No encontrado" / navegar atrás |
| `RESOURCE_NOT_FOUND` | 404 | Ruta no existe → tratar como "módulo no disponible / feature OFF" |
| `VALIDATION_ERROR (Password)` | 422 | Mapear error de complejidad: 12 caracteres, mayúsculas, minúsculas y números. |

> Para el catálogo completo de error codes, ver [`CONTRACTS.md`](CONTRACTS.md) §4.

### Patrón `Retry-After` en 429

Todas las respuestas 429 incluyen el header `Retry-After` (segundos). Ejemplo de Axios interceptor:

```javascript
axios.interceptors.response.use(null, (error) => {
  if (error.response?.status === 429) {
    const retryAfter = error.response.headers['retry-after'];
    // Mostrar countdown de retryAfter segundos
  }
  if (error.response?.status === 401) {
    // Limpiar token y redirigir a login
  }
  return Promise.reject(error);
});
```

### Mapeo de `VALIDATION_ERROR` (422)

```javascript
// error.details = { "email": ["El campo email es obligatorio."], "password": ["La contraseña debe tener al menos 12 caracteres, una mayúscula, una minúscula y un número."] }
Object.entries(error.details).forEach(([field, messages]) => {
  setFieldError(field, messages[0]); // React Hook Form, Formik, etc.
});
```

### Flujo de Refresh recomendado

1. Guardar `expires_in` del login.
2. Antes de que expire: `POST /auth/refresh` con el token actual.
3. Si refresh falla (401) → limpiar token, redirigir a login.
4. Si refresh éxito → actualizar `access_token` local.

---
> Para una referencia técnica exhaustiva de cada campo, consulta el Swagger en `/api/documentation`.
