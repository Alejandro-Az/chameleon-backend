# Kaan Core Backend — Production Readiness Checklist

> **Objetivo:** Dejar claro cuándo una instalación de **Kaan Core Backend** está realmente lista para producción / clientes (nivel premium/enterprise) y qué condiciones debe cumplir cualquier proyecto que reutilice este core.

Este checklist asume que ya seguiste la guía de [`DOCUMENTATION.md`](../DOCUMENTATION.md) y de [`docs/CONTRACTS.md`](./CONTRACTS.md). Aquí se resumen **requisitos mínimos no negociables**.

---

## 1. Entorno de aplicación

- **APP_ENV**
  - Debe ser: `staging` o `production` (no `local`).
  - Cualquier despliegue hacia clientes debe usar `APP_ENV=production`.

- **APP_DEBUG**
  - Debe estar en: `false` en `staging` y `production`.
  - Nunca se debe desplegar con `APP_DEBUG=true` fuera de entornos locales/isolados.

- **APP_URL**
  - Debe apuntar a la URL real del backend (con HTTPS en producción).
  - Ejemplo: `https://api.kaanforge.com`.

- **Claves criptográficas**
  - `php artisan key:generate` ejecutado una sola vez por entorno.
  - `php artisan jwt:secret` ejecutado una sola vez por entorno.
  - **Nunca** versionar `.env` ni exponer `APP_KEY` o `JWT_SECRET`.

---

## 2. Base de datos y migraciones

- **Migraciones al día**
  - `php artisan migrate` ejecutado sin errores.
  - No deben quedar migraciones pendientes en entornos productivos.

- **Usuario admin inicial**
  - `php artisan kaan:install` completado sin errores.
  - El usuario admin bootstrap **NO** debe usar la contraseña por defecto.
    - `KAAN_ADMIN_EMAIL` y `KAAN_ADMIN_PASSWORD` **deben** definirse en `.env`.
    - La contraseña debe cumplir política estricta: mínimo 12 caracteres, mayúsculas, minúsculas, números, símbolos, y no estar en diccionarios comunes.

- **Índices mínimos**
  - Asegurarse de que las migraciones que crean índices para `auth_sessions`, `login_attempts` y `audit_logs` han corrido (ver sección *Performance Contract* en `DOCUMENTATION.md`).

---

## 3. Variables de entorno críticas

### 3.1 JWT

- `JWT_SECRET`:
  - Debe estar presente y no vacío.
  - Debe haber sido generado con `php artisan jwt:secret`.

- `JWT_TTL` / `JWT_REFRESH_TTL`:
  - Revisar que los valores son compatibles con la política del producto:
    - Tokens demasiado largos aumentan superficie de riesgo.
    - Tokens demasiado cortos pueden degradar UX.

### 3.2 Auth y Registro

- `KAAN_AUTH_ALLOW_PUBLIC_REGISTRATION`:
  - Debe estar alineado con el modelo de negocio del proyecto.
  - Si está en `true`, revisar:
    - Política de verificación de email (`KAAN_AUTH_REQUIRE_VERIFIED_EMAIL`).
    - Estado por defecto de registros (`KAAN_AUTH_REGISTRATION_DEFAULT_STATUS`).

- `KAAN_AUTH_REQUIRE_VERIFIED_EMAIL`:
  - En la mayoría de escenarios de producción se recomienda `true`.

- `KAAN_AUTH_REGISTRATION_DEFAULT_STATUS`:
  - Para productos B2C típicos: `active`.
  - Para productos de alto riesgo: considerar `pending` + activación manual/admin.

### 3.3 Retención y pruning

- `KAAN_AUDIT_RETENTION_DAYS`:
  - Debe definirse según política legal/compliance del cliente.
  - No dejar indefinido si el volumen de eventos es alto.

- `KAAN_SECURITY_SESSIONS_EXPIRED_DAYS` / `KAAN_SECURITY_SESSIONS_REVOKED_DAYS`:
  - Ajustar para equilibrar:
    - Auditoría/forense vs. tamaño de tabla `auth_sessions`.

