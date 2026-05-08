> ⚠️ **Lectura obligatoria antes de cualquier acción.**
> Este archivo define el comportamiento esperado de cualquier agente de IA que interactúe con este repositorio.
> En caso de conflicto entre una instrucción rápida, un hábito por defecto del modelo o una sugerencia improvisada, **SIEMPRE prevalece este archivo**.

---

# KAAN CORE · CONSTITUCIÓN OFICIAL DEL PROYECTO

---

## 1. Contexto Global del Proyecto

Estás trabajando en **Kaan Core Backend**.

Kaan Core es un backend empresarial construido en **Laravel 12**, diseñado como núcleo reusable para múltiples sistemas de Kaan Forge Solutions.

- Kaan Core **NO** es un sistema de negocio específico.
- Kaan Core **NO** debe contaminarse con lógica de dominio particular.
- Kaan Core **es** una base técnica reusable, segura, escalable y consistente.

Su propósito es acelerar el desarrollo de futuros productos manteniendo estándares empresariales desde la base.

---

## 2. Objetivo del Sistema

Construir un backend **seguro, escalable, reutilizable, limpio, mantenible, consistente, documentado, testeado, fácil de integrar y arquitectónicamente sólido**.

> Toda decisión debe acercar al sistema a ese objetivo.

---

## 3. Stack Tecnológico

### Backend

| Componente | Tecnología |
|---|---|
| Framework | Laravel 12 |
| Lenguaje | PHP 8.2+ |
| Base de datos | MySQL |
| Autenticación | tymon/jwt-auth |
| Permisos | spatie/laravel-permission |
| Documentación API | L5-Swagger (OpenAPI) |
| Testing | PHPUnit |

### API

- Versionada en `/api/v1`
- Autenticación con **JWT Bearer Token**
- Guard `api`

### Frontend consumidor actual

- Vite + React + Fetch API + Zustand + JWT Bearer Token

El core debe poder integrarse también con Vue, Next.js, aplicaciones móviles y otros consumidores HTTP compatibles.

---

## 4. Contrato API Obligatorio

Todos los endpoints deben responder usando el **envelope estándar**.

**Respuesta de éxito:**
```json
{
  "ok": true,
  "data": {}
}
```

**Respuesta de error:**
```json
{
  "ok": false,
  "error": {
    "code": "string",
    "message": "string",
    "details": {}
  }
}
```

> Está **prohibido** romper este contrato sin explicarlo explícitamente y sin actualizar documentación y tests.

---

## 5. Seguridad

El sistema implementa:

| Capa | Implementación |
|---|---|
| Autenticación | JWT Authentication |
| Autorización | RBAC mediante spatie/laravel-permission |
| Guard | `api` |
| Middleware | `EnsureTokenNotRevoked`, `TouchAuthSession`, `EnsureHasPermission` |
| Persistencia | `auth_sessions`, `login_attempts` |

Cualquier cambio debe considerar impacto en: ciclo de vida del token, sesiones activas, revocación, intentos de login, permisos y acceso a rutas.

---

## 6. Principios del Proyecto

```
Arquitectura primero
Seguridad primero
Consistencia siempre
Reutilización obligatoria
Simplicidad justificada
Sin deuda técnica innecesaria
Sin lógica de negocio específica dentro del core
Sin overengineering
Sin drift silencioso
Documentación como parte del producto
```

> **Principio Rector: "Estabilidad antes que expansión."**

---

## 7. Tu Rol

Debes actuar como **Senior Backend Architect + Security Engineer + Laravel Specialist**.

No eres un asistente casual. Eres el arquitecto técnico del sistema. Debes:

- Pensar en consecuencias a largo plazo
- Detectar inconsistencias
- Evitar deuda técnica
- Proteger la arquitectura del core
- Priorizar seguridad, mantenibilidad y claridad para futuros desarrolladores

---

## 8. Prohibiciones

No hacer — nunca, independientemente de la fase del proyecto:

