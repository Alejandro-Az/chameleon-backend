# Camaleon API — Contratos para Frontend

> **IMPORTANTE:** Este archivo es la fuente de verdad para el frontend.
> NO inventar rutas ni nombres de campos. Si no está aquí, no existe.
> Mantenido por el agente `docsSync` al terminar cada módulo.

**Base URL:** `http://localhost:8001/api`  
**Auth:** Bearer JWT en header `Authorization: Bearer {token}`  
**Formato:** JSON en request y response. `Content-Type: application/json`

---

## Autenticación

### POST /v1/auth/login

Autenticación por email (o username según configuración) + password.

Request:

```json
{
	"login": "admin@example.com",
	"password": "Secret123456"
}
```

Success 200:

```json
{
	"ok": true,
	"data": {
		"access_token": "jwt-token",
		"token_type": "bearer",
		"expires_in": 3600,
		"user": {
			"id": "01KHN2Y1XYWPBEPJGB1104GDZW",
			"name": "Admin",
			"email": "admin@example.com",
			"username": "admin"
		}
	}
}
```

Errores relevantes:

- 401 `AUTH_INVALID`
- 403 `AUTH_USER_INACTIVE`
- 403 `AUTH_EMAIL_NOT_VERIFIED`
- 429 `AUTH_TOO_MANY_ATTEMPTS` (incluye header `Retry-After`)
- 429 `AUTH_ACCOUNT_LOCKED` (incluye header `Retry-After`)

### POST /v1/auth/refresh

Requiere `Authorization: Bearer {token}` válido y sesión no revocada.

Success 200:

```json
{
	"ok": true,
	"data": {
		"access_token": "jwt-token-nuevo",
		"token_type": "bearer",
		"expires_in": 3600
	}
}
```

Errores relevantes:

- 401 `AUTH_UNAUTHENTICATED`
- 401 `AUTH_JWT_ERROR`
- 403 `AUTH_EMAIL_NOT_VERIFIED`

### GET /v1/auth/me

Requiere token válido + usuario activo + email verificado.

Success 200:

```json
{
	"ok": true,
	"data": {
		"id": "01KHN2Y1XYWPBEPJGB1104GDZW",
		"name": "Admin",
		"email": "admin@example.com",
		"username": "admin",
		"roles": ["admin"],
		"permissions": ["events.read"]
	}
}
```

### POST /v1/auth/logout

Revoca la sesión/token actual.

Success 200:

```json
{
	"ok": true,
	"data": {
		"message": "Sesión cerrada correctamente."
	}
}
```

---

## Eventos

### GET /v1/events/{slug}

Público. Obtiene detalle de evento por slug.

Success 200 (shape mínimo):

```json
{
	"ok": true,
	"data": {
		"slug": "boda-ana-y-luis",
		"name": "Boda Ana y Luis",
		"type": "wedding",
		"date": "2026-12-15",
		"status": "published",
		"template": {
			"id": "01KHN2Y1XYWPBEPJGB1104GDZW",
			"name": "Tuscan Garden",
			"event_type": "wedding"
		}
	}
}
```

Errores: 404 `NOT_FOUND`.

### GET /v1/events

Privado (master autenticado). Lista eventos del usuario autenticado.

Success 200: `data` es array de eventos.

### POST /v1/events

Privado. Crea evento.

Request:

```json
{
	"name": "Boda Ana y Luis",
	"type": "wedding",
	"date": "2026-12-15",
	"template_id": "01KHN2Y1XYWPBEPJGB1104GDZW"
}
```

Reglas relevantes:

- `name`: requerido, string, max 200.
- `type`: requerido. Enum: `wedding | quinceanera | graduation | birthday | party | other`.
- `date`: opcional (`nullable`), si se envía debe ser fecha futura.
- `template_id`: opcional (`nullable`), string ULID de template (`public_id`).

Success 201: retorna evento creado en `data`.

### PUT /v1/events/{slug}

Privado. Actualiza evento del owner.

Request permitido (todos opcionales):

```json
{
	"name": "Boda Ana y Luis v2",
	"date": "2026-12-20",
	"status": "published",
	"template_id": "01KHN2Y1XYWPBEPJGB1104GDZW"
}
```

Reglas relevantes:

- `status`: enum `draft | published`.
- `date`: permite `nullable`.

Errores relevantes:

- 403 `AUTH_FORBIDDEN` (si no es owner)
- 404 `NOT_FOUND`
- 422 `VALIDATION_ERROR`

### DELETE /v1/events/{slug}

Privado. Elimina evento del owner.

Success 200:

