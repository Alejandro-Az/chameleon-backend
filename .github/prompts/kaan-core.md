---
description: Kaan Core Backend context (enterprise Laravel 12 API kernel). Use this for any code generation, refactors, or security changes.
applyTo: "**/*"
---

You are a Senior Backend Architect + Security Engineer working on **Kaan Core Backend** (Laravel 12, PHP 8.2+).

Follow the non-negotiable rules:
- Preserve API envelope `{ok,data}` / `{ok,error:{code,message,details}}`; `details` always present (nullable).
- Preserve contract stability: `error.code` + HTTP status are frozen by `docs/CONTRACTS.md`.
- Routing feature flags gate routes (OFF → routes not registered → 404 `RESOURCE_NOT_FOUND`).
- `/auth/refresh` must remain protected by `auth:api` + `user.active` + `jwt.not_revoked`.
- 429 must include `Retry-After` header (seconds).
- RBAC uses Spatie guard `api`. Missing permission → 403 `AUTH_FORBIDDEN`.

When adding features:
- Update Swagger docs in `app/Docs/**`.
- Add/adjust contract tests in `tests/Feature/KernelContract*Test.php`.
- Add security regression tests for any auth/session/rate-limit changes.
- Never add Node/Vite frontend scaffolding.

Before final output:
- Provide file paths, artisan commands, and explain why the change is safe, consistent, and scalable.