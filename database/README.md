# Database Foundation — T.N. Memorial Public School Digital Platform

> **Sprint 3 — Database Foundation**
> Authority: `.github/AGENT.md`
> Version: 1.0.0 | Created: 2026-07-03

This directory contains the complete MySQL 8 database foundation for the TNM School Digital Platform. All SQL follows the conventions defined in `.github/AGENT.md`.

---

## Table of Contents

1. [Directory Structure](#directory-structure)
2. [Schema Overview](#schema-overview)
3. [Migration Order](#migration-order)
4. [Seeder Order](#seeder-order)
5. [Relationship Diagram](#relationship-diagram)
6. [Naming Conventions](#naming-conventions)
7. [Indexing Strategy](#indexing-strategy)
8. [Views](#views)
9. [Stored Procedures](#stored-procedures)
10. [Functions](#functions)
11. [Triggers](#triggers)
12. [How to Run](#how-to-run)
13. [Security Notes](#security-notes)

---

## Directory Structure

```
database/
├── migrations/                     — Sequential DDL migration files
│   ├── 0001_create_roles_table.sql
│   ├── 0002_create_permissions_table.sql
│   ├── 0003_create_role_permissions_table.sql
│   ├── 0004_create_users_table.sql
│   ├── 0005_create_user_roles_table.sql
│   ├── 0006_create_auth_tokens_table.sql
│   ├── 0007_create_academic_sessions_table.sql
│   ├── 0008_create_academic_classes_table.sql
│   ├── 0009_create_sections_table.sql
│   ├── 0010_create_subjects_table.sql
│   ├── 0011_create_teachers_table.sql
│   ├── 0012_create_students_table.sql
│   ├── 0013_create_timetables_table.sql
│   ├── 0014_create_periods_table.sql
│   ├── 0015_create_attendance_table.sql
│   ├── 0016_create_attendance_records_table.sql
│   ├── 0017_create_holiday_calendars_table.sql
│   ├── 0018_create_audit_logs_table.sql
│   └── 0019_create_system_settings_table.sql
├── seeds/                          — Minimal bootstrap seed data
│   ├── 0001_seed_roles_and_permissions.sql
│   └── 0002_seed_admin_user.sql
├── schema/                         — Bootstrap SQL (database creation)
│   └── 0001-bootstrap.sql
├── views/
│   └── views.sql                   — SQL Views
├── procedures/
│   └── procedures.sql              — Stored Procedures
├── functions/
│   └── functions.sql               — SQL Functions
├── triggers/
│   └── triggers.sql                — Audit triggers
└── README.md                       — This file
```

---

## Schema Overview

| # | Table | Module | Purpose |
|---|-------|--------|---------|
| 1 | `roles` | Auth/RBAC | User roles (super-admin, admin, teacher, student, parent) |
| 2 | `permissions` | Auth/RBAC | Granular permissions per module action |
| 3 | `role_permissions` | Auth/RBAC | Pivot: roles ↔ permissions |
| 4 | `users` | Auth | Platform login accounts |
| 5 | `user_roles` | Auth/RBAC | Pivot: users ↔ roles |
| 6 | `auth_tokens` | Auth | JWT refresh token revocation store |
| 7 | `academic_sessions` | AcademicSession | School years (e.g., 2026-2027) |
| 8 | `academic_classes` | AcademicClass | Grade groups per session |
| 9 | `sections` | Section | Class divisions (A, B, C) |
| 10 | `subjects` | Subject | Subject catalogue |
| 11 | `teachers` | Teacher | Teacher staff records |
| 12 | `students` | Student | Student enrollment records |
| 13 | `timetables` | Timetable | Class timetable assignments |
| 14 | `periods` | Period | Individual time slots within timetables |
| 15 | `attendance` | Attendance | Daily attendance per student |
| 16 | `attendance_records` | AttendanceRecord | Detailed record entries |
| 17 | `holiday_calendars` | HolidayCalendar | School holiday events |
| 18 | `audit_logs` | System | Immutable audit trail |
| 19 | `system_settings` | System | Application configuration KV store |

**Total tables: 19**

All tables use:
- Engine: `InnoDB`
- Charset: `utf8mb4 COLLATE utf8mb4_unicode_ci`
- Primary key: `BIGINT UNSIGNED AUTO_INCREMENT`
- Timestamps: `created_at`, `updated_at`
- Soft delete: `deleted_at` (where applicable)

---

## Migration Order

Run migrations in strict numerical order. Each migration must succeed before the next runs.

```
0001 → roles
0002 → permissions
0003 → role_permissions          (FK: roles, permissions)
0004 → users
0005 → user_roles                (FK: users, roles)
0006 → auth_tokens               (FK: users)
0007 → academic_sessions
0008 → academic_classes          (FK: academic_sessions)
0009 → sections                  (FK: academic_classes)
0010 → subjects
0011 → teachers                  (FK: users)
0012 → students                  (FK: users, academic_sessions, academic_classes, sections)
0013 → timetables                (FK: academic_sessions, academic_classes, sections, subjects, teachers)
0014 → periods                   (FK: timetables)
0015 → attendance                (FK: academic_sessions, academic_classes, sections, students)
0016 → attendance_records        (FK: attendance)
0017 → holiday_calendars         (FK: academic_sessions)
0018 → audit_logs                (FK: users)
0019 → system_settings
```

---

## Seeder Order

Run seeders in strict numerical order, AFTER all migrations complete.

```
0001 → roles, permissions, role_permissions
0002 → users (admin), user_roles, academic_sessions (placeholder), system_settings
```

---

## Relationship Diagram

```
users ──────────────────────────────────── user_roles ── roles ── role_permissions ── permissions
  │
  ├── teachers (user_id FK, nullable)
  ├── students (user_id FK, nullable)
  └── auth_tokens (user_id FK)

academic_sessions
  └── academic_classes (academic_session_id FK)
        └── sections (class_id FK)

students ── academic_sessions (session FK)
students ── academic_classes  (class FK)
students ── sections          (section FK)

timetables ── academic_sessions
timetables ── academic_classes
timetables ── sections
timetables ── subjects
timetables ── teachers
  └── periods (timetable_id FK)

attendance ── academic_sessions
attendance ── academic_classes
attendance ── sections
attendance ── students
  └── attendance_records (attendance_id FK)

holiday_calendars ── academic_sessions (nullable)

audit_logs ── users (nullable)
```

---

## Naming Conventions

| Rule | Standard |
|------|----------|
| Table names | `snake_case`, plural |
| Column names | `snake_case` |
| Primary keys | `id` (BIGINT UNSIGNED) |
| Foreign keys | `{referenced_table_singular}_id` |
| Indexes | `idx_{table}_{column(s)}` |
| Unique keys | `uq_{table}_{column(s)}` |
| FK constraints | `fk_{table}_{referenced}` |
| Soft delete | `deleted_at TIMESTAMP NULL DEFAULT NULL` |
| Timestamps | `created_at`, `updated_at` with DEFAULT CURRENT_TIMESTAMP |

---

## Indexing Strategy

| Index type | Columns indexed |
|------------|----------------|
| Login lookup | `users.email` (UNIQUE) |
| Student search | `students.admission_number` (UNIQUE), `students.roll_number`, `students.status` |
| Teacher search | `teachers.employee_id` (UNIQUE), `teachers.email` (UNIQUE), `teachers.status` |
| Attendance query | `attendance.attendance_date`, `attendance.academic_session_id`, `attendance.class_id`, `attendance.status` |
| Academic lookup | `academic_sessions.status`, `academic_sessions.is_current` |
| Timetable query | `timetables.academic_session_id`, `timetables.class_id`, `timetables.teacher_id` |
| RBAC lookups | `role_permissions (role_id, permission_id)` composite PK |
| Soft delete filtering | `deleted_at` indexed on all soft-deletable tables |
| Audit log querying | `audit_logs.entity_type+entity_id`, `audit_logs.action`, `audit_logs.created_at` |

---

## Views

| View | Purpose |
|------|---------|
| `student_overview` | Full student record with class and session names joined |
| `teacher_overview` | Full teacher record with user account status |
| `attendance_summary` | Aggregated attendance totals and percentage per student per session |
| `timetable_overview` | Timetable with teacher, class, subject names joined |
| `active_academic_session` | Returns the single currently active academic session |

---

## Stored Procedures

| Procedure | Parameters | Purpose |
|-----------|------------|---------|
| `sp_attendance_report` | session_id, class_id, date_from, date_to | Attendance report for a class in a date range |
| `sp_search_students` | name, admission_no, class_id, status, page, per_page | Paginated student search |
| `sp_search_teachers` | name, employee_id, department, status, page, per_page | Paginated teacher search |

---

## Functions

| Function | Returns | Purpose |
|----------|---------|---------|
| `fn_student_attendance_percentage` | DECIMAL(5,2) | Attendance % for a student in a session |
| `fn_current_academic_session_id` | BIGINT | ID of the currently active academic session |
| `fn_student_full_name` | VARCHAR | Concatenated full name for a student ID |

---

## Triggers

| Trigger | Table | Event | Purpose |
|---------|-------|-------|---------|
| `trg_users_audit_insert` | users | AFTER INSERT | Log user creation |
| `trg_users_audit_update` | users | AFTER UPDATE | Log user changes |
| `trg_students_audit_insert` | students | AFTER INSERT | Log student enrollment |
| `trg_students_soft_delete_audit` | students | AFTER UPDATE | Log student soft-delete |
| `trg_teachers_audit_insert` | teachers | AFTER INSERT | Log teacher creation |
| `trg_attendance_audit_insert` | attendance | AFTER INSERT | Log attendance marking |
| `trg_attendance_audit_update` | attendance | AFTER UPDATE | Log attendance status change |

---

## How to Run

### Step 1 — Create database

```bash
mysql -u root -p < database/schema/0001-bootstrap.sql
```

### Step 2 — Run migrations in order

```bash
for f in database/migrations/*.sql; do
    echo "Running $f..."
    mysql -u tnm_user -p tnm_school_platform < "$f"
done
```

### Step 3 — Run seeders in order

```bash
for f in database/seeds/*.sql; do
    echo "Running $f..."
    mysql -u tnm_user -p tnm_school_platform < "$f"
done
```

### Step 4 — Install views, procedures, functions, triggers

```bash
mysql -u tnm_user -p tnm_school_platform < database/views/views.sql
mysql -u tnm_user -p tnm_school_platform < database/functions/functions.sql
mysql -u tnm_user -p tnm_school_platform < database/procedures/procedures.sql
mysql -u tnm_user -p tnm_school_platform < database/triggers/triggers.sql
```

### Via Docker

```bash
docker exec -i tnm_mysql mysql -u tnm_user -pCHANGE_ME tnm_school_platform < database/migrations/0001_create_roles_table.sql
```

---

## Security Notes

1. **Admin password must be changed** immediately after first login. Default: `Admin@TNM2026`.
2. **Auth tokens table** stores JWT identifiers. Tokens themselves must not be logged.
3. **Audit logs** are append-only. Do not delete rows. Revoke DB DELETE privilege on `audit_logs` for the app user.
4. **`password_hash`** always uses bcrypt (cost ≥ 12). Never MD5/SHA1.
5. **Prepared statements mandatory** — all PHP PDO queries must use named parameters. Raw string interpolation into SQL is prohibited per `.github/AGENT.md` Section 24.5.

---

## Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | 2026-07-03 | Project Team | Sprint 3 — Database Foundation complete |