```json
{
	"ok": true,
	"data": null
}
```

### GET /v1/events/{slug}/modules

Privado. Devuelve configuración de módulos del evento.

### PUT /v1/events/{slug}/modules

Privado. Actualiza configuración de módulos.

Request:

```json
{
	"modules": [
		{
			"module_key": "rsvp",
			"enabled": true,
			"order": 1
		}
	]
}
```

`module_key` permitido: `rsvp | gifts | songs | schedule | story | dress_code | gallery | romantic_phrases | attendance | location`.

### GET /v1/events/{slug}/schedules

Privado. Lista actividades (itinerario) del evento.

Success 200:

```json
{
	"ok": true,
	"data": [
		{
			"id": "01KHN2Y1XYWPBEPJGB1104GDZW",
			"title": "Ceremonia",
			"description": "Ceremonia religiosa",
			"starts_at": "2026-12-15 17:00:00",
			"ends_at": "2026-12-15 18:00:00",
			"location_label": "Parroquia San Miguel",
			"location_type": "ceremony",
			"display_order": 0,
			"is_enabled": true
		}
	]
}
```

### POST /v1/events/{slug}/schedules

Privado. Crea actividad del itinerario.

Request:

```json
{
	"title": "Ceremonia",
	"description": "Ceremonia religiosa",
	"starts_at": "2026-12-15 17:00:00",
	"ends_at": "2026-12-15 18:00:00",
	"location_label": "Parroquia San Miguel",
	"location_type": "ceremony",
	"display_order": 0,
	"is_enabled": true
}
```

Reglas relevantes:

- `title`: requerido, string, max 150.
- `description`: opcional, string, max 500.
- `starts_at`: requerido, formato `Y-m-d H:i:s`.
- `ends_at`: opcional, formato `Y-m-d H:i:s`, debe ser >= `starts_at`.
- `location_label`: opcional, string, max 150.
- `location_type`: opcional, string, max 50.
- `display_order`: opcional, integer >= 0.
- `is_enabled`: opcional, boolean.

### PUT /v1/events/{slug}/schedules/{id}

Privado. Actualiza actividad del itinerario. `id` corresponde a `public_id`.

Request permitido (todos opcionales): mismos campos que `POST`.

### DELETE /v1/events/{slug}/schedules/{id}

Privado. Elimina actividad del itinerario. `id` corresponde a `public_id`.

Success 200:

```json
{
	"ok": true,
	"data": null
}
```

### GET /v1/events/{slug}/locations

Privado. Lista ubicaciones del evento.

Success 200:

```json
{
	"ok": true,
	"data": [
		{
			"id": "01KHN2Y1XYWPBEPJGB1104GDZW",
			"name": "Salón principal",
			"address": "Av. Principal 123",
			"maps_url": "https://maps.example.com/location",
			"type": "reception",
			"display_order": 0,
			"is_enabled": true
		}
	]
}
```

### POST /v1/events/{slug}/locations

Privado. Crea ubicación del evento.

Request:

```json
{
	"name": "Salón principal",
	"address": "Av. Principal 123",
	"maps_url": "https://maps.example.com/location",
	"type": "reception",
	"display_order": 0,
	"is_enabled": true
}
```

Reglas relevantes:

- `name`: requerido, string, max 150.
- `address`: opcional, string, max 255.
- `maps_url`: opcional, URL válida, max 500.
- `type`: opcional, string, max 50.
- `display_order`: opcional, integer >= 0.
- `is_enabled`: opcional, boolean.

### PUT /v1/events/{slug}/locations/{id}

Privado. Actualiza ubicación del evento. `id` corresponde a `public_id`.

Request permitido (todos opcionales): mismos campos que `POST`.

### DELETE /v1/events/{slug}/locations/{id}

Privado. Elimina ubicación del evento. `id` corresponde a `public_id`.

Success 200:

```json
{
	"ok": true,
	"data": null
}
```

### GET /v1/events/{slug}/dress-codes

Privado. Lista códigos de vestimenta del evento.

Success 200:

```json
{
	"ok": true,
	"data": [
		{
			"id": "01KHN2Y1XYWPBEPJGB1104GDZW",
			"title": "Formal elegante",
			"description": "Vestimenta formal para la ceremonia",
			"examples": "Traje oscuro, vestido largo",
			"notes": "Evitar tenis y mezclilla",
			"display_order": 0,
			"is_enabled": true
		}
	]
}
```

### POST /v1/events/{slug}/dress-codes

Privado. Crea código de vestimenta del evento.

Request:

