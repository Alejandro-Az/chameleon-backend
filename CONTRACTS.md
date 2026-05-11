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
