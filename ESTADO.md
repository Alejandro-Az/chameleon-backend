# Camaleon Backend — Estado del Proyecto

> Archivo de contexto para agentes y desarrolladores.
> Actualizar al cerrar cada plan o tarea significativa.
> **Última actualización:** 2026-05-11

---

## ¿Qué es este proyecto?

`camaleon-backend` es una API REST Laravel 12 para gestión de eventos sociales (bodas, XV, graduaciones, etc.).

- **Base:** Clonado de `kaan-core-backend` (C:\Users\pablo\Documents\KaanCoreAppointments\kaan-core-backend) — plantilla reutilizable con JWT, roles, permisos, Swagger, y arquitectura base.
- **Legacy (solo referencia, NO modificar):** `C:\xampp\htdocs\camaleon` — app Laravel+Blade anterior con toda la lógica de negocio a portar.
- **Frontend (repo separado, pendiente):** `C:\xampp\htdocs\camaleon-frontend` — React, aún no iniciado.

---

## Stack técnico

| Tecnología | Versión | Uso |
|------------|---------|-----|
| Laravel | 12 | Framework principal |
| PHP | 8.3 | Runtime |
| MySQL | 8.x (XAMPP) | Base de datos (`camaleon_dev`) |
| tymon/jwt-auth | ^2.2 | Autenticación JWT |
| spatie/laravel-permission | ^6.24 | Roles y permisos |
| darkaonline/l5-swagger | ^10.1 | Documentación OpenAPI 3.0 |
| PHPUnit | (via Laravel) | Tests |

---

## Entorno local

```
Repo:         C:\xampp\htdocs\camaleon-backend
URL dev:      http://localhost:8001   (php artisan serve --port=8001)
Swagger UI:   http://localhost:8001/api/documentation
DB:           MySQL — camaleon_dev (usuario: root, sin contraseña)
```

### Comandos clave

```bash
php artisan serve --port=8001          # Levantar servidor
php artisan test                       # Correr todos los tests
php artisan test --filter=NombreTest   # Tests de un módulo
php artisan l5-swagger:generate        # Regenerar docs Swagger
php artisan migrate:fresh --seed       # Resetear DB con datos
```

---

## Arquitectura de roles

| Rol | Descripción |
|-----|-------------|
| `admin` | Superadmin del sistema |
| `master` | Organizador del evento (cliente) |
| `user` | Invitado autenticado |
| público | Accede por slug sin auth (`/api/v1/events/{slug}`) |

---

## Agentes Claude disponibles

Ubicados en `.claude/agents/` — invocar en Claude Code desde el directorio del repo:

| Agente | Cómo invocar | Qué hace |
|--------|-------------|----------|
| `modulePorter` | `"modulePorter: migra módulo {nombre}"` | Lee el legacy, crea migration+model+factory+seeder+requests+service+resource+controller+tests siguiendo el patrón kaan-core |
| `testRunner` | `"testRunner: corre tests de {módulo}"` | Corre los tests del módulo, analiza fallos, propone fix exacto |
| `docsSync` | `"docsSync: sincroniza módulo {nombre}"` | Lee rutas+controller+requests+resource y actualiza CONTRACTS.md |

---

## Patrón obligatorio por módulo

Cada módulo DEBE implementarse en este orden. No saltar pasos:

```
1. database/migrations/create_{modulo}_table.php
2. app/Models/{Modulo}.php
3. database/factories/{Modulo}Factory.php
4. database/seeders/{Modulo}Seeder.php
5. app/Http/Requests/{Modulo}/Store{Modulo}Request.php
6. app/Http/Requests/{Modulo}/Update{Modulo}Request.php
7. app/Services/{Modulo}Service.php
8. app/Http/Resources/{Modulo}Resource.php
9. app/Http/Controllers/{Modulo}Controller.php  ← con anotaciones @OA\
10. routes/api.php  ← registrar rutas
11. tests/Feature/{Modulo}Test.php  ← TDD: escribir ANTES del controller
```

### Definición de "módulo completo"

- [ ] `php artisan test --filter={Modulo}` → 100% verde
- [ ] `php artisan l5-swagger:generate` → sin errores
- [ ] `php artisan db:seed --class={Modulo}Seeder` → sin excepción
- [ ] `docsSync` ejecutado → CONTRACTS.md actualizado

---

## Documentos clave

| Archivo | Ubicación | Qué contiene |
|---------|-----------|--------------|
| Spec de diseño | `C:\xampp\htdocs\camaleon\docs\superpowers\specs\2026-05-08-camaleon-api-migration-design.md` | Decisiones de arquitectura aprobadas |
| Plan 0 | `C:\xampp\htdocs\camaleon\docs\superpowers\plans\2026-05-08-plan-0-setup-infraestructura.md` | Setup inicial (COMPLETO) |
| CONTRACTS.md | `C:\xampp\htdocs\camaleon-backend\CONTRACTS.md` | Contratos API para el frontend |

---

## Estado de implementación

### ✅ Plan 0 — Setup + Infraestructura (COMPLETO — 2026-05-08)

| Task | Descripción | Commit |
|------|-------------|--------|
| T1 | Clonar kaan-core-backend → camaleon-backend, git init | `e0f894e` |
| T2 | Configurar .env (MySQL camaleon_dev, APP_NAME=Camaleon) | `8168768` |
| T3 | composer install, APP_KEY, JWT_SECRET, migrate:fresh --seed | `eb6be93` |
| T4 | Configurar L5-Swagger (config, anotaciones, Swagger UI funcional) | `a2767c8` + `4667646` |
| T5 | Crear CONTRACTS.md inicial | `d26656c` |
| T6-T8 | Crear agentes docsSync, modulePorter, testRunner | `8a7faef` |
| T9 | Verificación final — 217 tests verdes, swagger OK, seeds OK | `1c9acc6` |