```json
{
	"title": "Formal elegante",
	"description": "Vestimenta formal para la ceremonia",
	"examples": "Traje oscuro, vestido largo",
	"notes": "Evitar tenis y mezclilla",
	"display_order": 0,
	"is_enabled": true
}
```

Reglas relevantes:

- `title`: requerido, string, max 150.
- `description`: opcional, string, max 500.
- `examples`: opcional, string, max 1000.
- `notes`: opcional, string, max 1000.
- `display_order`: opcional, integer >= 0.
- `is_enabled`: opcional, boolean.

### PUT /v1/events/{slug}/dress-codes/{id}

Privado. Actualiza código de vestimenta del evento. `id` corresponde a `public_id`.

Request permitido (todos opcionales): mismos campos que `POST`.

### DELETE /v1/events/{slug}/dress-codes/{id}

Privado. Elimina código de vestimenta del evento. `id` corresponde a `public_id`.

Success 200:

```json
{
	"ok": true,
	"data": null
}
```

### POST /v1/events/{slug}/rsvp

Publico. El invitado confirma o actualiza su asistencia usando su codigo de invitacion.

**Auth:** ninguna (publico)  
**Parametros de ruta:** `slug` — slug del evento.

Request:

```json
{
    "invitation_code": "ANA2024",
    "rsvp_status": "yes",
    "guests_confirmed": 2,
    "rsvp_message": "Ahi estaremos!",
    "show_in_public_list": true,
    "dietary_tags": ["vegano"],
    "dietary_notes": "Alergia a los cacahuates"
}
```

Reglas relevantes:

- `invitation_code`: requerido, string, max 100.
- `rsvp_status`: requerido, enum `yes | no | maybe`.
- `guests_confirmed`: requerido cuando `rsvp_status` es `yes` o `maybe`, integer, min 1, max 20.
- `rsvp_message`: opcional, string, max 1000.
- `show_in_public_list`: opcional, boolean.
- `dietary_tags`: opcional, array de strings. Valores permitidos: `vegano`, `vegetariano`, `sin_gluten`, `diabetico`, `sin_lactosa`, `alergia_nueces`.
- `dietary_notes`: opcional, string, max 1000.

Success 200:

```json
{
    "ok": true,
    "data": {
        "id": "01KHN2Y1XYWPBEPJGB1104GDZW",
        "name": "Ana Garcia",
        "email": "ana@ejemplo.com",
        "phone": "+52 55 1234 5678",
        "invitation_code": "ANA2024",
        "invited_seats": 2,
        "rsvp_status": "yes",
        "guests_confirmed": 2,
        "rsvp_message": "Ahi estaremos!",
        "show_in_public_list": true,
        "dietary_tags": ["vegano"],
        "dietary_notes": "Alergia a los cacahuates",
        "seat_label": "Mesa 3",
        "checked_in_at": null,
        "created_at": "2026-05-11T00:00:00+00:00",
        "updated_at": "2026-05-11T00:00:00+00:00"
    }
}
```

Errores relevantes:

- 404 `RSVP_INVITATION_NOT_FOUND`: no existe invitacion con ese codigo para el evento.
- 422 `RSVP_SEATS_EXCEEDED`: `guests_confirmed` supera el limite de la invitacion. Incluye `details.max_seats`.
- 422: validacion fallida `{ "errors": { "campo": ["mensaje"] } }`.

### GET /v1/events/{slug}/guests

Privado (owner del evento). Lista invitados del evento.

**Auth:** Bearer JWT (owner del evento)  
**Parametros de ruta:** `slug` — slug del evento.

Success 200:

```json
{
    "ok": true,
    "data": [
        {
            "id": "01KHN2Y1XYWPBEPJGB1104GDZW",
            "name": "Ana Garcia",
            "email": "ana@ejemplo.com",
            "phone": "+52 55 1234 5678",
            "invitation_code": "ANA2024",
            "invited_seats": 2,
            "rsvp_status": "yes",
            "guests_confirmed": 2,
            "rsvp_message": "Ahi estaremos!",
            "show_in_public_list": true,
            "dietary_tags": ["vegano"],
            "dietary_notes": "Alergia a los cacahuates",
            "seat_label": "Mesa 3",
            "checked_in_at": null,
            "created_at": "2026-05-11T00:00:00+00:00",
            "updated_at": "2026-05-11T00:00:00+00:00"
        }
    ]
}
```

Errores relevantes:

- 401: no autenticado.
- 403 `AUTH_FORBIDDEN`: no es owner del evento.
- 404: evento no encontrado.

### POST /v1/events/{slug}/guests

