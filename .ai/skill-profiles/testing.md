# Skill Profile · Testing

## Skills

Base:
- laravel-specialist
- php-pro

Testing:
- laravel-tdd
- php-best-practices

## Cuándo usarlo

Usar este profile cuando la tarea implique:

- feature tests
- regression tests
- auth flow tests
- permission tests
- testing de endpoints
- testing de validaciones
- testing de bugs corregidos

## Comportamiento esperado

Al usar este profile, el agente debe:

- validar comportamiento real
- priorizar riesgo sobre volumen
- cubrir success + error + seguridad + edge cases
- usar factories/seeders cuando corresponda
- mantener tests legibles y mantenibles
- evitar tests redundantes

## Anti-patterns

No hacer:

- tests para depuración temporal
- tests de rastreo interno del agente
- tests que solo validan status code sin valor real
- tests frágiles
- tests excesivamente acoplados a implementación interna irrelevante