# PROJECT_MEMORY.md — Living AI Memory Document

> **T.N. Memorial Public School Digital Platform**
> Document Version: **v1.0.0** | Status: **Active** | Created: **2026-07-02** | Authority: `.github/AGENT.md`

This document allows any future AI assistant to reach full situational awareness within two minutes. It is sourced entirely from the actual repository state as read on 2026-07-02. No content has been invented.

---

## Table of Contents

1. [Project Identity](#1-project-identity)
2. [Technology Stack](#2-technology-stack)
3. [Repository Architecture](#3-repository-architecture)
4. [Backend Progress](#4-backend-progress)
5. [Frontend Progress](#5-frontend-progress)
6. [Database Status](#6-database-status)
7. [Security Status](#7-security-status)
8. [Documentation Status](#8-documentation-status)
9. [Docker Status](#9-docker-status)
10. [Development Workflow](#10-development-workflow)
11. [Current Sprint Status](#11-current-sprint-status)
12. [Immediate Next Tasks](#12-immediate-next-tasks)
13. [Known Technical Debt](#13-known-technical-debt)
14. [AI Handoff Notes](#14-ai-handoff-notes)
15. [Repository Metrics](#15-repository-metrics)
16. [Revision History](#16-revision-history)

---

## 1. Project Identity

| Field | Value |
|-------|-------|
| **Project Name** | T.N. Memorial Public School Digital Platform |
| **Repository Name** | `tnm-school-digital-platform` |
| **Purpose** | Enterprise-grade school website and ERP platform serving administrators, teachers, students, and parents with role-based access, academic management, and school operations digitisation |
| **Vision** | A secure, scalable, and role-based digital environment that replaces fragmented manual school workflows with a unified platform supporting admissions, attendance, examination, fees, notices, and reporting |
| **Repository Version** | v3.0 (architecture frozen at this baseline) |
| **AI Rulebook Version** | v1.1.0 (`.github/AGENT.md`) |
| **Repository Status** | Documentation phase complete. Implementation phase in progress. Architecture is locked. |
| **Effective Date** | 2026-07-02 |

---

## 2. Technology Stack

### Frontend

| Layer | Technology | Notes |
|-------|------------|-------|
| Public Website | Next.js + TypeScript + Tailwind CSS | `apps/website/` — App Router, Server Components first |
| ERP Dashboard | Next.js + TypeScript + Tailwind CSS | `apps/erp/` — App Router, role-based dashboard views |
| UI Pattern | React Server Components by default | Client Components only where interactivity requires it |
| Styling | Tailwind CSS | No inline styles; no alternative CSS frameworks |

### Backend

| Layer | Technology | Notes |
|-------|------------|-------|
| Language | PHP 8.3+ | `require: "php": "^8.3"` per `composer.json` |
| Autoloading | PSR-4 | Namespace `App\` maps to `backend/app/` |
| API Style | REST | JSON responses only |
| Architecture | Custom modular monolith | Domain module pattern, no framework dependency |

### Database

| Layer | Technology | Notes |
|-------|------------|-------|
| RDBMS | MySQL | Connection via PDO, prepared statements mandatory |
| Schema Location | `database/schema/` | Bootstrap SQL: `0001-bootstrap.sql` |
| Migrations | `database/migrations/` | Sequential numbering; currently empty (pending) |

### Deployment

| Layer | Technology | Notes |
|-------|------------|-------|
| Local Environment | Docker | `deployment/docker/` — primary dev environment |
| Web Server | Apache or Nginx | `deployment/apache/` and `deployment/nginx/` |
| Hosting | `deployment/hosting/` | Runbook and environment documentation |

### Documentation

| Tool | Coverage |
|------|---------|
| GitHub Markdown | All SDLC documentation in `docs/` across 19 subfolders |
| AI Rulebook | `.github/AGENT.md` v1.1.0 |
| Governance | `.github/copilot-instructions.md` (supplementary AI behaviour) |

### AI Workflow

| Tool | Role |
|------|------|
| All AI Contributors | Governed equally by `.github/AGENT.md` v1.1.0 |
| Supported Models | ChatGPT, Claude, Gemini, GitHub Copilot, Codex, Cursor, Cline, Continue.dev, Windsurf |
| Workflow | Session bootstrap -> Implementation -> Self-verification -> Await approval |

---

## 3. Repository Architecture

### Architecture Status: PERMANENTLY LOCKED at v3.0

The repository folder structure, module boundaries, internal module file patterns, and technology layers are permanently frozen. No AI assistant may rename, move, delete, restructure, or redesign any part of the repository. Every addition must extend the existing structure only.

### Locked Top-Level Structure

```text
tnm-school-digital-platform/
├── .github/               — AGENT.md, copilot-instructions.md, PROJECT_MEMORY.md
├── apps/
│   ├── erp/               — ERP frontend (Next.js, TypeScript, Tailwind CSS)
│   └── website/           — Public website frontend (Next.js, TypeScript, Tailwind CSS)
├── backend/               — PHP 8.3+ REST API and business logic
├── database/              — MySQL schema, migrations, seeders, procedures, triggers, views
├── deployment/            — Apache, Nginx, Docker, hosting scripts
├── docs/                  — Complete 19-folder SDLC documentation suite
├── scripts/               — Utility and automation scripts
├── tests/                 — unit/, integration/, api/, ui/, fixtures/
├── assets/                — Static project assets
├── archive/               — Archived materials
├── templates/             — Document and code templates
├── config/                — Application configuration
└── tools/                 — Development tooling
```

### Rule: Extension Only

New backend files go inside the correct existing domain module. A new module folder may only be created after explicit team approval, and must fully implement all 19 components defined in `AGENT.md` Section 27. No new top-level directories may be created.

---

## 4. Backend Progress

### Module Completion Summary

All 12 domain modules in `backend/app/` have been scaffolded and are **fully implemented** with the complete internal module pattern. Infrastructure modules (`Database`, `Http`, `Support`) are also fully implemented.

### Domain Module Status

| Module | Files | Status | Notes |
|--------|-------|--------|-------|
| `Auth` | 21 files | Complete | Includes `JwtHelper`, `PasswordHasher`, `TokenRepository`, `AuthMiddleware`, `UserRepository` — exceeds standard pattern |
| `Student` | 13 files | Complete | Full standard pattern |
| `Teacher` | 13 files | Complete | Full standard pattern |
| `Attendance` | 13 files | Complete | Full standard pattern |
| `AttendanceRecord` | 13 files | Complete | Full standard pattern |
| `AcademicClass` | 13 files | Complete | Full standard pattern |
| `AcademicSession` | 13 files | Complete | Full standard pattern |
| `Section` | 13 files | Complete | Full standard pattern |
| `Subject` | 13 files | Complete | Full standard pattern |
| `Period` | 13 files | Complete | Full standard pattern |
| `Timetable` | 13 files | Complete | Full standard pattern |
| `HolidayCalendar` | 13 files | Complete | Full standard pattern |

### Infrastructure Module Status

| Module | Files | Status | Contents |
|--------|-------|--------|----------|
| `Database` | 1 file | Complete | `ConnectionManager.php` — PDO singleton |
| `Http` | 3 files | Complete | `Pipeline.php`, `RequestHelper.php`, `ResponseHelper.php` |
| `Support` | 4 files | Complete | `AppContainer.php`, `Bootstrap.php`, `ErrorHandler.php`, `Logger.php` |

### Locked Internal Module Pattern

Every domain module must implement these 13 files minimum:

```text
{Module}.php                      — Domain entity
{Module}Controller.php            — HTTP controller
{Module}Exception.php             — Domain exception
{Module}ListRequest.php           — List/filter DTO
{Module}Repository.php            — Concrete repository (PDO)
{Module}RepositoryInterface.php   — Repository contract
{Module}Response.php              — Response DTO
{Module}Service.php               — Business logic service
{Module}ServiceInterface.php      — Service contract
{Module}ServiceProvider.php       — Dependency wiring
{Module}Validator.php             — Input validation
Create{Module}Request.php         — Create request DTO
Update{Module}Request.php         — Update request DTO
```

Plus router registration in `backend/routes/api.php`, container binding in `backend/app/Support/Bootstrap.php`, unit tests in `tests/unit/`, and integration tests in `tests/integration/`.

### Routes Status

`backend/routes/api.php` currently contains only a `/health` endpoint stub. All 12 domain module routes are **pending registration**. This is the primary outstanding backend integration task.

### Container Bindings Status

`backend/app/Support/Bootstrap.php` contains infrastructure wiring only. Domain module service provider wiring into the container is **pending**. Each module has a `*ServiceProvider.php` ready to be connected.

---

## 5. Frontend Progress

### Website (`apps/website/`)

| Item | Status |
|------|--------|
| App Router structure | Scaffolded |
| Route group `(public)` | Present |
| Route group `(auth)` | Present |
| Route group `(erp)` | Present |
| `layout.tsx` | Present |
| `globals.css` | Present |
| `loading.tsx` | Present |
| `not-found.tsx` | Present |
| `components/`, `hooks/`, `lib/`, `services/`, `types/` | Scaffolded |
| Page content implementation | Pending |
| API service integration | Pending |

### ERP (`apps/erp/`)

| Item | Status |
|------|--------|
| App Router structure | Scaffolded |
| Route group `(auth)` | Present |
| Route group `(dashboard)` | Present |
| `layout.tsx` | Present |
| `globals.css` | Present |
| `components/`, `hooks/`, `layouts/`, `modules/`, `services/`, `types/` | Scaffolded |
| Dashboard page content | Pending |
| Role-based navigation | Pending |
| API service integration | Pending |

### Current Frontend Status

Both frontend applications are fully scaffolded with the correct folder structure and route groups. No substantive page content or API service integration has been implemented yet.

---

## 6. Database Status

### Current Assumptions

- Database engine: MySQL, version as per deployment configuration.
- Connection: PDO with prepared statements, credentials via `.env` and `EnvironmentLoader.php`.
- Database name: `tnm_school_platform` (default in `Bootstrap.php`).

### Schema Status

| Artifact | Status |
|----------|--------|
| Bootstrap SQL | Present — `database/schema/0001-bootstrap.sql` |
| Table migration files | Pending — no migration files exist yet |
| ER Design documentation | Complete — `DB-001` through `DB-005` in `docs/05_Database/` |

### Migration Status

`database/migrations/` exists but contains only a `README.md`. No migration files have been created. SQL migration files implementing the documented schema must still be authored.

### Seed Status

`database/seeds/` and `database/seeders/` directories exist but contain no seed files. Seed data for development and testing is pending.

### Future Plan

1. Author migration files for all domain entities following sequential numbering.
2. Author seed files for reference data (roles, sessions, sample records).
3. Create stored procedures, views, and triggers as required.
4. Update `docs/05_Database/` for all schema changes.

---

## 7. Security Status

The following security decisions are documented in `.github/AGENT.md` v1.1.0 and in `docs/08_Security/` (SEC-001 through SEC-010). This section summarises decisions from those documents only. No new rules are introduced here.

| Security Domain | Decision / Implementation Status |
|----------------|----------------------------------|
| **Authentication** | JWT-based. `JwtHelper.php`, `AuthMiddleware.php`, `PasswordHasher.php` (bcrypt), `TokenRepository.php` implemented in `Auth` module. Token blacklisting on logout implemented. |
| **JWT** | Short-lived access tokens. Refresh token pattern. Tokens must include `iat` and `exp` claims. HttpOnly + Secure required for cookie storage. Tokens must not be logged. |
| **Input Validation** | All input validated through `*Validator.php` before entering business logic. All 12 domain validators implemented. |
| **Prepared Statements** | PDO with named/positional binding mandatory. All domain repositories use prepared statements. No raw SQL interpolation permitted — zero exceptions. |
| **File Upload Security** | Extension allowlist, MIME validation, random filenames, storage isolation, maximum upload size required. Upload directory: `backend/uploads/`. Implementation pending. |
| **Security Headers** | CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy required on all HTTP responses. Web server configuration pending. |
| **Firewall** | Layered: Cloudflare (edge), Apache/Nginx (reverse proxy), UFW/Windows Defender (OS), future AWS/Azure/GCP security groups. |
| **WAF** | Cloudflare WAF at edge; ModSecurity + OWASP CRS on server; bot protection, geo-blocking, SQL injection and XSS WAF rules required. |
| **Logging** | Centralised through `Logger.php` to `backend/logs/app.log`. Audit logs, security logs, access logs, failed login logs, and admin activity logs required. |
| **Rate Limiting** | Required at web server/reverse proxy layer and on API endpoints. Not yet configured. |
| **Backups** | Daily (7-day), weekly (4-week), monthly (12-month), offsite encrypted. Restore testing monthly. Pre-deployment — not yet operational. |
| **Disaster Recovery** | RTO and RPO to be defined in operational runbook in `deployment/hosting/`. Failover procedure to be documented. |
| **Docker Isolation** | Docker Compose files authoritative. No config modification without approval. Apache, Nginx, PHP, MySQL must remain compatible. `.env` must not be committed. |
| **Cloud Security** | Architecture compatible with AWS, Azure, GCP, Kubernetes without code changes. Cloud-specific SDKs prohibited without approval. |
| **RBAC** | Documented in `SEC-003-Role-Based-Access-Control-(RBAC).md`. Enforced through `AuthMiddleware.php`. |
| **Error Handling** | `ErrorHandler.php` catches all unhandled exceptions. Production responses must not expose stack traces or internal paths. |
| **Secrets** | `.env` listed in `.gitignore`. `EnvironmentLoader.php` manages loading. `.env.example` documents required variables without values. |

---

## 8. Documentation Status

### Overall Documentation Phase: Complete

The full SDLC documentation suite is complete across 19 subfolders in `docs/`. The implementation phase is ongoing.

### Major Documents by Domain

| Domain | Documents | Status |
|--------|-----------|--------|
| Project Governance (`00_`) | Governance and process documents | Complete |
| Discovery (`01_`) | Project discovery and scoping | Complete |
| Business (`02_`) | Business requirements and context | Complete |
| Requirements (`03_`) | Functional and non-functional requirements | Complete |
| Architecture (`04_`) | ARC-001 through ARC-004 | Complete |
| Database (`05_`) | DB-001 through DB-005 | Complete |
| API (`06_`) | API-001 through API-009 | Complete |
| UI (`07_`) | UI documentation suite | Complete |
| Security (`08_`) | SEC-001 through SEC-010 | Complete |
| Testing (`09_`) | Testing strategy and guidelines | Complete |
| Deployment (`10_`) | Deployment strategy and runbooks | Complete |
| Portfolio (`11_`) | Portfolio documentation | Complete |
| ADR (`12_`) | ADR-001 Architecture Decision Log | Present |
| RTM (`13_`) | Requirements traceability matrix | Complete |
| QMS (`14_`) | Quality management documentation | Complete |
| Appendix (`15_`) | Reference appendix | Complete |
| Architecture Handbook (`16_`) | Implementation handbook | Complete |
| AI Prompts (`17_`) | AI workflow prompt guidance | Complete |
| Development Planning (`18_`) | PLAN-001 (Product Vision), PLAN-002 (Branding) | Complete |

### Governance Status

| Document | Version | Status |
|----------|---------|--------|
| `.github/AGENT.md` | v1.1.0 | Active — highest authority |
| `.github/copilot-instructions.md` | — | Active — supplementary AI behaviour |
| `.github/PROJECT_MEMORY.md` | v1.0.0 | Active — this document |

---

## 9. Docker Status

### Current Docker Planning

Docker is the designated primary local development environment per `AGENT.md` Section 4.1. The `deployment/docker/` directory exists and contains a `README.md`. No `Dockerfile` or `docker-compose.yml` files have been created yet. Docker configuration is planned but not yet implemented.

### Required Future Containers

| Container | Role |
|-----------|------|
| Apache | Primary web server for local PHP backend |
| Nginx | Reverse proxy for local and production-equivalent environments |
| PHP 8.3+ | Backend runtime container |
| MySQL | Relational database container |

All four services must remain mutually compatible within the Docker environment at all times.

### Deployment Strategy

1. Local Development: Docker Compose orchestrating all four services.
2. Staging: Server-based Apache or Nginx with PHP-FPM and MySQL.
3. Production: Behind reverse proxy with WAF, HSTS, and security headers active.
4. Future Cloud: AWS, Azure, or GCP compatible without code changes via environment variable abstraction.

### Docker Governance Rules

- No Docker configuration file may be modified without explicit written approval from the project architect.
- `docker-compose.override.yml` must be used for local overrides and must not be committed.
- `.env` files provide container environment variables and must not be committed.

---

## 10. Development Workflow

### Git Workflow

| Element | Convention |
|---------|------------|
| Branch naming | `{type}/{scope}-{short-description}` |
| Types | `feat`, `fix`, `docs`, `refactor`, `test`, `chore`, `hotfix` |
| Commit format | Conventional Commits: `{type}({scope}): {description}` |
| Scope reference | See `AGENT.md` Section 11.3 for full scope-to-folder mapping |

### Branch Strategy

- `main` — production-ready, force push prohibited
- `develop` — integration branch
- Feature branches off `develop`, merged via pull request after human review

### Review Process

Every pull request must reference a specific task, pass all Verification Workflow steps (`AGENT.md` Section 8), be reviewed by a human team member, and have all comments resolved before merging.

### Definition of Done

A task is complete only when all 16 criteria in `AGENT.md` Section 9 are satisfied: code correctness, architecture alignment, naming conventions, interface contracts, input validation, SQL safety, type safety, no debug output, no secrets, access control, unit tests, integration tests, documentation updated, revision history updated, correct commit message, and current cross-references.

### Verification Process

Mandatory self-verification across 8 dimensions before declaring completion (`AGENT.md` Section 20): Architecture, Security, Documentation, Git, Testing, Performance, Coding Standards, and Definition of Done.

### Sprint Cadence

- Duration: 2 weeks
- Planning: elaborate backlog into tasks with acceptance criteria
- Execution: implement within scope, escalate architectural deviations immediately
- Review: demonstrate completed work, validate Definition of Done
- Retrospective: capture gaps and improvement actions

---

## 11. Current Sprint Status

### Completed (as of 2026-07-02)

| Item | Detail |
|------|--------|
| Repository architecture | Fully scaffolded and frozen at v3.0 |
| SDLC documentation | All 19 documentation subfolders complete |
| Backend domain module scaffolding | All 12 domain modules fully implemented with complete pattern |
| Backend infrastructure modules | Database, Http, and Support modules complete |
| AI governance | `AGENT.md` v1.1.0 authored and active |
| Project memory | `PROJECT_MEMORY.md` v1.0.0 authored (this document) |

### In Progress

| Item | Detail |
|------|--------|
| Backend route registration | Domain module routes need registering in `backend/routes/api.php` |
| Container binding wiring | Module ServiceProviders need connecting in `Bootstrap.php` |

### Pending

| Item | Detail |
|------|--------|
| Database migrations | No migration files exist; all domain tables pending |
| Database seeds | No seed files exist |
| Docker configuration | `deployment/docker/` has no Dockerfile or Compose files |
| Frontend page content | Both apps are structurally scaffolded but have no page content |
| Frontend API integration | `services/` layers in both apps are empty |
| Unit tests | `tests/unit/` is empty |
| Integration tests | `tests/integration/` is empty |
| Security header configuration | Web server configuration for security headers pending |
| Rate limiting configuration | Not yet configured |

### Blockers

No hard blockers. Primary prerequisite for frontend and testing work is completing backend route registration and database migration creation.

---

## 12. Immediate Next Tasks

Ordered by dependency. All tasks must be executed within the locked architecture.

1. **Register domain module routes** — Add REST route definitions for all 12 domain modules in `backend/routes/api.php`. Apply `AuthMiddleware` to all protected routes.

2. **Wire module ServiceProviders** — Connect all 12 domain module `*ServiceProvider.php` files into `backend/app/Support/Bootstrap.php` container initialization.

3. **Author database migrations** — Create sequential migration files in `database/migrations/` for all domain entities. Reference `docs/05_Database/` for table specifications. Update `docs/05_Database/` revision histories after each migration.

4. **Create development seed data** — Author initial seed files in `database/seeds/` for roles, academic sessions, and test records.

5. **Create Docker configuration** — Author `Dockerfile` and `docker-compose.yml` in `deployment/docker/` for Apache/Nginx, PHP 8.3+, and MySQL. Requires architect approval per `AGENT.md` Section 4.1 before any modification.

6. **Implement frontend API service layer** — Build API service functions in `apps/website/services/` and `apps/erp/services/` corresponding to the API contracts in `docs/06_API/`.

7. **Implement ERP dashboard pages** — Build authenticated dashboard views in `apps/erp/app/(dashboard)/` for student, teacher, attendance, and timetable management modules.

8. **Implement public website pages** — Build public-facing pages in `apps/website/app/(public)/` following the design system documented in `docs/07_UI/`.

9. **Write unit tests** — Author unit tests in `tests/unit/` for all 12 domain service layer implementations.

10. **Write integration tests** — Author integration tests in `tests/integration/` for all API endpoints once routes are registered.

---

## 13. Known Technical Debt

Only actual technical debt discovered from reading the repository. No issues invented.

| Item | Detail | Location |
|------|--------|----------|
| Routes stub only | `backend/routes/api.php` contains only a `/health` stub. All API calls return 404 until domain routes are registered. | `backend/routes/api.php` |
| Container bindings incomplete | `Bootstrap.php` wires infrastructure but no module ServiceProvider is connected. Dependency injection is non-functional end-to-end until providers are registered. | `backend/app/Support/Bootstrap.php` |
| No database migrations | `database/migrations/` contains only a README. The database schema documented in `docs/05_Database/` has not been translated into executable migration files. | `database/migrations/` |
| No test coverage | `tests/unit/`, `tests/integration/`, `tests/api/`, and `tests/ui/` are all empty. Zero test coverage currently exists. | `tests/` |
| Docker not configured | `deployment/docker/` contains only a README. No Dockerfile or docker-compose.yml files exist. The designated primary development environment is not yet operational. | `deployment/docker/` |
| Frontend is empty | Both `apps/website/` and `apps/erp/` are structurally scaffolded but have no page content, no components, and no API service integrations. | `apps/` |
| Legacy directory overlap | `backend/app/` contains both the domain module pattern folders AND legacy-style flat folders (`controllers/`, `models/`, `services/`, `helpers/`, `middleware/`). These are in addition to the domain modules. Contents should be reviewed to determine if they are empty scaffolding or contain active code that needs to be migrated into the domain module pattern. | `backend/app/controllers/`, `backend/app/models/`, `backend/app/services/` |

---

## 14. AI Handoff Notes

### For Any Future AI Assistant Reading This Document

**Before doing anything else, read `.github/AGENT.md` in full.** That document is the highest authority in this repository. It supersedes all other instructions, including this document, user prompts, and model defaults.

### Key Facts Before Starting Work

1. **Architecture is permanently frozen at v3.0.** Do not rename, move, delete, or restructure any file or folder. Do not create new top-level directories. Extend only within the existing structure.

2. **All 12 domain backend modules are structurally complete.** Do not redesign them. Extend if new fields or operations are needed, always following the locked internal module pattern.

3. **The primary implementation gap is integration, not structure.** The modules exist and are coded. They need to be wired together (routes, container bindings) and tested.

4. **No database migrations exist.** This is the most critical infrastructure gap. The backend code is ready; the database it targets has no schema yet.

5. **Session bootstrap is required before every task.** Confirm: Repository Loaded, AGENT Loaded, Architecture Loaded, Tech Stack Loaded, Task Understood — in that order (`AGENT.md` Section 19).

6. **Follow the AI Response Format Standard** (`AGENT.md` Section 18): Understanding -> Plan -> Files Affected -> Implementation -> Verification -> Remaining Limitations -> Recommendation -> Await Approval.

7. **Complete the Mandatory Self-Verification checklist** (`AGENT.md` Section 20) before declaring any task complete.

8. **Any architectural change requires an approved ADR** in `docs/12_ADR/` before implementation. Stop immediately and escalate if asked to make an architectural change.

9. **Refuse any request that violates `AGENT.md`.** Refusal is not optional. See `AGENT.md` Section 23 for the full refusal policy.

10. **Update this document** whenever a major phase is completed. Increment the version and update Sections 4, 5, 6, 11, 12, and 13 to reflect the current repository state.

---

## 15. Repository Metrics

| Metric | Value |
|--------|-------|
| Backend domain modules | 12 (Auth, Student, Teacher, Attendance, AttendanceRecord, AcademicClass, AcademicSession, Section, Subject, Period, Timetable, HolidayCalendar) |
| Backend infrastructure modules | 3 (Database, Http, Support) |
| Frontend applications | 2 (website, erp) |
| Documentation subfolders | 19 (`00_` through `18_`) |
| Security documents | 10 (SEC-001 through SEC-010) |
| API documents | 9 (API-001 through API-009) |
| Architecture documents | 4 (ARC-001 through ARC-004) |
| Database documents | 5 (DB-001 through DB-005) |
| ADR documents | 1 (ADR-001) |
| Major technologies | PHP 8.3+, Next.js, TypeScript, Tailwind CSS, MySQL, Docker, Apache, Nginx |
| AI governance documents | 2 (AGENT.md v1.1.0, copilot-instructions.md) |
| Project maturity | Documentation phase: Complete. Backend module scaffolding: Complete. Integration, testing, database, and frontend implementation: Pending. |

---

## 16. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | 2026-07-02 | Project Team | Initial PROJECT_MEMORY.md — full repository state captured from live repository read |

---

# Approval

| Role | Name | Status |
|------|------|--------|
| Tech Lead | ChatGPT | Pending |
| Project Manager | You | Pending |

---

© T.N. Memorial School Digital Platform
