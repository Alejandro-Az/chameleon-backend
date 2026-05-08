# CLAUDE.md

Bootstrap file for Claude Code in this repository.

This file is intentionally concise.
It does not replace `AGENTS.md`.

---

## Mandatory Read Order

Before any non-trivial change, read in this order:

1. `AGENTS.md`
2. `STATUS.md`
3. Relevant workflow in `/ai/workflows/`
4. Referenced skill profiles in `/ai/skill-profiles/`
5. Supporting docs as needed (`docs/CONTRACTS.md`, frontend integration docs, Swagger, etc.)

If documents conflict, follow this priority:

| Priority | Source |
|----------|--------|
| 1 | `docs/CONTRACTS.md` |
| 2 | `AGENTS.md` |
| 3 | `STATUS.md` |
| 4 | Workflow |
| 5 | Skill profile |
| 6 | Swagger |
| 7 | Code |

---

## Project Identity

**Kaan Core Backend** is a reusable enterprise identity and security kernel built on **Laravel 12 + PHP 8.2+**.

**It is:**
- A reusable technical foundation
- An enterprise backend core
- A base for multiple Kaan Forge systems

**It is not:**
- A domain-specific product
- A place for business-specific logic
- A place for shortcuts, hacks, or experiments

> Do not introduce domain-specific logic into the core.

---

## Non-Negotiable Rules

**Always preserve:**
- API envelope contract
- JWT lifecycle consistency
- RBAC consistency
- `guard: api`
- Session persistence and revocation model
- Public IDs in exposed routes where the project convention requires them
- Documentation consistency
- Repository hygiene

**Never introduce:**
- Silent contract drift
- Ghost code
- Temporary debug artifacts
- Low-value tracking tests
- Overengineering
- Business-domain coupling inside the core

---

## API Contract

All endpoints must return the standard envelope.

**Success:**
```json
{
  "ok": true,
  "data": {}
}
```

**Error:**
```json
{
  "ok": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable message",
    "details": {}
  }
}
```

> Breaking this contract without updating tests and documentation is prohibited.

---

## Core Security Model

Core security includes:

- JWT authentication
- RBAC with Spatie + `guard: api`
- Session persistence in `auth_sessions`
- Login attempt controls in `login_attempts`
- Route protection through middleware
- Audit-sensitive mindset for admin/write behavior

**Core middleware includes:**

| Middleware | Purpose |
|------------|---------|
| `EnsureTokenNotRevoked` | Validates token has not been revoked |
| `TouchAuthSession` | Keeps session activity updated |
| `EnsureHasPermission` | Enforces RBAC on protected routes |

---

## Implementation Expectations

When making changes:

- Keep code in **English**
- Keep official documentation in **Spanish**
- Preserve separation of responsibilities
- Avoid complex logic in controllers
- Use **Form Requests** when validation deserves its own layer
- Use **Resources** when response shape must be controlled
- Keep services focused and explicit

**Preferred shape when complexity justifies it:**

```
Controller → Request → Service → Resource
```

---

## Workflows

For non-trivial work, use the matching workflow in `/ai/workflows/`.

| File | Purpose |
|------|---------|
| `backend-feature.md` | New feature development |
| `bugfix.md` | Bug diagnosis and resolution |
| `testing.md` | Test writing and coverage |
| `review.md` | Code review and quality checks |

---

## Skill Profiles

Skills are **not** assumed active automatically.
Use the profiles required by the active workflow from `/ai/skill-profiles/`.

| File | Purpose |
|------|---------|
| `backend-core.md` | Core backend patterns and conventions |
| `eloquent-performance.md` | Query optimization and Eloquent best practices |
| `testing.md` | Testing patterns and coverage strategy |
| `review.md` | Review criteria and quality standards |

> Use only the profiles needed for the task.

---

## Testing Expectations

Tests must validate **real system behavior**.

**Prioritize:**

1. Success paths
2. Validation failures
3. Authentication failures
4. Authorization failures
5. Edge cases
6. Regression coverage for fixed bugs

> Do not leave temporary, tracking, or low-value tests.

---

## Documentation Expectations

Any relevant change must evaluate impact on all three documentation levels:

1. **Technical documentation**
2. **Frontend integration documentation**
3. **Product documentation**

> If behavior changes, docs must be reviewed.

---

## Hygiene Expectations

Before considering work complete, verify:

- [ ] No temp scripts
- [ ] No debug leftovers
- [ ] No manual logs
- [ ] No dead code
- [ ] No unused support files
- [ ] No silent drift
- [ ] No environment leakage

> `.env` must never be versioned. Project setup must remain reproducible.

---

## Key Principle

> If a fast solution conflicts with Kaan Core architecture, **reject it**.