**Baseline de tests:** 217 passed, 0 failed (2026-05-08)

---

### 🟡 Plan 1 — Módulos Fase 1: Auth base + Events CRUD (EN PROGRESO AVANZADO)

Módulos a implementar:
- Adaptar/verificar Auth (login, registro, refresh token) para Camaleon ✅
- Events CRUD (ya existe estructura en kaan-core — adaptar y documentar con Swagger) ✅
- Rutas públicas de evento por slug ✅
- Event modules (`GET/PUT /api/v1/events/{slug}/modules`) ✅
- Templates (`GET/POST/PUT /api/v1/templates`) ✅

Validaciones ejecutadas:

- `php artisan test` → 217 passed
- `php artisan l5-swagger:generate` → OK
- `php artisan route:list --path=api/v1/events` → 7 rutas
- `php artisan route:list --path=api/v1/templates` → 3 rutas

Pendiente para cierre formal de plan:

- Revisión final de documentación de integración frontend
- Validación de consumo frontend sobre endpoints de Events/Templates en entorno integrado

**Fuente de lógica legacy:**
- `C:\xampp\htdocs\camaleon\app\Http\Controllers\Client\EventController.php`
- `C:\xampp\htdocs\camaleon\app\Http\Controllers\Client\AuthController.php`
- `C:\xampp\htdocs\camaleon\app\Http\Controllers\Api\AuthController.php`

---

### 🟡 Plan 2 — Módulos Fase 2: Info esencial del evento (EN PROGRESO)

Módulos: `schedule`, `location`, `dress_code`

Estado por módulo:
- `schedule` ✅ implementado y documentado
- `location` ✅ implementado y documentado
- `dress_code` ✅ implementado y documentado

**Fuente de lógica legacy:**
- `C:\xampp\htdocs\camaleon\app\Http\Controllers\Client\ScheduleController.php`
- `C:\xampp\htdocs\camaleon\app\Http\Controllers\Client\LocationController.php`
- `C:\xampp\htdocs\camaleon\app\Http\Controllers\Client\DressCodeController.php`

---

### 🟡 Plan 3 — Módulos Fase 3: Flujo core del invitado (EN PROGRESO)

Módulos: `rsvp`, `attendance`

Estado por módulo:
- `rsvp` ✅ implementado — 16/16 tests verdes (2026-05-11)
  - RSVP mapea a tabla `guests` (igual que legacy)
  - Endpoint público: `POST /api/v1/events/{slug}/rsvp` (sin auth)
  - Guest CRUD: `GET/POST/PUT/DELETE /api/v1/events/{slug}/guests` (owner del evento)
  - Seat cap enforced en Service
- `attendance` ✅ implementado — 11/11 tests verdes (2026-05-11)
  - Sin migration nueva — reutiliza `checked_in_at` de `guests`
  - Check-in: `POST /api/v1/events/{slug}/attendance/{guest}`
  - Revertir: `DELETE /api/v1/events/{slug}/attendance/{guest}`
  - Listar: `GET /api/v1/events/{slug}/attendance`

**Fuente de lógica legacy:**
- `C:\xampp\htdocs\camaleon\app\Http\Controllers\Public\RsvpController.php`
- `C:\xampp\htdocs\camaleon\app\Http\Controllers\Client\GuestController.php`

---

### 🔲 Plan 4 — Módulos Fase 4: Interacción del invitado (PENDIENTE)

Módulos: `gallery`, `gifts`, `songs`

**Fuente de lógica legacy:**
- `C:\xampp\htdocs\camaleon\app\Http\Controllers\Public\GuestPhotoController.php`
- `C:\xampp\htdocs\camaleon\app\Http\Controllers\Admin\EventPhotoController.php`
- `C:\xampp\htdocs\camaleon\app\Http\Controllers\Public\GiftController.php`
- `C:\xampp\htdocs\camaleon\app\Http\Controllers\Client\GiftController.php`
- `C:\xampp\htdocs\camaleon\app\Http\Controllers\Public\SongController.php`

---

### 🔲 Plan 5 — Módulos Fase 5: Contenido decorativo (PENDIENTE)

Módulos: `story`, `romantic_phrases`

**Fuente de lógica legacy:**
- `C:\xampp\htdocs\camaleon\app\Http\Controllers\Client\StoryController.php`
- `C:\xampp\htdocs\camaleon\app\Http\Controllers\Client\RomanticPhraseController.php`

---

### 🔲 Frontend React (PENDIENTE — sesión separada)

- Repo: `C:\xampp\htdocs\camaleon-frontend` (no iniciado)
- Dos apps: página pública del evento + panel cliente/master
- Diseño visual: Cálido Moderno V1 (crema + terracota)
- Consume 100% API, JWT en localStorage

---

## Reglas del proyecto

1. **No tocar** `C:\xampp\htdocs\camaleon` — es solo referencia de lógica
2. **TDD obligatorio** — tests antes del Controller en cada módulo
3. **Swagger obligatorio** — `@OA\` en cada endpoint, sin excepción
4. **Seeders obligatorios** — datos realistas para QA en cada módulo
5. **docsSync obligatorio** — CONTRACTS.md actualizado al cerrar cada módulo
6. **No avanzar al siguiente módulo** si el actual tiene tests rojos
7. **Patrón estricto** — Service para lógica, Controller máximo 5 líneas por método