- `KAAN_SECURITY_LOGIN_ATTEMPTS_RETENTION_DAYS`:
  - Ajustar según se necesite histórico de ataques de fuerza bruta.

---

## 4. Feature flags y superficie expuesta

### 4.1 Core Capabilities (siempre activas)

- `auth_sessions` y `rbac` son **core capabilities obligatorias** del kernel. No aparecen como feature flags y están siempre activas.
- `auth_sessions` garantiza fail-closed (revocación inmediata de tokens).
- `rbac` garantiza control de acceso basado en roles y permisos.

### 4.2 Feature Flags

- Revisar en `config/kaan.php` (o vía entorno) que:
  - `KAAN_FEATURE_ADMIN` solo esté activo en proyectos que necesiten el panel de administración sobre este mismo backend.
  - `KAAN_FEATURE_AUDIT` esté activo en todos los entornos donde la auditoría es requisito (recomendado para producción).
  - `KAAN_FEATURE_ADMIN_SECURITY` esté activo si se va a usar el módulo de observabilidad de login attempts.
  - `KAAN_FEATURE_API_KEYS` esté activo solo si el proyecto requiere autenticación M2M con Service Accounts y API Keys.
  - `KAAN_FEATURE_POLICY_CENTER` esté activo solo si el proyecto necesita configuración dinámica de políticas en runtime.

- Si usas caché de rutas en producción, siempre regenera la configuración también para que los feature flags tomen efecto:
  - `php artisan config:clear && php artisan route:clear`
  - `php artisan config:cache && php artisan route:cache`

---

## 5. Scheduler / Cron (OBLIGATORIO)

En entornos `staging` y `production` **es obligatorio** configurar el scheduler de Laravel. 

- Cron recomendado (usar ruta absoluta de PHP y posicionarse en el directorio):

```bash
* * * * * cd /ruta/al/proyecto && /usr/bin/php artisan schedule:run >> /var/log/cron.kaan-core.log 2>&1
```

- Comandos que deben ejecutarse automáticamente (configurados en el scheduler del propio core):
  - `kaan:sessions:prune-expired` — limpia sesiones expiradas y revocadas.
  - `kaan:audit:prune` — limpia `audit_logs` según `KAAN_AUDIT_RETENTION_DAYS`.
  - `kaan:security:prune-login-attempts` — limpia `login_attempts` según configuración.

> **Advertencia:** El core verifica el "latido" (heartbeat) de este scheduler a través de la caché. 
> 1. Si tu `CACHE_STORE` es `array`, el latido se perderá y el Health Check fallará. **Usa `redis`, `memcached` o `database` en producción.**
> 2. Si el cron no está corriendo, operaciones críticas de health check marcarán `FAIL`. El límite de tiempo por defecto es 5 minutos, pero puedes ajustarlo con `KAAN_SCHEDULER_HEARTBEAT_MAX_AGE_MINUTES`. Sin scheduler, las tablas crecerán indefinidamente y el rendimiento se degradará.

---

## 6. Email, colas y enlaces frontend

- **Colas**
  - El sistema de colas (`QUEUE_CONNECTION`) debe estar configurado en producción con un worker real (Redis, DB, SQS).
  - **No se recomienda usar `sync`** en producción debido al riesgo de timeout en la request HTTP (útil solo para fase alpha/testing).
  - Usado para envío de correos de verificación y notificaciones.

- **Enlaces frontend (headless)**
  - `KAAN_FRONTEND_VERIFY_EMAIL_URL` apunta al frontend real para verificación.
  - `KAAN_FRONTEND_RESET_PASSWORD_URL` apunta a la pantalla de reset del frontend.

- **Correo**
  - Configuración probada en entorno real sin drivers locales (`log` o `array`), garantizando que los emails transaccionales alcanzan su destino.

---

## 7. Seguridad de credenciales y defaults

- `.env.example`:
  - Debe usarse solo como referencia para desarrollo.
  - En producción, **siempre** crear un `.env` nuevo y adaptado al entorno del cliente.