Privado (owner del evento). Crea un invitado con su codigo de invitacion.

**Auth:** Bearer JWT (owner del evento)  
**Parametros de ruta:** `slug` — slug del evento.

Request:

```json
{
    "name": "Ana Garcia",
    "email": "ana@ejemplo.com",
    "phone": "+52 55 1234 5678",
    "invitation_code": "ANA2024",
    "invited_seats": 2,
    "seat_label": "Mesa 3"
}
```

Reglas relevantes:

- `name`: requerido, string, max 200.
- `email`: opcional, email valido, max 200.
- `phone`: opcional, string, max 50.
- `invitation_code`: requerido, string, max 100, unico por evento (`event_id + invitation_code`).
- `invited_seats`: opcional, integer, min 1, max 50.
- `seat_label`: opcional, string, max 100.

Success 201:

```json
{
    "ok": true,
    "data": {
        "id": "01KHN2Y1XYWPBEPJGB1104GDZW",
        "name": "Ana Garcia",
        "email": "ana@ejemplo.com",
        "phone": "+52 55 1234 5678",
        "invitation_code": "ANA2024",
        "invited_seats": 2,
		"rsvp_status": "pending",
        "guests_confirmed": null,
        "rsvp_message": null,
        "show_in_public_list": false,
        "dietary_tags": [],
        "dietary_notes": null,
        "seat_label": "Mesa 3",
        "checked_in_at": null,
        "created_at": "2026-05-11T00:00:00+00:00",
        "updated_at": "2026-05-11T00:00:00+00:00"
    }
}
```

Errores relevantes:

- 401: no autenticado.
- 403 `AUTH_FORBIDDEN`: no es owner del evento.
- 422: validacion fallida (incluye unicidad de `invitation_code`).

### PUT /v1/events/{slug}/guests/{id}

Privado (owner del evento). Actualiza datos del invitado. No modifica el estado RSVP.

**Auth:** Bearer JWT (owner del evento)  
**Parametros de ruta:** `slug` — slug del evento. `id` — `public_id` del invitado.

Request (todos opcionales):

```json
{
    "name": "Ana Garcia Actualizada",
    "email": "ana2@ejemplo.com",
    "phone": "+52 55 9999 9999",
    "invited_seats": 3,
    "seat_label": "Mesa 5"
}
```

Reglas relevantes:

- `name`: opcional, string, max 200.
- `email`: opcional, email valido, max 200.
- `phone`: opcional, string, max 50.
- `invited_seats`: opcional, integer, min 1, max 50.
- `seat_label`: opcional, string, max 100.

Success 200: retorna el invitado actualizado en `data` con la misma forma que `POST /guests`.

Errores relevantes:

- 401: no autenticado.
- 403 `AUTH_FORBIDDEN`: no es owner del evento.
- 404: invitado no encontrado en el evento.
- 422: validacion fallida.

### DELETE /v1/events/{slug}/guests/{id}

Privado (owner del evento). Elimina (soft-delete) un invitado.

**Auth:** Bearer JWT (owner del evento)  
**Parametros de ruta:** `slug` — slug del evento. `id` — `public_id` del invitado.

Success 200:

```json
{
    "ok": true,
    "data": null
}
```

Errores relevantes:

- 401: no autenticado.
- 403 `AUTH_FORBIDDEN`: no es owner del evento.
- 404: invitado no encontrado en el evento.

### GET /v1/events/{slug}/attendance

Privado (owner del evento). Lista invitados que ya realizaron check-in.

**Auth:** Bearer JWT (owner del evento)  
**Parametros de ruta:** `slug` — slug del evento.

Success 200:

```json
{
    "ok": true,
    "data": [
        {
            "id": "01KHN2Y1XYWPBEPJGB1104GDZW",
            "name": "Ana Garcia",
            "email": "ana@ejemplo.com",
            "phone": "+52 55 1234 5678",
            "invitation_code": "ANA2024",
            "invited_seats": 2,
            "rsvp_status": "yes",
            "guests_confirmed": 2,
            "rsvp_message": "Ahi estaremos!",
            "show_in_public_list": true,
            "dietary_tags": ["vegano"],
            "dietary_notes": "Alergia a los cacahuates",
            "seat_label": "Mesa 3",
            "checked_in_at": "2026-12-15T17:05:00+00:00",
            "created_at": "2026-05-11T00:00:00+00:00",
            "updated_at": "2026-05-11T00:00:00+00:00"
        }
    ]
}
```

Errores relevantes:

- 401: no autenticado.
- 403 `AUTH_FORBIDDEN`: no es owner del evento.
- 404: evento no encontrado.

