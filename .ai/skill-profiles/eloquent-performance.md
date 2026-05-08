# Skill Profile · Eloquent Performance

## Skills

Base:
- laravel-specialist
- php-pro

Contextual:
- eloquent-best-practices

## Cuándo usarlo

Usar este profile cuando la tarea implique:

- relaciones Eloquent
- eager loading
- queries complejas
- filtros
- paginación con relaciones
- optimización de acceso a datos
- prevención de N+1
- revisión de performance en capa de datos

## Comportamiento esperado

Al usar este profile, el agente debe:

- evitar N+1 queries
- preferir eager loading cuando corresponda
- mantener queries legibles
- evitar consultas innecesarias
- respetar integridad de relaciones
- no optimizar prematuramente sin motivo real

## Anti-patterns

No hacer:

- relaciones cargadas accidentalmente sin control
- hacks de query para arreglar mal diseño
- scopes oscuros sin claridad
- optimizaciones difíciles de mantener sin justificación