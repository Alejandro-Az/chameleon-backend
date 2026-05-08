# Security Center Kernel

Kaan Core incluye un **Security Center** integrado para proporcionar **visibilidad**, **trazabilidad** y **control** sobre la seguridad de identidades.

Este documento sirve como guía de integración (“hand-off”) para proyectos hijos.

---

## 0) ¿Qué resuelve este módulo?

- **Self-Service:** El usuario puede listar y revocar sus sesiones activas (control de dispositivos).
- **Observabilidad Admin:** Soporte y seguridad pueden monitorear intentos de login y bloqueos en tiempo real.
- **Higiene:** Pruning automático de sesiones, intentos de login y logs de auditoría para mantener la base de datos optimizada.
- **Privacidad:** Implementa enmascaramiento estable de identificadores y **HMAC Throttling** para proteger PII en capas de cache.

---

## 1) Feature Flags

| Flag / Env | Default | Qué controla |
|------------|---------|--------------|
| `KAAN_FEATURE_ADMIN_SECURITY` | `true` | Registra endpoints administrativos `/api/v1/admin/security/*` |
| `KAAN_FEATURE_LOGIN_ATTEMPTS` | `true` | Habilita el registro de intentos y bloqueos persistentes en DB |
| `KAAN_SECURITY_EXPOSE_RAW_LOGIN` | `false` | Permite al admin ver el identificador real (`login_raw`) |

> **Nota:** El self-service de sesiones (`/api/v1/auth/sessions`) es parte del core de identidad. `auth_sessions` es una **core capability** siempre activa (no desactivable).

---

## 2) Endpoints

### A) Self-Service (Usuario)
Base: `/api/v1/auth/sessions`
- `GET /`: Lista sesiones activas (IP, User Agent, Last Seen).
- `DELETE /{id}`: Revoca una sesión específica. Es **idempotente** (devuelve `was_already_revoked`).
- `POST /revoke-others`: Cierra sesión en todos los dispositivos excepto el actual.
- `POST /revoke-all`: Cierra todas las sesiones e invalida el token actual.

### B) Observabilidad Administrativa
Base: `/api/v1/admin/security` (Requiere permiso `admin.security.view`)
- `GET /login-attempts`: Historial de intentos.
  - Filtros: `q` (búsqueda), `ip`, `status` (success/failed/blocked), `from`, `to`.

---

## 3) Contrato de Errores Críticos

| Code | HTTP | Significado |
|------|------|-------------|
| `AUTH_TOKEN_REVOKED` | 401 | La sesión fue revocada (ej. desde otro dispositivo). |
| `AUTH_SESSION_NOT_FOUND` | 401 | Fail-closed: El token es válido pero no tiene sesión activa en DB. |
| `AUTH_TOO_MANY_ATTEMPTS` | 429 | Bloqueo temporal por rate limiting (cache). |
| `AUTH_ACCOUNT_LOCKED` | 429 | Bloqueo persistente por múltiples fallos (DB). |

---

## 4) Pruning y Retención (Ops)

Los comandos de limpieza están programados diariamente en el Kernel. La retención es configurable en el `.env`:

- **Sesiones**: `KAAN_SECURITY_SESSIONS_EXPIRED_DAYS` (Default: 7), `KAAN_SECURITY_SESSIONS_REVOKED_DAYS` (Default: 30).
- **Security Logs**: `KAAN_SECURITY_LOGIN_ATTEMPTS_RETENTION_DAYS` (Default: 30).
- **Audit Logs**: `KAAN_AUDIT_RETENTION_DAYS` (Configurable por cumplimiento normativo).

---

## 5) Permisos RBAC Involucrados

- `admin.security.view`: Permite acceso al catálogo de intentos de login.
- `admin.service_accounts.manage`: Gestión de service accounts (Módulo API Keys).
- `admin.api_keys.manage`: Gestión de API keys (Módulo API Keys).
