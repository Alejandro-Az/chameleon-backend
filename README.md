# 📘 Kaan Core Backend

[![Laravel Version](https://img.shields.io/badge/Laravel-12-FF2D20.svg)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.2-777BB4.svg)](https://php.net/)

**Kaan Core Backend** es un backend API reutilizable diseñado como **kernel de identidad y seguridad** para múltiples proyectos.

Incluye JWT + refresh, **sesiones server-side con revocación inmediata** (`auth_sessions`, fail-closed), RBAC (Spatie), auditoría forense, registro público por feature flag, un **Security Center** (self-service + observabilidad admin) y **Policy Center** para configuraciones dinámicas con fallback degradado.

## 📚 Hub de Documentación

Todo el sistema está rigurosamente documentado para garantizar una adopción Enterprise:

* 💼 **[Resumen Comercial (docs/PITCH_COMERCIAL.md)](docs/PITCH_COMERCIAL.md)** - Descripción ejecutiva y beneficios del proyecto orientada a clientes.
* 📖 **[Arquitectura y Módulos Core (DOCUMENTATION.md)](DOCUMENTATION.md)** - La guía principal de la arquitectura del proyecto.
* 🤝 **[Contratos de API (docs/CONTRACTS.md)](docs/CONTRACTS.md)** - Estructura estricta de sobres de respuesta (Envelopes) y códigos de error.
* 🛡️ **[Security Center (docs/security-center.md)](docs/security-center.md)** - Especificaciones del motor de auditoría y protección contra fuerza bruta.
* ⚙️ **[Policy Center (docs/policy-center.md)](docs/policy-center.md)** - Configuración dinámica, cache versionado, ETag y redacción/cifrado de policies sensibles.
* 🚀 **[Guía de Integración Frontend (docs/frontend-integration.md)](docs/frontend-integration.md)** - Manual paso a paso para consumir esta API desde React/Vue.
* 🛂 **[Production Readiness (docs/PRODUCTION-READINESS.md)](docs/PRODUCTION-READINESS.md)** - Checklist obligatorio (deployment gate) para entornos productivos y staging.
* 📜 **[Historial de Cambios (CHANGELOG.md)](CHANGELOG.md)** - Registro detallado de todas las versiones, características añadidas y correcciones.

---

## 🚀 Instalación rápida

### Requisitos
- PHP >= 8.2
- Composer
- MySQL 8.0+ (o SQLite para tests)

### Pasos

```bash
git clone <repo-url> && cd kaan-core-backend
composer install

cp .env.example .env
php artisan key:generate
php artisan jwt:secret

php artisan kaan:install
```

---

## 🏛️ Core Capabilities (siempre activas)

| Capacidad          | Descripción                                                   |
| ------------------ | ------------------------------------------------------------- |
| `auth_sessions`    | Sesiones JWT en DB + validación estricta (fail-closed).       |
| `rbac`             | Roles y permisos (Spatie, guard `api`). Siempre activos.      |

> ⚠️ Estas capacidades no aparecen como feature flags porque son obligatorias y no desactivables.

## 🎛️ Feature Flags (módulos)

Los módulos se activan desde `.env` (ver `config/kaan.php`).

| Flag                           | Default | Descripción                                                   |
| ------------------------------ | ------- | ------------------------------------------------------------- |
| `KAAN_FEATURE_ADMIN`           | `true`  | Endpoints admin (Users/Roles/Permissions + módulos internos). |
| `KAAN_FEATURE_AUDIT`           | `true`  | Auditoría forense + `/admin/audit-logs`.                      |
| `KAAN_FEATURE_ADMIN_SECURITY`  | `true`  | Endpoints admin de seguridad (`/admin/security/*`).           |
| `KAAN_FEATURE_LOGIN_ATTEMPTS`  | `true`  | Registro de intentos de login y bloqueo en DB.                |
| `KAAN_FEATURE_API_KEYS`        | `false` | **Módulo 4**: API Keys / Service Accounts (M2M → exchange).   |
| `KAAN_FEATURE_POLICY_CENTER`   | `false` | **Módulo 5**: Policy Center (`/admin/policies`).              |

---

## 🔒 Capas de seguridad

1. **RateLimiter (cache)**: 5 intentos/min por `IP + identifier(HMAC)` (sin PII en cache).
2. **LoginAttempts (DB)**: bloqueo persistente por identifier tras múltiples fallos.
3. **JWT Sessions strict validation**: revocación inmediata validada contra `auth_sessions`.
4. **RBAC**: permisos para el Control Plane.
5. **Auditoría**: registro forense en `audit_logs`.

---

## 🧪 Tests y Swagger

```bash
php artisan test
php artisan l5-swagger:generate
# http://localhost/api/documentation
```

---

## 🗄️ Política de migraciones (baseline)

El estado actual de las migraciones forma el **baseline** de la versión instalable.

* Antes del baseline: puedes compactar `create_*` para inicialización.
* Después: **migraciones inmutables** (solo nuevas migraciones incrementales).