- Credenciales por defecto:
  - El sistema de instalación bloqueará el despliegue en producción si detecta:
    - `APP_DEBUG=true`
    - `KAAN_ADMIN_PASSWORD` trivial, débil o por defecto.
    - `JWT_SECRET` o `APP_KEY` vacíos.

---

## 8. Observabilidad mínima

Aunque Kaan Core ya registra auditoría y login attempts, para entornos enterprise es recomendable:

- Adoptar un formato de logs estructurados (JSON) para el backend.
- Generar y requerir un ID de correlación (ej. header `X-Request-Id`) en Request/Response si no está presente, y registrarlo junto con la IP y el User Agent en logs relevantes y de auditoría.
- Incluir métricas clave: conteo de `AUTH_UNAUTHENTICATED`, `AUTH_FORBIDDEN` y `AUTH_TOO_MANY_ATTEMPTS`.
- La implementación concreta dependerá de la topología (ELK, Loki, Datadog) del proyecto que consuma el core.

---

## 9. Testing antes de exponer a clientes

Antes de considerar que una instancia de Kaan Core está lista para producción:

- Ejecutar suite de tests en base de datos en memoria o local:
  ```bash
  php artisan test
  ```

- Ejecutar validación de "smoke" real en MySQL (Staging):
  ```bash
  php artisan migrate:fresh --seed
  php artisan l5-swagger:generate
  ```

- Para cada nuevo proyecto que reutilice el core, añadir tests propios que cubran flujos de negocio específicos y verifiquen contratos.

---

## 10. Reutilización en nuevos proyectos

Cuando arranques un proyecto nuevo usando este core:

1. Clonar o instalar Kaan Core como base.
2. Ajustar `.env` siguiendo este checklist (no copiar `.env.example` sin cambios).
3. Revisar y configurar:
   - Feature flags (`KAAN_FEATURE_*`).
   - Política de registro/verificación.
   - Retención/pruning acorde al cliente.
4. Ejecutar `php artisan kaan:install` y validar:
   - Que los checks de seguridad incorporados pasen con éxito (Health Check: OK).
   - Rutas y contratos (`/api/v1/*`) expuestos según lo esperado.
5. Ejecutar `php artisan kaan:health` para monitorizar posibles lagunas en la configuración.
6. Solo entonces comenzar a añadir módulos específicos del proyecto (nuevos controladores, rutas y modelos).

---

## 11. Plan de Rollback por Release (Obligatorio)

Cada release debe documentar explícitamente su estrategia de reversión antes de desplegar:

### 11.1 Clasificación de migraciones

- **Reversible**: la migración tiene `down()` seguro y probado.
- **Potencialmente irreversible**: hay pérdida de datos, cambios destructivos, o dependencias externas no reversibles.

Para cada migración del release, declarar en la nota de release:
- tipo (`reversible` o `potencialmente irreversible`)
- riesgo
- prerequisito de respaldo

### 11.2 Previo al deploy (DB)

1. Tomar backup/snapshot verificable de base de datos.
2. Confirmar punto de restauración y tiempo objetivo de recuperación (RTO).
3. Confirmar ventana de mantenimiento y responsable de ejecución.

### 11.3 Rollback de aplicación (código)

1. Revertir a tag/commit estable previo.
2. Limpiar y regenerar cachés:
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   ```
3. Ejecutar smoke tests críticos (`/auth/login`, `/auth/me`, endpoints admin principales).

### 11.4 Rollback de base de datos

Escenario A: migraciones reversibles
1. Ejecutar rollback controlado de migraciones del release.
2. Verificar integridad de tablas/índices críticos.

Escenario B: migraciones potencialmente irreversibles
1. Restaurar backup/snapshot tomado antes del deploy.
2. Validar consistencia de datos y conectividad del aplicativo.

### 11.5 Criterio de cierre post-rollback

El rollback se considera exitoso solo si:
- health checks están en verde
- autenticación JWT + sesiones funcionan
- RBAC y endpoints admin críticos responden correctamente
- no hay drift entre contrato esperado y Swagger publicado
