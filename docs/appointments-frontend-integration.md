# Integracion Frontend - Modulo Appointments (v1)

## Objetivo

Este documento define como consumir el modulo de citas v1 desde frontend.

Alcance v1:
- Servicios de citas
- Staff de citas
- Disponibilidad global y por staff
- Excepciones y bloqueos
- Citas self-service
- Citas internas (admin)
- Recordatorios y notificaciones por email

Fuera de alcance v1:
- Precios
- Cupones
- Pagos
- Vinculacion automatica de citas de invitado con un usuario futuro

## Reglas globales

- Base URL: /api/v1
- Envelope exito: { ok: true, data: ... }
- Envelope error: { ok: false, error: { code, message, details } }
- IDs publicos: todos los recursos del modulo exponen id = public_id (ULID string)
- Feature flag: KAAN_FEATURE_APPOINTMENTS
- Capability en health: appointments_enabled

## Permisos del modulo

- appointments.services.manage
- appointments.staff.manage
- appointments.availability.manage
- appointments.bookings.view_all
- appointments.bookings.view_own
- appointments.bookings.create_internal
- appointments.bookings.manage
- appointments.bookings.assign
- appointments.bookings.status.manage

## Endpoints self-service

Requieren auth:api + user.active + user.verified + jwt.not_revoked.

- GET /appointments
  - Query: status, page, per_page
  - Solo devuelve citas del usuario autenticado

- GET /appointments/{appointment}
  - Ownership estricto

- POST /appointments
  - Body:
    - service_id (required)
    - starts_at (required, ISO datetime)
    - customer_notes (nullable)

- POST /appointments/{appointment}/reschedule
  - Body:
    - starts_at (required)
  - Regla de ventana: minimo 24h antes (configurable)

- POST /appointments/{appointment}/cancel
  - Body:
    - reason (nullable)
  - Regla de ventana: minimo 24h antes (configurable)

- GET /appointments/slots
  - Query:
    - service_id (required)
    - date (required, YYYY-MM-DD)

## Endpoints admin

Requieren auth:api + throttle:admin + user.active + user.verified + jwt.not_revoked.

Servicios:
- GET /admin/appointment-services
- POST /admin/appointment-services
- GET /admin/appointment-services/{appointmentService}
- PUT/PATCH /admin/appointment-services/{appointmentService}
- DELETE /admin/appointment-services/{appointmentService}

Staff:
- GET /admin/appointment-staff
- POST /admin/appointment-staff
- GET /admin/appointment-staff/{appointmentStaffProfile}
- PATCH /admin/appointment-staff/{appointmentStaffProfile}
- DELETE /admin/appointment-staff/{appointmentStaffProfile}

Disponibilidad:
- GET /admin/appointment-availability
- PUT /admin/appointment-availability
- GET /admin/appointment-availability/exceptions
- POST /admin/appointment-availability/exceptions
- PATCH /admin/appointment-availability/exceptions/{appointmentException}
- DELETE /admin/appointment-availability/exceptions/{appointmentException}
- GET /admin/appointment-availability/staff/{appointmentStaffProfile}
- PUT /admin/appointment-availability/staff/{appointmentStaffProfile}
- POST /admin/appointment-availability/staff/{appointmentStaffProfile}/exceptions
- PATCH /admin/appointment-availability/staff/{appointmentStaffProfile}/exceptions/{appointmentStaffException}
- DELETE /admin/appointment-availability/staff/{appointmentStaffProfile}/exceptions/{appointmentStaffException}
- POST /admin/appointment-availability/reset-defaults

Citas operativas:
- GET /admin/appointments
- POST /admin/appointments
- GET /admin/appointments/slots
- GET /admin/appointments/{appointment}
- PATCH /admin/appointments/{appointment}
- POST /admin/appointments/{appointment}/assign
- POST /admin/appointments/{appointment}/status
- POST /admin/appointments/{appointment}/cancel
- POST /admin/appointments/{appointment}/reschedule

## Enums

Appointment status:
- pending
- confirmed
- completed
- cancelled
- no_show

Appointment source:
- self_service
- internal

Global exception type:
- holiday
- closed_block
- open_exception

Staff exception type:
- closed_block
- open_exception

Reminder type:
- reminder_24h
- reminder_2h

Reminder status:
- pending
- sent
- failed
- cancelled

## Reglas de slots y autoasignacion

- Se valida resolucion global (default 30 min).
- Se valida horario global por dia.
- Se aplican excepciones globales (holiday, closed_block, open_exception).
- Se valida elegibilidad por servicio-staff.
- Se valida disponibilidad del staff y no traslape de citas.
- Si no se envia empleado preferido, el backend autoasigna entre elegibles disponibles.
- Autoasignacion: prioriza menor carga de citas del dia; empate por public_id.

## Errores del modulo

- APPOINTMENT_SLOT_UNAVAILABLE (422)
- APPOINTMENT_CANCELLATION_WINDOW_CLOSED (403)
- APPOINTMENT_RESCHEDULE_WINDOW_CLOSED (403)
- APPOINTMENT_STATUS_TRANSITION_INVALID (422)

## Filtros y paginacion

Self-service:
- GET /appointments: status, page, per_page

Admin:
- GET /admin/appointments: status, service_id, employee_id, date, page, per_page
- GET /admin/appointment-services: q, is_active, page, per_page
- GET /admin/appointment-staff: q, is_active, page, per_page
- GET /admin/appointment-availability/exceptions: type

## Reglas de ownership y seguridad

- Usuario final solo puede ver/modificar sus citas self-service.
- Admin/master con permisos adecuados pueden operar alcance global.
- Employee opera segun permisos asignados y reglas de acceso del endpoint.

## Notificaciones y recordatorios

Eventos de correo:
- Cita creada
- Cita reagendada
- Cita cancelada

Recordatorios:
- 24h antes
- 2h antes

Solo para citas confirmed con email resoluble (guest_email o user.email).

## Limitaciones explicitas v1

- No hay pricing, cupones ni pagos.
- Invitados pueden existir sin user_id.
- Citas de invitado no aparecen en self-service hasta una fase futura de vinculacion.
