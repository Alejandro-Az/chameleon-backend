# Testing Workflow · Kaan Core

## Skill Profiles requeridos

Este workflow debe usar:

- `/ai/skill-profiles/testing.md`

Agregar también:

- `/ai/skill-profiles/eloquent-performance.md` si el test involucra relaciones, filtros o acceso a datos complejo
- `/ai/skill-profiles/review.md` para validar calidad final del set de tests

Si un workflow requiere skill profiles, estos deben leerse antes de iniciar exploration.

---

## Objetivo

Asegurar cobertura útil, mantenible y alineada al comportamiento real del sistema.

---

## Principios

1. Los tests existen para proteger comportamiento real.
2. Los tests no existen para que la IA se rastree a sí misma.
3. Un test inútil es deuda técnica.
4. Un test frágil es deuda técnica.
5. La cobertura debe priorizar riesgo, no volumen.

---

## Qué debe probarse

Toda feature relevante debe considerar:

1. caso de éxito
2. validación fallida
3. autenticación fallida
4. autorización fallida
5. edge cases
6. regresión si hubo bug previo

---

## Priorización

### Alta prioridad
- auth flow
- RBAC / permisos
- endpoints críticos
- envelope API
- validaciones importantes
- bugs corregidos

### Media prioridad
- filtros
- paginación
- serialización
- resources / transforms

### Baja prioridad
- caminos triviales sin riesgo real
- duplicación de cobertura existente

---

## Tipos de test esperados

Principalmente:
- Feature tests

Y según aplique:
- auth flow tests
- permission tests
- regression tests

---

## Reglas de diseño de tests

1. El nombre del test debe describir comportamiento observable.
2. El test debe ser específico.
3. El test no debe depender de estado oculto.
4. El test no debe usar datos arbitrarios sin intención.
5. Debe usar factories/seeders cuando corresponda.
6. Debe mantenerse legible.

---

## Test Intent Rule

Está prohibido dejar tests que existan solo para:

- depuración temporal
- explorar el código
- confirmar una sospecha puntual del agente
- rastrear un bug ya descartado
- cubrir líneas sin valor funcional

---

## Anti-patterns

No hacer:

- tests gigantes con demasiadas responsabilidades
- asserts vagos
- tests acoplados a implementación interna irrelevante
- tests redundantes
- tests que solo verifican 200 sin validar contenido
- tests que dependen de orden accidental

---

## Seguridad

Todo endpoint protegido debe evaluar:

1. acceso con token válido
2. acceso sin token
3. acceso con permisos insuficientes si aplica
4. acceso con credenciales inconsistentes si aplica

---

## Contrato API

Cuando corresponda, verificar:

1. envelope `{ok,data,error}`
2. estructura de payload
3. códigos de error esperados
4. mensajes o codes relevantes cuando aporten valor

---

## Hygiene Review de tests

Antes de cerrar:

1. eliminar tests experimentales
2. eliminar archivos de soporte no usados
3. eliminar fixtures temporales
4. verificar que el test aporte valor real
5. verificar que no duplique cobertura inútilmente

---

## Definition of Done

Un set de tests solo se considera correcto si:

- protege comportamiento real
- cubre riesgo relevante
- es legible
- es mantenible
- no introduce ruido innecesario