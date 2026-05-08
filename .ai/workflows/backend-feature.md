# Backend Feature Workflow · Kaan Core

## Skill Profiles requeridos

Este workflow debe usar:

- `/ai/skill-profiles/backend-core.md`

Agregar también:

- `/ai/skill-profiles/eloquent-performance.md` si la feature involucra relaciones, queries o performance de datos
- `/ai/skill-profiles/testing.md` si la feature incluye o modifica tests
- `/ai/skill-profiles/review.md` al cierre para validar calidad final

Si un workflow requiere skill profiles, estos deben leerse antes de iniciar exploration.

---

## Objetivo

Implementar nuevas funcionalidades sin romper:

- arquitectura del core
- contrato API
- seguridad
- documentación
- mantenibilidad
- reutilización futura

---

## Fase 1 · Exploration

Antes de escribir código:

1. Identificar el objetivo exacto de la feature.
2. Identificar si pertenece al core o a lógica de negocio específica.
3. Localizar archivos relacionados:
   - routes
   - controllers
   - requests
   - services
   - models
   - resources
   - middleware
   - permisos
   - tests
   - Swagger
   - documentación relacionada
4. Entender dependencias con:
   - JWT
   - RBAC
   - auth_sessions
   - login_attempts
   - envelope `{ok,data,error}`

Regla crítica:
No proponer implementación sin entender primero el flujo actual.

---

## Fase 2 · Architecture Reasoning

Evaluar:

1. ¿La feature pertenece realmente al core?
2. ¿Requiere permisos nuevos?
3. ¿Afecta autenticación o autorización?
4. ¿Afecta contratos existentes?
5. ¿Requiere migración?
6. ¿Requiere nueva documentación?
7. ¿Debe usar Request + Service + Resource?
8. ¿Hay riesgo de sobreingeniería?

Regla crítica:
No agregar capas si no aportan valor real.

---

## Fase 3 · Implementation Plan

Definir explícitamente:

### Archivos nuevos
Indicar:
- ruta exacta
- razón de existencia
- comando artisan si aplica

### Archivos modificados
Indicar:
- ruta exacta
- sección afectada
- impacto esperado

### Impactos
Evaluar:
- tests
- Swagger
- documentación técnica
- documentación frontend
- documentación de producto si aplica
- RBAC
- seguridad
- migraciones

---

## Fase 4 · Implementation Rules

Al implementar:

1. Mantener código en inglés.
2. Mantener documentación en español.
3. Usar Form Requests para validación cuando corresponda.
4. Usar API Resources cuando la respuesta lo amerite.
5. Mantener envelope estándar.
6. No retornar modelos crudos directamente si la salida debe controlarse.
7. No meter lógica compleja en controllers.
8. Mantener responsabilidad clara por capa.

Preferencia de diseño cuando la complejidad lo justifique:

Controller → Request → Service → Resource

---

## Fase 5 · Security Validation

Verificar:

1. ¿La ruta requiere auth?
2. ¿La ruta requiere permiso específico?
3. ¿Se valida con guard `api`?
4. ¿Se mantiene compatibilidad con JWT?
5. ¿Se afecta `auth_sessions`?
6. ¿Se afecta `login_attempts`?
7. ¿La respuesta evita exponer datos sensibles?

---

## Fase 6 · Testing

Toda feature importante debe incluir:

1. Caso de éxito
2. Caso de validación fallida
3. Caso de autenticación fallida
4. Caso de autorización fallida
5. Edge cases relevantes
6. Test de regresión si aplica

Regla crítica:
No crear tests de relleno.

---

## Fase 7 · Swagger & Documentation

Antes de cerrar:

1. Verificar si Swagger debe actualizarse.
2. Verificar si documentación técnica debe actualizarse.
3. Verificar si documentación de integración frontend debe actualizarse.
4. Verificar si documentación de producto debe actualizarse si cambia algo comercializable o explicable al cliente.

---

## Fase 8 · Hygiene Review

Antes de terminar:

1. Eliminar archivos temporales
2. Eliminar logs manuales
3. Eliminar tests experimentales
4. Eliminar dumps o fixtures temporales
5. Verificar que no haya ghost code
6. Verificar que no haya silent drift

---

## Definition of Done

Una feature solo se considera terminada si:

- respeta arquitectura
- respeta seguridad
- respeta envelope API
- tiene testing suficiente
- no introduce basura en el repo
- actualiza documentación cuando corresponde
- no rompe reutilización futura del core