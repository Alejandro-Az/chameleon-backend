# Camaleon — Diseño del Sistema

**Fecha:** 2026-04-28  
**Rama:** feature/camaleon-domain  
**Repos:** `kaan-core-backend` · `kaan-core-frontend`

---

## 1. Visión general

Camaleon es una plataforma SaaS para crear micrositios de eventos (bodas, XV años, graduaciones, cumpleaños, etc.). El dueño del evento (rol `master`) configura su página a través de un CMS acotado: elige un template, activa módulos, los reordena con drag-and-drop y llena el contenido. Los invitados (rol `user`) acceden a la página pública sin login para confirmar asistencia, votar canciones, ver regalos, etc.

El sistema se llama Camaleon porque el mismo layout se adapta visualmente a cada tipo de evento cambiando únicamente el template (paleta, tipografía, imágenes base).

---

## 2. Arquitectura

### Repos y responsabilidades

| Repo | Stack | Rol |
|---|---|---|
| `kaan-core-backend` | Laravel 12 · PHP 8.2 · JWT · Spatie RBAC | API REST bajo `/api/v1/` |
| `kaan-core-frontend` | React 19 · TS · Vite · Tailwind 4 · shadcn | SPA — panel + página pública |

### Roles (Spatie — sin cambios al core)

| Rol | Quién | Acceso |
|---|---|---|
| `admin` | Desarrolladores | Todo — gestión de usuarios, templates, métricas globales |
| `master` | Dueño del evento | CRUD de sus eventos, configurar módulos, ver estadísticas |
| `user` | Invitado al evento | Página pública — RSVP, votar canciones, mesa de regalos |

### Rutas del frontend

| Ruta | Componente | Acceso |
|---|---|---|
| `/e/:slug` | `EventPage` | Público (sin login) |
| `/panel/events` | `MyEvents` | `master` |
| `/panel/events/new` | `CreateEventWizard` | `master` |
| `/panel/events/:slug/edit` | `EventEditor` | `master` (dueño) |
| `/admin/templates` | `TemplatesAdmin` | `admin` |
| `/admin/users` | `UsersAdmin` | `admin` (ya existe) |

---

## 3. Modelo de datos

### Tablas nuevas

```sql
-- Tipos de evento permitidos: wedding, quinceañera, graduation, birthday, party, other
events
  id               bigint PK
  slug             string UNIQUE          -- identificador público
  name             string
  type             enum
  owner_id         bigint FK → users
  template_id      bigint FK → templates
  date             date
  status           enum(draft, published)
  timestamps

templates
  id               bigint PK
  public_id        ulid UNIQUE
  name             string
  event_type       enum (mismo set que events.type)
  default_module_order  json              -- array de module_keys en orden
  styles           json                  -- { primary_color, accent_color, font_serif, font_sans, bg_image_url }
  timestamps

-- Módulos disponibles: rsvp, gifts, songs, schedule, story,
--                      dress_code, gallery, romantic_phrases, attendance
event_module_configs
  id               bigint PK
  event_id         bigint FK → events
  module_key       string
  enabled          boolean default true
  order            integer
  timestamps
  UNIQUE(event_id, module_key)
```

### Tablas migradas de Camaleon (sin cambios de estructura)

`event_locations` · `event_schedules` · `event_gifts` · `event_gift_claims` · `event_stories` · `event_dress_codes` · `event_romantic_phrases` · `event_songs` · `song_votes` · `event_photos` · `guests`

### Tablas de pagos (fase final — SaaS)

```sql
plans          -- free, basic, premium
subscriptions  -- owner_id → users, plan_id, status, expires_at
```

---

## 4. API — rutas del dominio Events

Todas bajo `/api/v1/`. Respuesta siempre con envelope `{ ok, data, error }`.

### Eventos

| Método | Ruta | Roles | Descripción |
|---|---|---|---|
| GET | `/events/:slug` | público | Datos del evento + módulos activos ordenados |
| POST | `/events` | master | Crear evento |
| PUT | `/events/:slug` | master (dueño) | Editar evento |
| DELETE | `/events/:slug` | master (dueño) | Eliminar evento |
| GET | `/events` | master | Mis eventos |

### Módulos

