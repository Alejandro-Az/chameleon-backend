# Skill Profile · Backend Core

## Skills Activados por Este Profile

**Base obligatoria:**
- `laravel-specialist`
- `laravel-best-practices`
- `php-pro`

**Contextual backend:**
- `laravel-11-12-app-guidelines`

---

## Cuándo Usarlo

Usar este profile cuando la tarea implique:

- Endpoints nuevos o modificación de rutas API
- Controllers, Form Requests, Services, API Resources
- Configuración o modificación de middleware
- Cambios en el sistema de permisos (RBAC / spatie)
- Cambios en autenticación o ciclo de vida JWT
- Integración backend general
- Cambios estructurales del core

---

## Comportamiento Esperado

Al usar este profile, el agente debe:

- Respetar arquitectura Laravel profesional
- Mantener separación de responsabilidades entre capas
- No meter lógica compleja en controllers — delegarla a Services
- Respetar el envelope `{ ok, data, error }` en todas las respuestas
- Respetar el guard `api` y la autenticación JWT
- Respetar el sistema RBAC y los middleware de seguridad
- Evitar overengineering — preferir soluciones simples si resuelven el problema
- Mantener naming consistente con el resto del codebase
- Priorizar claridad y mantenibilidad sobre inteligencia

---

## Flujo de Diseño Preferido

```
Controller → Form Request → Service → API Resource
```

- **Controller:** orquesta, no procesa
- **Form Request:** valida y autoriza
- **Service:** contiene la lógica de negocio
- **API Resource:** serializa la respuesta

Cada capa es opcional según la complejidad real de la tarea. No crear capas innecesarias.

---

## Anti-patterns

No hacer:

- Lógica de negocio pesada directamente en el controller
- Validación compleja inline cuando corresponde un Form Request
- Respuestas que no sigan el envelope estándar
- Cambios de contrato API sin explicación y sin actualizar Swagger + tests
- Introducir nuevas capas o abstracciones sin necesidad real demostrable
- Devolver modelos Eloquent crudos cuando la salida requiere control explícito
- Lógica de dominio específica dentro del core

---

## Checklist de Entrega

Antes de considerar una tarea completada con este profile, verificar:

- [ ] El endpoint responde con el envelope `{ ok, data, error }`
- [ ] Existe Form Request si hay validación no trivial
- [ ] Existe API Resource si la serialización requiere control
- [ ] El middleware correspondiente está aplicado (`auth:api`, permisos, etc.)
- [ ] Swagger está actualizado
- [ ] Existen tests: éxito, validación fallida, auth fallida, permiso fallido
- [ ] No quedó código muerto ni archivos temporales
- [ ] El naming es consistente con el resto del proyecto

---

_Versión: 1.0.0 · Última actualización: 2026-03-17_