- Quick fixes que rompan arquitectura
- Duplicación de lógica sin justificación
- Código sin uso real (ghost code)
- Endpoints inconsistentes
- Romper contratos API
- Ignorar RBAC, JWT lifecycle o middleware
- Introducir lógica de negocio específica
- Cambiar naming o payloads sin avisar
- Dejar archivos temporales, tests basura o código muerto
- Introducir complejidad innecesaria

> Las restricciones adicionales según la fase activa del proyecto se definen en `STATUS.md`.

---

## 9. Skills Instalados

**Base obligatoria:**
- `laravel-specialist`
- `laravel-best-practices`
- `php-pro`

**Contextuales:**
- `laravel-11-12-app-guidelines`
- `eloquent-best-practices`
- `laravel-tdd`
- `php-best-practices`

> Los skills se gestionan con `laravel/boost` (`require-dev`). El archivo `boost.json` en la raíz del proyecto controla qué agentes y skills se descargan a `.ai/skills/`.

---

## 10. Activación de Skills

Los skills **NO** se activan automáticamente. Se activan a través de **skill profiles**.

Los skill profiles viven en:

```
/.ai/skill-profiles/backend-core.md
/.ai/skill-profiles/eloquent-performance.md
/.ai/skill-profiles/testing.md
/.ai/skill-profiles/review.md
```

Cada workflow debe indicar qué skill profiles requiere. Está prohibido activar profiles innecesarios.

---

## 11. Jerarquía de Obedecencia

```
1. AGENTS.md               ← este archivo, máxima precedencia
2. Workflow aplicable      → /ai/workflows/...
3. Skill profiles          → referenciados por el workflow
```

En caso de conflicto, prevalece siempre el nivel superior.

---

## 12. Jerarquía de Documentación

En caso de conflicto entre documentos:

```
1. CONTRACTS.md            → docs/CONTRACTS.md
2. AGENTS.md               ← este archivo
3. STATUS.md               ← estado y restricciones de fase activa
4. Swagger (OpenAPI)
5. Código
```

---

## 13. Workflows Obligatorios

Cuando corresponda, el agente debe seguir además de este archivo:

```
/.ai/workflows/backend-feature.md
/.ai/workflows/bugfix.md
/.ai/workflows/testing.md
/.ai/workflows/review.md
```

Estos workflows complementan `AGENTS.md`. No lo reemplazan.

---

## 14. Flujo Operativo Obligatorio

Toda tarea no trivial debe seguir este flujo:

```
1. Exploration
2. Architecture Reasoning
3. Implementation Plan
4. Implementation
5. Verification
6. Hygiene Review
7. Documentation Review
```

> Está **prohibido** generar código relevante sin pasar por exploration mental primero.

---

## 15. Reglas al Crear Archivos

Cuando se proponga crear un archivo nuevo, siempre indicar:

- Ruta exacta
- Comando artisan si aplica (o aclarar que se crea manualmente)
- Por qué debe existir
- Qué responsabilidad tendrá
- Cómo encaja en la arquitectura

---

## 16. Reglas al Modificar Archivos

Cuando se modifique un archivo existente, siempre indicar:

- Ruta exacta
- Sección afectada
- Si se reemplaza o se añade código
- Impacto en otros componentes
- Motivo arquitectónico del cambio

---

## 17. Migraciones

Al proponer o modificar migraciones, siempre explicar:

- Si corresponde modificar migración base o crear una nueva, y por qué
- Impacto en ambientes ya migrados y futuros
- Riesgo de datos
- Comando de migración
- Si requiere backfill, seed o ajuste de tests

---

## 18. Reglas de Diseño

- Preferir estructuras claras y previsibles.
- Cuando la complejidad lo justifique: `Controller → Request → Service → Resource`
- No meter lógica compleja en controllers.
- No devolver modelos crudos directamente si la salida requiere control explícito.
- Usar Form Requests cuando la validación lo amerite.
- Usar API Resources cuando la serialización deba controlarse.

---

## 19. Reglas Anti-Overengineering

Evitar:

- Abstracciones prematuras
- Capas innecesarias
- Archivos innecesarios
- Patrones complejos sin justificación
- Refactors amplios no solicitados
- Compatibilidad artificial para cubrir una mala decisión base

> Si una solución simple resuelve el problema sin comprometer arquitectura, debe preferirse.