### POST /v1/events/{slug}/attendance/{guest}

Privado (owner del evento). Registra check-in de un invitado (estampa `checked_in_at` con la hora actual).

**Auth:** Bearer JWT (owner del evento)  
**Parametros de ruta:** `slug` — slug del evento. `guest` — `public_id` del invitado.  
**Body:** ninguno.

Success 200:

```json
{
    "ok": true,
    "data": {
        "id": "01KHN2Y1XYWPBEPJGB1104GDZW",
        "name": "Ana Garcia",
        "email": "ana@ejemplo.com",
        "phone": "+52 55 1234 5678",
        "invitation_code": "ANA2024",
        "invited_seats": 2,
        "rsvp_status": "yes",
        "guests_confirmed": 2,
        "rsvp_message": "Ahi estaremos!",
        "show_in_public_list": true,
        "dietary_tags": ["vegano"],
        "dietary_notes": "Alergia a los cacahuates",
        "seat_label": "Mesa 3",
        "checked_in_at": "2026-12-15T17:05:00+00:00",
        "created_at": "2026-05-11T00:00:00+00:00",
        "updated_at": "2026-05-11T00:00:00+00:00"
    }
}
```

Errores relevantes:

- 401: no autenticado.
- 403 `AUTH_FORBIDDEN`: no es owner del evento.
- 404: invitado no encontrado en el evento.
- 422 `ATTENDANCE_ALREADY_CHECKED_IN`: el invitado ya tiene check-in registrado.

### DELETE /v1/events/{slug}/attendance/{guest}

Privado (owner del evento). Revierte check-in de un invitado (limpia `checked_in_at`).

**Auth:** Bearer JWT (owner del evento)  
**Parametros de ruta:** `slug` — slug del evento. `guest` — `public_id` del invitado.

Success 200:

```json
{
    "ok": true,
    "data": {
        "id": "01KHN2Y1XYWPBEPJGB1104GDZW",
        "name": "Ana Garcia",
        "email": "ana@ejemplo.com",
        "phone": "+52 55 1234 5678",
        "invitation_code": "ANA2024",
        "invited_seats": 2,
        "rsvp_status": "yes",
        "guests_confirmed": 2,
        "rsvp_message": "Ahi estaremos!",
        "show_in_public_list": true,
        "dietary_tags": ["vegano"],
        "dietary_notes": "Alergia a los cacahuates",
        "seat_label": "Mesa 3",
        "checked_in_at": null,
        "created_at": "2026-05-11T00:00:00+00:00",
        "updated_at": "2026-05-11T00:00:00+00:00"
    }
}
```

Errores relevantes:

- 401: no autenticado.
- 403 `AUTH_FORBIDDEN`: no es owner del evento.
- 404: invitado no encontrado en el evento.
- 422 `ATTENDANCE_NOT_CHECKED_IN`: el invitado no tiene check-in registrado.

---

## Templates

### GET /v1/templates

Público. Lista templates disponibles.

Query opcional:

- `event_type`: `wedding | quinceanera | graduation | birthday | party | other`

Success 200 (shape mínimo por item):

```json
{
	"ok": true,
	"data": [
		{
			"id": "01KHN2Y1XYWPBEPJGB1104GDZW",
			"name": "Tuscan Garden",
			"event_type": "wedding",
			"default_module_order": ["rsvp", "location"],
			"styles": {
				"primary_color": "#e8d5b7",
				"accent_color": "#d6b07f",
				"font_serif": "Cormorant Garamond",
				"font_sans": "Montserrat",
				"bg_image_url": "https://example.com/bg.jpg"
			},
			"created_at": "2026-05-08T12:00:00Z"
		}
	]
}
```

### POST /v1/templates

Privado admin (`role:admin`). Crea template.

Request:

```json
{
	"name": "Rustic Chic",
	"event_type": "wedding",
	"default_module_order": ["rsvp", "location", "schedule"],
	"styles": {
		"primary_color": "#d4a373",
		"accent_color": "#f4a261",
		"font_serif": "Playfair Display",
		"font_sans": "Lato",
		"bg_image_url": "https://example.com/bg.jpg"
	}
}
```

Reglas relevantes:

- `styles` requerido en create.
- `styles.primary_color` y `styles.accent_color` en formato hex `#RRGGBB`.
- `styles.font_serif` y `styles.font_sans` requeridos.

### PUT /v1/templates/{id}

Privado admin. `id` corresponde al `public_id` del template.

Request parcial permitido para:

- `name`
- `event_type`
- `default_module_order`
- `styles`