| Método | Ruta | Roles | Descripción |
|---|---|---|---|
| GET | `/events/:slug/modules` | master | Obtener config de módulos |
| PUT | `/events/:slug/modules` | master | Actualizar orden y estado de todos los módulos |

### Templates

| Método | Ruta | Roles | Descripción |
|---|---|---|---|
| GET | `/templates` | público | Listar templates (con filtro por event_type) |
| POST | `/templates` | admin | Crear template |
| PUT | `/templates/:public_id` | admin | Editar template |

### Módulos específicos (por evento)

| Ruta base | Módulo |
|---|---|
| `/events/:slug/rsvp` | Confirmar asistencia (POST público) |
| `/events/:slug/guests` | Lista de invitados (master) |
| `/events/:slug/gifts` | Mesa de regalos (GET público, CRUD master) |
| `/events/:slug/songs` | Canciones (GET público, CRUD master) |
| `/events/:slug/songs/:id/vote` | Votar canción (POST público) |
| `/events/:slug/schedule` | Cronograma (GET público, CRUD master) |
| `/events/:slug/story` | Historia (GET público, PUT master) |
| `/events/:slug/dress-code` | Dress code (GET público, PUT master) |
| `/events/:slug/phrases` | Frases románticas (GET público, CRUD master) |
| `/events/:slug/photos` | Galería (GET público, POST público, DELETE master) |
| `/events/:slug/location` | Ubicación (GET público, PUT master) |
| `/events/:slug/attendance` | Lista de asistencia (GET master) |

---

## 5. Diseño visual (decisiones aprobadas)

### Página pública `/e/:slug`
**Estilo: Cálido Moderno (V1)**
- Fondo crema (`#fdf8f3`)
- Hero con gradiente suave terracota, tipografía serif ligera
- Módulos como cards blancas con sombra suave y acento lateral izquierdo en color primario del template
- Botón CTA sólido en color primario
- El template define: `primary_color`, `accent_color`, `font_serif`, `font_sans`, `bg_image_url`
- El layout es idéntico para todos los tipos de evento — el template cambia la paleta

### Panel del master — Editor de evento
**Estilo: Tabs con estadísticas (C)**
- Tabs: **Módulos** · **Diseño** · **Invitados** · **Ajustes**
- Tab Módulos: lista drag-and-drop con toggle ON/OFF y botón Editar por módulo + mini-stats del evento (confirmados, canciones, regalos)
- Tab Invitados: tabla de guests con estado de RSVP
- Tab Diseño: selector de template (cambiar en cualquier momento)
- Tab Ajustes: nombre, fecha, slug, status (draft/published)
- Botones: Guardar · Publicar · Ver página pública →

### Crear nuevo evento — Wizard en pasos (A)
- **Paso 1:** Tipo de evento (grid con iconos: 💍 Boda · 👑 XV Años · 🎓 Graduación · 🎂 Cumpleaños · 🎉 Fiesta · + Otro)
- **Paso 2:** Elegir template de esa categoría (cards con preview de gradiente + nombre)
- **Paso 3:** Nombre del evento, fecha, slug (auto-generado, editable)

---

## 6. Flujo de agentes de desarrollo

```
backendCore  →  implementa endpoint
     ↓
docsSync     →  actualiza CONTRACTS.md + Swagger
     ↓
apiIntegration → consume endpoint en frontend con tipos exactos
```

Los tres agentes viven en `.claude/agents/` de sus respectivos repos.

---

## 7. Módulo de pagos (fase final)

- Provider: por definir (Stripe / MercadoPago)
- Modelo: suscripción mensual/anual por `master`
- Plans: free (1 evento activo, sin galería), basic (3 eventos, todos los módulos), premium (ilimitado + analytics)
- Implementar después de que todos los módulos de eventos estén completos y probados

---

## 8. Consideraciones de desarrollo

- Backend corre en XAMPP (`htdocs/`) o con `php artisan serve` apuntando al mismo DB
- Frontend corre con `npm run dev` en `localhost:5173`
- `VITE_API_URL=http://localhost:8000/api/v1` en `.env` del frontend
- El slug del evento es el identificador público — nunca exponer el `id` interno
- Las rutas públicas (página del evento, RSVP, votos) no requieren JWT
- Las rutas de `master` requieren middleware `auth:api` + verificación de ownership del evento
- Las rutas de `admin` requieren middleware `auth:api` + permiso Spatie `admin`
