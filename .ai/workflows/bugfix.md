# Bugfix Workflow · Kaan Core

## Skill Profiles requeridos

Este workflow debe usar:

- `/ai/skill-profiles/backend-core.md`

Agregar también:

- `/ai/skill-profiles/eloquent-performance.md` si el bug involucra queries o relaciones
- `/ai/skill-profiles/testing.md` si requiere test de regresión
- `/ai/skill-profiles/review.md` para validar que el fix no introduzca deuda técnica

Si un workflow requiere skill profiles, estos deben leerse antes de iniciar exploration.

---

## Objetivo

Corregir el problema real con el menor cambio correcto posible, sin degradar arquitectura.

---

## Fase 1 · Reproducción

Antes de corregir:

1. Definir el bug exacto.
2. Identificar el comportamiento esperado.
3. Identificar el comportamiento actual.
4. Determinar si el bug es:
   - funcional
   - de validación
   - de seguridad
   - de permisos
   - de integración
   - de performance
5. Reproducir el bug con evidencia clara.

Regla crítica:
No corregir síntomas sin entender la causa.

---

## Fase 2 · Root Cause Analysis

Identificar la causa raíz:

1. ¿Está en rutas?
2. ¿Está en controller?
3. ¿Está en request?
4. ¿Está en service?
5. ¿Está en model / query?
6. ¿Está en middleware?
7. ¿Está en permisos?
8. ¿Está en contrato API?
9. ¿Está en supuestos de integración frontend?

Regla crítica:
No aplicar parches si la causa raíz sigue viva.

---

## Fase 3 · Scope Control

Definir el alcance mínimo correcto del cambio.

Preguntas obligatorias:

1. ¿Qué archivo debe cambiar realmente?
2. ¿Qué archivo parece afectado pero no debe tocarse?
3. ¿Se requiere migración?
4. ¿Se requiere test de regresión?
5. ¿Se afecta Swagger?
6. ¿Se afecta integración frontend?
7. ¿Se afecta documentación?

---

## Fase 4 · Fix Strategy

La estrategia debe:

1. resolver la causa raíz
2. minimizar side effects
3. respetar arquitectura
4. evitar hacks
5. evitar cambios no relacionados

Regla crítica:
Un bugfix no es excusa para refactor amplio no solicitado, salvo necesidad real.

---

## Fase 5 · Implementation

Al corregir:

1. modificar solo lo necesario
2. mantener convenciones
3. no introducir deuda técnica nueva
4. no cambiar contrato API sin explicarlo
5. no alterar permisos sin justificarlo

---

## Fase 6 · Regression Testing

Evaluar si requiere:

1. test de regresión
2. test de autorización
3. test de validación
4. test de edge case relacionado

---

## Fase 7 · Security & Contract Check

Verificar:

1. ¿El fix rompe auth?
2. ¿El fix rompe RBAC?
3. ¿El fix rompe guard `api`?
4. ¿El fix rompe envelope?
5. ¿El fix expone datos?
6. ¿El fix cambia comportamiento consumido por frontend?

---

## Fase 8 · Hygiene Review

Antes de cerrar:

1. eliminar scripts de depuración
2. eliminar logs temporales
3. eliminar código experimental
4. eliminar tests basura
5. verificar que no haya ghost code

---

## Definition of Done

Un bugfix solo está terminado si:

- corrige la causa raíz
- no rompe arquitectura
- no rompe contrato API
- no deja código temporal
- tiene protección razonable contra regresión