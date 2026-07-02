# PROJECT_CONTEXT.md — AI Bootstrap Guide

> **T.N. Memorial Public School Digital Platform**
> Document Version: **v1.0.0** | Status: **Active** | Created: **2026-07-02**
> Authority: `.github/AGENT.md` is the highest governing document in this repository.

This document is a lightweight AI bootstrap guide. Read it in under two minutes before starting any task. It is not a governance document — for governance, read `.github/AGENT.md`. For full current state, read `.github/PROJECT_MEMORY.md`.

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [AI Startup Checklist](#2-ai-startup-checklist)
3. [Repository Map](#3-repository-map)
4. [Technology Snapshot](#4-technology-snapshot)
5. [Backend Snapshot](#5-backend-snapshot)
6. [Frontend Snapshot](#6-frontend-snapshot)
7. [Documentation Snapshot](#7-documentation-snapshot)
8. [Security Snapshot](#8-security-snapshot)
9. [Development Rules](#9-development-rules)
10. [Git Workflow](#10-git-workflow)
11. [Current Progress](#11-current-progress)
12. [Next Immediate Priorities](#12-next-immediate-priorities)
13. [AI Session Bootstrap](#13-ai-session-bootstrap)
14. [Quick Commands](#14-quick-commands)
15. [Revision History](#15-revision-history)

---

## 1. Project Overview

| Field | Value |
|-------|-------|
| **Project Name** | T.N. Memorial Public School Digital Platform |
| **Purpose** | Enterprise-grade school website and ERP platform providing role-based digital administration, academic management, and school operations for administrators, teachers, students, and parents |
| **Vision** | Replace fragmented manual school workflows with a secure, scalable, unified digital platform covering admissions, attendance, examinations, fees, notices, and reporting |
| **Repository Version** | v3.0 — architecture permanently locked at this baseline |
| **AI Rulebook Version** | v1.1.0 (`.github/AGENT.md`) |
| **Current Maturity** | Documentation complete. Backend module scaffolding complete. Integration, database, testing, and frontend implementation pending. |
| **Current Phase** | Implementation Phase — Phase 1 (Foundation) in progress |

---

## 2. AI Startup Checklist

Every AI assistant must complete this checklist before writing a single line of code, documentation, or configuration.

- [ ] Read `.github/AGENT.md` in full — this is the highest authority document
- [ ] Read `.github/PROJECT_MEMORY.md` — this contains the full current repository state
- [ ] Read `.github/PROJECT_CONTEXT.md` — this document, for orientation
- [ ] Read only the specific module or folder relevant to the assigned task
- [ ] Understand the existing architecture before proposing any change
- [ ] Verify the current git branch before making any changes
- [ ] Verify repository status with `git status` before starting
- [ ] Confirm the task does not require renaming, moving, or deleting any file
- [ ] Confirm the task does not require architecture changes (if it does, stop and raise an ADR)
- [ ] Confirm the task stays within the locked technology stack

---

## 3. Repository Map

| Folder | Purpose |
|--------|---------|
| `.github/` | Repository governance. Contains `AGENT.md` (AI rulebook), `copilot-instructions.md` (supplementary AI behaviour), `PROJECT_MEMORY.md` (living state document), and `PROJECT_CONTEXT.md` (this bootstrap guide). |
| `apps/` | Frontend applications. `apps/website/` is the public-facing school website. `apps/erp/` is the role-based ERP dashboard. Both use Next.js, TypeScript, and Tailwind CSS with the App Router. |
| `backend/` | PHP 8.3+ REST API. Contains all domain modules in `backend/app/`, routing in `backend/routes/`, infrastructure in `backend/app/Support/`, `backend/app/Http/`, and `backend/app/Database/`. |
| `database/` | MySQL database artefacts: schema bootstrap SQL, migrations, seeders, stored procedures, views, triggers, and backups. |
| `deployment/` | Deployment assets for Apache, Nginx, Docker, and hosting. Docker is the primary local development environment. |
| `docs/` | Complete 19-folder SDLC documentation suite covering governance, discovery, business, requirements, architecture, database, API, UI, security, testing, deployment, portfolio, ADR, RTM, QMS, appendix, architecture handbook, AI prompts, and development planning. |
| `tests/` | Test suites: `unit/`, `integration/`, `api/`, `ui/`, and `fixtures/`. Currently empty — all test authoring is pending. |
| `assets/` | Static project assets. |
| `scripts/` | Utility and automation scripts. |
| `config/` | Application configuration files. |
| `archive/` | Archived materials from earlier project stages. |
| `templates/` | Document and code templates for consistent output. |
| `tools/` | Development tooling support. |

---

## 4. Technology Snapshot

| Layer | Technology |
|-------|------------|
| **Frontend** | Next.js 14+, TypeScript (strict mode), Tailwind CSS, React Server Components |
| **Backend** | PHP 8.3+, PSR-4 autoloading, custom modular monolith, no third-party framework |
| **Database** | MySQL, PDO with prepared statements, credentials via `.env` |
| **Authentication** | JWT (access + refresh tokens), bcrypt password hashing, token blacklisting on logout, RBAC enforcement via `AuthMiddleware` |
| **Deployment** | Docker (primary local), Apache or Nginx (server), Cloudflare (edge), cloud-agnostic |
| **Documentation** | GitHub Markdown, 19-folder SDLC suite, Conventional Commits |
| **AI Workflow** | All AI contributors governed by `AGENT.md` v1.1.0. Supported tools: ChatGPT, Claude, Gemini, GitHub Copilot, Codex, Cursor, Cline, Continue.dev, Windsurf. |

---

## 5. Backend Snapshot

### Architecture

The backend is a custom modular monolith written in PHP 8.3+. There is no Symfony, Laravel, or other framework. The architecture is built on a hand-crafted dependency injection container (`AppContainer`), bootstrapped by `Bootstrap.php`, with request handling through `RequestHelper` and responses through `ResponseHelper`.

### Module Pattern

Every domain module in `backend/app/` follows an identical, locked internal file pattern:

| File | Responsibility |
|------|---------------|
| `{Module}.php` | Domain entity — data model |
| `{Module}Controller.php` | HTTP handler — delegates to service, returns response |
| `{Module}Service.php` | Business logic — delegates to repository interface |
| `{Module}ServiceInterface.php` | Service contract — all consumers depend on this |
| `{Module}Repository.php` | Data access — PDO queries, prepared statements only |
| `{Module}RepositoryInterface.php` | Repository contract — service depends on this |
| `{Module}Validator.php` | Input validation — invoked by controller before processing |
| `{Module}Response.php` | Response DTO — shapes HTTP output, no raw domain object serialisation |
| `{Module}ListRequest.php` | List/filter input DTO |
| `Create{Module}Request.php` | Create operation input DTO |
| `Update{Module}Request.php` | Update operation input DTO |
| `{Module}Exception.php` | Domain-specific exception |
| `{Module}ServiceProvider.php` | Dependency wiring — binds interfaces to implementations |

**Architecture is permanently frozen.** This pattern may not be changed, shortened, or restructured.

### Implemented Domain Modules

`Auth`, `Student`, `Teacher`, `Attendance`, `AttendanceRecord`, `AcademicClass`, `AcademicSession`, `Section`, `Subject`, `Period`, `Timetable`, `HolidayCalendar` — all 12 fully implemented with the complete file pattern.

### Infrastructure Modules

`Database` (`ConnectionManager.php`), `Http` (`Pipeline`, `RequestHelper`, `ResponseHelper`), `Support` (`AppContainer`, `Bootstrap`, `ErrorHandler`, `Logger`) — all implemented.

### Integration Gaps

- `backend/routes/api.php` — contains only a `/health` stub. All domain routes are pending registration.
- `backend/app/Support/Bootstrap.php` — infrastructure wiring complete; module `ServiceProvider` wiring into the container is pending.

---

## 6. Frontend Snapshot

### Website (`apps/website/`)

Next.js App Router application with route groups `(public)`, `(auth)`, and `(erp)` present and locked. Root `layout.tsx`, `globals.css`, `loading.tsx`, and `not-found.tsx` are implemented. `components/`, `hooks/`, `lib/`, `services/`, and `types/` directories are scaffolded. **No page content or API service integration exists yet.**

### ERP (`apps/erp/`)

Next.js App Router application with route groups `(auth)` and `(dashboard)` present and locked. Root `layout.tsx` and `globals.css` implemented. `components/`, `hooks/`, `layouts/`, `modules/`, `services/`, and `types/` directories are scaffolded. **No page content or API service integration exists yet.**

### Current Status

Both applications are structurally complete and correctly scaffolded. Implementation of page content, components, and API service integration is the next frontend delivery phase.

---

## 7. Documentation Snapshot

### Documentation Phase: Complete

All 19 documentation subfolders in `docs/` are complete. The suite covers the full SDLC lifecycle from discovery through deployment, quality management, and AI workflow guidance.

### Key Reference Documents

| Document | Purpose |
|----------|---------|
| `.github/AGENT.md` v1.1.0 | **Highest authority.** AI governance, architecture lock, security rules, coding standards, development workflow, git workflow, and all mandatory AI behaviour. Read this first. |
| `.github/PROJECT_MEMORY.md` v1.0.0 | **Current state.** Full living record of repository progress, module status, technical debt, sprint state, and handoff notes. Read this second. |
| `.github/PROJECT_CONTEXT.md` v1.0.0 | **This document.** Lightweight AI bootstrap guide. |
| `docs/04_Architecture/` | ARC-001 through ARC-004 — high-level, system, component, and database architecture |
| `docs/05_Database/` | DB-001 through DB-005 — ER design, schema, table specifications, data dictionary |
| `docs/06_API/` | API-001 through API-009 — REST standards, endpoint documentation |
| `docs/08_Security/` | SEC-001 through SEC-010 — all security policies and guidelines |
| `docs/18_Development_Planning/` | PLAN-001 (Product Vision), PLAN-002 (Branding and Configuration) |
| `docs/12_ADR/` | ADR-001 — Architecture Decision Log; new ADRs go here before implementation |

---

## 8. Security Snapshot

The following summarises the security strategy already defined in `AGENT.md` Sections 12 and 24 and in `docs/08_Security/`. Nothing new is introduced here.

| Area | Summary |
|------|---------|
| **Authentication** | JWT access tokens (short-lived) + refresh token pattern. `AuthMiddleware.php` enforces token validation on all protected routes. Implemented in the `Auth` module. |
| **JWT** | Tokens include `iat` and `exp` claims. Token blacklisting on logout via `TokenRepository.php`. Tokens must not be logged. |
| **Password Security** | bcrypt hashing via `PasswordHasher.php`. Plaintext comparison is prohibited. Password policy: 8+ characters, uppercase, digit, special character. |
| **Input Validation** | All input passes through `*Validator.php` before any business logic executes. No bypass is permitted. |
| **Prepared Statements** | All database queries use PDO prepared statements with named or positional binding. Raw SQL interpolation is prohibited without exception. |
| **WAF** | Cloudflare WAF at edge, ModSecurity + OWASP CRS on server. Bot protection, geo-blocking, SQL injection, and XSS WAF rules required. |
| **Firewall** | Layered: Cloudflare (edge), Apache/Nginx (reverse proxy), OS-level UFW or Windows Defender, future cloud security groups. |
| **Security Headers** | CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy — all required on HTTP responses. |
| **Logging** | Centralised through `Logger.php` to `backend/logs/`. Auth events, audit events, failed logins, admin actions, and access logs all required. |
| **Rate Limiting** | Required at web server and API endpoint level. Not yet configured. |
| **Docker Isolation** | Docker Compose files are authoritative. No modification without approval. `.env` must not be committed. |
| **Cloud Readiness** | Architecture is compatible with AWS, Azure, and GCP without code changes. Infrastructure abstracted via environment variables. |
| **RBAC** | Role-based access enforced via `AuthMiddleware`. Roles and permissions documented in `SEC-003`. |
| **Secrets** | Managed via `.env` (never committed). `EnvironmentLoader.php` handles loading. `.env.example` documents required keys. |

---

## 9. Development Rules

These rules are mandatory. They are derived from `AGENT.md` and are non-negotiable.

- **Never redesign the architecture.** The folder structure, module boundaries, and file patterns are permanently frozen at v3.0.
- **Never rename any file.** Not for any reason, under any instruction.
- **Never move any file.** Files stay in the directory they were created in.
- **Never delete any file.** Removal requires explicit human approval outside this rulebook.
- **Always extend.** New features extend existing modules. New modules require team approval and full pattern compliance.
- **Always verify before committing.** Run the mandatory self-verification checklist (`AGENT.md` Section 20) before declaring any task complete.
- **Use existing conventions.** File naming, class naming, method naming, and commit message format follow the patterns already present in the repository.
- **No placeholder content.** Every file committed must be complete, accurate, and production-ready.
- **No unapproved dependencies.** No library, framework, or package outside the approved stack may be introduced without explicit written approval.
- **No secrets in commits.** Credentials, tokens, API keys, and passwords must never appear in any committed file, commit message, or PR description.

---

## 10. Git Workflow

### Branch Naming

`{type}/{scope}-{short-description}`

Types: `feat`, `fix`, `docs`, `refactor`, `test`, `chore`, `hotfix`

Example: `feat/auth-jwt-refresh`, `fix/student-validator-null`, `docs/api-attendance`

### Commit Format

Conventional Commits: `{type}({scope}): {description}`

- Description starts lowercase, uses imperative mood, max 72 characters, no trailing full stop.
- Example: `feat(student): add paginated student list endpoint`
- Scope maps to the affected module folder (see `AGENT.md` Section 11.3 for full scope table).

### Review Process

All work is submitted via pull request. Every PR must: reference the specific task or requirement it addresses, pass the full Verification Workflow (`AGENT.md` Section 8), be reviewed and approved by a human team member, and have all review comments resolved before merging. Force-push to `main` or `develop` is prohibited.

### Definition of Done

A task is complete only when all 16 criteria in `AGENT.md` Section 9 are satisfied. Key criteria: code correct, architecture aligned, naming conventions followed, interface contracts implemented, input validated, SQL safe, type-safe, no debug output, no secrets, access control applied, unit tests present, integration tests present, documentation updated, revision history updated, commit message correct, cross-references current.

---

## 11. Current Progress

### Completed

| Item | Status |
|------|--------|
| Repository architecture — frozen at v3.0 | Complete |
| SDLC documentation — all 19 subfolders | Complete |
| Backend domain modules — all 12 fully scaffolded | Complete |
| Backend infrastructure modules (Database, Http, Support) | Complete |
| AI governance — `AGENT.md` v1.1.0 | Complete |
| `PROJECT_MEMORY.md` v1.0.0 | Complete |
| `PROJECT_CONTEXT.md` v1.0.0 | Complete (this document) |

### In Progress

| Item | Status |
|------|--------|
| Domain route registration in `backend/routes/api.php` | In Progress |
| Module ServiceProvider wiring in `Bootstrap.php` | In Progress |

### Pending

| Item | Status |
|------|--------|
| Database migration files | Pending |
| Database seed files | Pending |
| Docker configuration (Dockerfile, docker-compose.yml) | Pending |
| Frontend page content — website and ERP | Pending |
| Frontend API service integration | Pending |
| Unit tests (all 12 domain modules) | Pending |
| Integration tests (all API endpoints) | Pending |
| Security header web server configuration | Pending |
| Rate limiting configuration | Pending |

---

## 12. Next Immediate Priorities

Ordered by dependency. All work must remain within the locked architecture.

1. Register all 12 domain module routes in `backend/routes/api.php` with authentication middleware applied to protected endpoints.
2. Wire all 12 domain module `*ServiceProvider.php` files into `backend/app/Support/Bootstrap.php`.
3. Author sequential database migration files in `database/migrations/` for all domain entities, referencing `docs/05_Database/`.
4. Author development seed files in `database/seeds/` for roles, sessions, and test records.
5. Author Docker configuration in `deployment/docker/` — Dockerfile and `docker-compose.yml` for Apache/Nginx, PHP 8.3+, and MySQL — after architect approval.
6. Implement API service functions in `apps/website/services/` and `apps/erp/services/` per `docs/06_API/` contracts.
7. Implement ERP dashboard pages in `apps/erp/app/(dashboard)/` for core modules.
8. Implement public website pages in `apps/website/app/(public)/` per `docs/07_UI/`.
9. Write unit tests in `tests/unit/` for all 12 domain service implementations.
10. Write integration tests in `tests/integration/` for all registered API endpoints.

---

## 13. AI Session Bootstrap

Every AI assistant starting a new session in this repository must follow this sequence. Steps may not be skipped or reordered.

| Step | Action |
|------|--------|
| **1** | Read `.github/AGENT.md` in full — highest authority document |
| **2** | Read `.github/PROJECT_MEMORY.md` — current repository state and progress |
| **3** | Read `.github/PROJECT_CONTEXT.md` — this lightweight orientation guide |
| **4** | Inspect only the specific module or folder required for the assigned task |
| **5** | Implement the task following the mandatory response format (`AGENT.md` Section 18): Understanding -> Plan -> Files Affected -> Implementation -> Verification -> Remaining Limitations -> Recommendation -> Await Approval |
| **6** | Complete mandatory self-verification across all 8 dimensions (`AGENT.md` Section 20) before declaring the task complete |
| **7** | Prepare the commit following Conventional Commits format and await human approval before merging |

---

## 14. Quick Commands

Common commands useful when working in this repository.

```bash
# Check current repository status
git status

# View unstaged changes
git diff

# Check for whitespace errors before committing
git diff --check

# View staged changes
git diff --cached

# Start all Docker services
docker compose up

# Start Docker services in background
docker compose up -d

# Stop all Docker services
docker compose down

# View Docker service logs
docker compose logs -f

# Validate PHP syntax for a file
php -l backend/app/{Module}/{Module}Service.php

# Check current branch
git branch --show-current

# View recent commits
git log --oneline -10
```

> Note: Docker commands require `deployment/docker/docker-compose.yml` to be created first. This is currently pending.

---

## 15. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | 2026-07-02 | Project Team | Initial PROJECT_CONTEXT.md — AI bootstrap guide created from full repository read |

---

# Approval

| Role | Name | Status |
|------|------|--------|
| Tech Lead | ChatGPT | Pending |
| Project Manager | You | Pending |

---

© T.N. Memorial School Digital Platform
