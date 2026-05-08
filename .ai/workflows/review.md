# Review Workflow · Kaan Core

## Skill Profiles requeridos

Este workflow debe usar:

- `/ai/skill-profiles/review.md`

Agregar también:

- `/ai/skill-profiles/eloquent-performance.md` para revisar queries y relaciones
- `/ai/skill-profiles/testing.md` para revisar calidad y cobertura de tests

Si un workflow requiere skill profiles, estos deben leerse antes de iniciar exploration.

---

## Objetivo

Evaluar calidad real del cambio, no solo si "funciona".

---

## Fase 1 · Scope Review

Preguntas iniciales:

1. ¿Qué intentaba resolver el cambio?
2. ¿El cambio realmente resuelve eso?
3. ¿Metió cosas no solicitadas?
4. ¿Faltó algo esencial?

---

## Fase 2 · Architecture Review

Verificar:

1. ¿Respeta arquitectura del core?
2. ¿Evita lógica de negocio específica?
3. ¿Respeta separación de responsabilidades?
4. ¿Evita sobreingeniería?
5. ¿Evita hacks?

---

## Fase 3 · Security Review

Verificar:

1. ¿Respeta JWT?
2. ¿Respeta RBAC?
3. ¿Respeta guard `api`?
4. ¿Respeta middleware?
5. ¿Expone datos sensibles?
6. ¿Abre superficies nuevas de riesgo?

---

## Fase 4 · API Contract Review

Verificar:

1. ¿Respeta envelope?
2. ¿Respeta naming y consistencia?
3. ¿Rompe payloads previos?
4. ¿Cambia responses sin avisar?
5. ¿Swagger requiere actualización?

Regla crítica:
No silent drift.

---

## Fase 5 · Data & Migration Review

Verificar:

1. ¿La migración es correcta?
2. ¿Debió ser nueva o modificar base?
3. ¿Hay riesgo para ambientes existentes?
4. ¿Se explicó impacto?
5. ¿Se consideró integridad de datos?

---

## Fase 6 · Testing Review

Verificar:

1. ¿Hay tests suficientes?
2. ¿Faltan casos críticos?
3. ¿Hay tests basura?
4. ¿Hay regresión cubierta?
5. ¿Los tests reflejan comportamiento real?

---

## Fase 7 · Documentation Review

Verificar:

1. ¿Debe actualizarse documentación técnica?
2. ¿Debe actualizarse integración frontend?
3. ¿Debe actualizarse documentación de producto?
4. ¿Se actualizó Swagger si correspondía?

---

## Fase 8 · Hygiene Review

Verificar:

1. ¿Se agregaron archivos temporales?
2. ¿Se dejaron logs?
3. ¿Se dejó código muerto?
4. ¿Se dejaron fixtures temporales?
5. ¿Hay scripts de depuración?
6. ¿Hay ghost code?

---

## Criterio de rechazo

El cambio debe rechazarse si:

- rompe arquitectura
- rompe contrato API
- ignora seguridad
- introduce deuda técnica innecesaria
- deja basura en el repo
- cambia comportamiento sin explicarlo
- agrega complejidad injustificada

---

## Definition of Done

Un cambio revisado se considera aceptable solo si:

- resuelve el problema real
- respeta el core
- respeta seguridad
- respeta contrato
- respeta higiene
- mantiene claridad para futuros desarrolladores