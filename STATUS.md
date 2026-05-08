> 📌 Este archivo describe el **estado actual del proyecto** y los gaps bloqueantes de la fase vigente.
> A diferencia de `AGENTS.md` (constitución permanente), este archivo **evoluciona con cada fase**.
> Los agentes deben leerlo junto con `AGENTS.md` para entender qué está permitido trabajar en este momento.

---

# KAAN CORE · STATUS

## Fase Actual: Estabilización

Kaan Core se encuentra en **fase de estabilización**, no de expansión funcional.

El objetivo de esta fase es alcanzar un backend:

- ✅ Estable — sin regresiones ni comportamientos inesperados
- ✅ Consistente — contratos, respuestas y estructura uniformes
- ✅ Seguro — todos los endpoints protegidos
- ✅ Reusable — el core sirve a múltiples apps sin modificación

> ❗ **No se están agregando nuevas features.**
> Cualquier cambio que no contribuya directamente a estos cuatro pilares **debe ser rechazado**.

---

## Criterio de Salida de Fase

Esta fase se considera completa cuando:

- Todos los gaps `P0` están en estado `done`
- Todos los gaps `P1` están en estado `done`
- CI verde de forma sostenida
- Sin deuda técnica abierta clasificada como `P0`

---

## Leyenda de Prioridades

| Nivel | Significado |
|---|---|
| `P0` | **Bloqueante.** La fase no puede considerarse completa sin resolverlo. Ningún `P1` debe iniciarse si hay `P0` pendientes. |
| `P1` | **Importante.** Debe resolverse en esta fase, pero no bloquea el trabajo en otros `P1` simultáneos. |

---

## Gaps Prioritarios

### Gap 1 · Global Rate Limiting `P0 · CRÍTICO` ✅

**Problema:** Existen endpoints sin protección de throttling, especialmente en rutas admin o de lectura.

**Definition of Done:**
- [x] Todos los endpoints `/api/v1/*` tienen rate limiting definido
- [x] Endpoints administrativos tienen límites diferenciados
- [x] Existen tests que validan respuestas `429 Too Many Requests`
- [x] No existen endpoints públicos sin protección

| Campo | Valor |
|---|---|
| Owner | `backend` |
| Estado | `done` |

---

### Gap 2 · Contract Tests Coverage `P0` ✅

**Problema:** No todos los módulos tienen tests de contrato (routing ON/OFF con feature flags).

**Brecha concreta:** `admin.audit-logs`, `admin.security`, `admin.policies`, `admin.api-keys`

**Definition of Done:**
- [x] Todos los módulos tienen tests de rutas activas/inactivas
- [x] Feature flags no rompen routing
- [x] Tests de contrato se ejecutan en CI sin skips
- [x] Cualquier cambio en rutas rompe tests si no se actualiza contrato

| Campo | Valor |
|---|---|
| Owner | `backend` |
| Estado | `done` |

---

### Gap 3 · Alineación Swagger / Tests / Código `P0` ✅

**Problema:** Riesgo de drift entre implementación, documentación y pruebas.

**Definition of Done:**
- [x] Swagger refleja exactamente la implementación real
- [x] Tests validan estructura de respuesta `{ ok, data, error }`
- [x] No existen endpoints documentados que no existan en código
- [x] No existen endpoints sin documentar
- [x] Generación de Swagger validada en CI

| Campo | Valor |
|---|---|
| Owner | `backend` |
| Estado | `done` |

---

### Gap 4 · Guía de Adopción del Core `P1` ✅

**Alcance:** El modelo de adopción confirmado es clone del repositorio (template repo). Cada proyecto es una copia independiente. El gap se cierra con documentación completa, no con un mecanismo técnico.

**Definition of Done:**
- [x] Existe `docs/GETTING-STARTED.md` con guía completa de adopción
- [x] Variables de entorno documentadas: obligatorias vs opcionales con contexto claro
- [x] Feature flags documentadas con tabla completa, dependencias y nota sobre caché
- [x] Convenciones de extensión documentadas: qué agregar, dónde y qué no tocar
- [x] Paso a paso funcional desde cero hasta `kaan:health` verde
- [x] `DOCUMENTATION.md §19` actualizado con referencia a la guía

| Campo | Valor |
|---|---|
| Owner | `arquitectura` |
| Estado | `done` |

---

### Gap 5 · Higiene del Repositorio `P1` ✅

**Estado:** `done`
**Responsable:** `backend`

**Definition of Done:**
- [x] Eliminación de archivos temporales y logs innecesarios (§23, §25).
- [x] No existen archivos de debugging en el repositorio.
- [x] `.env.example` consistente.
- [x] No existe código muerto.
- [x] Estructura limpia y profesional.
- [x] `declare(strict_types=1)` en todos los archivos de test.

| Campo | Valor |
|---|---|
| Owner | `backend` |
| Estado | `done` |

---

### Gap 6 · Structured Logging `P1` ✅

**Problema:** Los logs no están estandarizados para consumo en sistemas externos.

**Definition of Done:**
- [x] Logs en formato estructurado (JSON o equivalente)
- [x] Campos mínimos definidos: `timestamp`, `level`, `message`, `context`
- [x] Compatible con herramientas externas (ELK, Loki, Datadog)
- [x] Sin uso de logs en texto plano para producción

| Campo | Valor |
|---|---|
| Owner | `backend` |
| Estado | `done` |

---

## Fuera del Alcance Actual

Las siguientes áreas están **explícitamente fuera del scope** de esta fase:

| Área | Razón |
|---|---|
| SLO / SLA | Preocupación de operación en producción |
| Métricas avanzadas y alertas | Preocupación de operación en producción |
| Chaos testing | Preocupación de operación en producción |
| Incident playbooks | Preocupación de operación en producción |

---

## Instrucciones para Agentes en Esta Fase

Durante la fase de estabilización, todos los agentes deben:

- Trabajar **exclusivamente** en los gaps definidos en este documento
- No proponer nuevas features
- No introducir cambios arquitectónicos no relacionados con los gaps activos
- Respetar el contrato API `{ ok, data, error }` (ver `AGENTS.md §4`)
- Consultar `AGENTS.md` para todas las reglas permanentes de comportamiento

---

_Versión: 1.3.0 · Fase: Estabilización completada · Última actualización: 2026-03-19_