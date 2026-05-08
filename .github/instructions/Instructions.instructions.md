---
description: Describe when these instructions should be loaded
# applyTo: 'Describe when these instructions should be loaded' # when provided, instructions will automatically be added to the request context when the pattern matches an attached file
---
# Copilot Instructions — Kaan Core Backend (v0.1.0-alpha)

You are assisting on **Kaan Core Backend**, an enterprise-grade Laravel 12 API kernel focused on identity & security.

## Golden Rules (Non-negotiable)
1. **Do not break API contracts.**
   - Responses MUST follow the envelope:
     - Success: `{ ok: true, data: ... }`
     - Error: `{ ok: false, error: { code, message, details } }`
   - `error.details` is **always present** (nullable). Never omit the key.
   - `error.code` + HTTP status are the stable contract. Never rely on `error.message`.

2. **Routing feature flags are route-level gating.**
   - If a module flag is OFF, its routes MUST NOT be registered → clients get **404** (`RESOURCE_NOT_FOUND`).
   - If you use `route:cache`, flags must be stable at cache time. Document any flag changes requiring cache clear.

3. **Security is fail-closed.**
   - JWT is not stateless: every token’s `jti` MUST map to a DB `auth_sessions` row.
   - If the session row is missing/expired/revoked → deny (401).
   - `/auth/refresh` MUST stay protected by `auth:api` + `user.active` + `jwt.not_revoked` (no bypass).

4. **RBAC must use Spatie guard `api`.**
   - Missing permission → 403 `AUTH_FORBIDDEN`.
   - Do not change guard names without a major version bump.

5. **429 responses must include `Retry-After` header** (seconds) wherever rate limiting applies.

## Canonical Docs (Source of Truth)
- `docs/CONTRACTS.md` — API contract law, error code catalog, breaking/non-breaking rules.
- `DOCUMENTATION.md` — architecture, modules, performance contract, flags.
- `docs/PRODUCTION-READINESS.md` — production requirements.

When in doubt, follow these files. Do not invent new error codes without updating `docs/CONTRACTS.md`, Swagger docs, and tests.

## Project Structure
- API routes: `routes/api.php` + module files under `routes/api/v1/*`
- Controllers: `app/Http/Controllers/Api/V1/*`
- Middleware: `app/Http/Middleware/*` (security layers: jwt.not_revoked, user.active, user.verified, EnsureHasPermission)
- Models: `User`, `AuthSession`, `LoginAttempt`, `AuditLog`, `UserProfile`
- Swagger: `app/Docs/**` (do not use legacy `app/swagger`)

## Testing Requirements
- All changes must keep `php artisan test` green.
- If you change response shapes/status/error.code/routing gating, add/adjust **Contract Tests**:
  - `tests/Feature/KernelContract*Test.php`
- Any security-affecting change must include a regression test.

## Implementation Style
- Prefer clear services over bloated controllers when logic grows.
- Use Form Requests for validation.
- Keep migrations additive once alpha is tagged (no editing old migrations).
- Keep responses consistent via `HasApiResponse` unless a custom response is required for headers (e.g., Retry-After).

## What NOT to do
- Do not add frontend scaffolding (no Vite/Tailwind). This is API-only.
- Do not use `assertExactJson()` in contract tests unless strictly necessary.
- Do not create new endpoints outside `/api/v1` without versioning.