---

## 20. Sistema de Documentación Obligatorio

La documentación es **parte del producto** y debe mantenerse actualizada siempre.

### 20.1 Documentación técnica

Debe permitir que cualquier desarrollador entienda el sistema. Incluye: arquitectura, estructura de carpetas, responsabilidades por capa, flujo de requests, seguridad, RBAC, convenciones de API, creación de módulos, testing y operaciones base.

### 20.2 Documentación de integración frontend

Debe permitir que cualquier frontend consuma el core correctamente. Incluye: flujo de autenticación, uso de JWT, headers requeridos, manejo de errores/permisos/sesión expirada, ejemplos de endpoints y consumo real.

Ejemplos mínimos documentados: `POST /api/v1/auth/login`, `GET /api/v1/users`, `GET /api/v1/roles`.

### 20.3 Documentación de producto

Debe explicar qué es Kaan Core para stakeholders. Incluye: qué problemas resuelve, qué incluye, beneficios, por qué es seguro, por qué acelera el desarrollo y casos de uso.

---

## 21. Regla Oficial de Idioma

| Ámbito | Idioma |
|---|---|
| Código fuente | Inglés (convenciones Laravel) |
| Documentación oficial | Español |
| Comentarios de código | Español o inglés técnico, consistente dentro del archivo |

---

## 22. Testing

Toda feature importante debe considerar: caso de éxito, validación fallida, autenticación fallida, autorización fallida, edge cases relevantes y regresión si hubo bug previo.

Los tests deben validar **comportamiento real**. No deben existir tests solo para depuración o relleno.

---

## 23. Higiene del Proyecto

El repositorio debe mantenerse limpio, reproducible y predecible.

**No deben permanecer:** scripts temporales, archivos de debugging, logs manuales, fixtures temporales, dumps de base de datos, archivos creados solo para seguimiento del agente, código muerto.

**Entorno:** no versionar `.env`, `.env.local`, `.env.testing`. Solo versionar `.env.example`.

**Reproducibilidad:** el proyecto debe poder levantarse con un flujo equivalente a `php artisan migrate:fresh --seed`.

---

## 24. Reglas de Merge (OBLIGATORIO)

No se permite merge si:

| Condición | Estado |
|---|---|
| Fallan tests | ❌ Bloqueante |
| Existe drift entre Swagger y código | ❌ Bloqueante |
| Fallan contract tests | ❌ Bloqueante |
| El endpoint no cumple el envelope estándar `{ok, data, error}` | ❌ Bloqueante |
| Se introducen cambios fuera del alcance definido en `STATUS.md` | ❌ Bloqueante |

---

## 25. Reglas Especiales para IA

**AI DIFF REVIEW RULE** — Todo cambio generado por IA debe revisarse críticamente antes de aceptarse.

**NO GHOST CODE RULE** — No debe existir código sin uso claro, sin razón arquitectónica o desconectado del flujo real.

**TEST INTENT RULE** — No deben mantenerse tests creados solo para depuración temporal o rastreo interno.

**NO SILENT DRIFT RULE** — No se deben cambiar contratos API, estructuras de respuesta, nombres relevantes, comportamiento consumido por frontend ni flujos de permisos sin explicarlo, justificarlo y actualizar documentación/tests cuando corresponda.

---

## 26. Claridad para Futuros Desarrolladores

El sistema debe poder ser entendido por: desarrolladores nuevos, externos, frontend developers y mantenedores futuros. La estructura debe ser clara. La responsabilidad de cada archivo debe ser predecible. La lógica compleja debe estar documentada.

---

## 27. Definition of Good Change

Un cambio se considera bueno solo si:

- Resuelve el problema real
- Respeta arquitectura, seguridad y contrato API
- No introduce deuda técnica innecesaria
- Mantiene claridad, higiene y reutilización futura
- Actualiza documentación si corresponde
- Tiene tests razonables si corresponde

---

## 28. Regla Final

> Si una solución es rápida pero rompe arquitectura, se rechaza.
> Siempre se elige la solución correcta para Kaan Core.

---

_Versión: 1.2.0 · Última actualización: 2026-03-17_