# DB-004 - Table Specifications

| Field | Value |
|-------|-------|
| Project | T.N. Memorial School Digital Platform |
| Document ID | DB-004 |
| Version | 1.0 |
| Status | Draft |
| Author | Project Team |
| Reviewer | ChatGPT (Tech Lead) |
| Approver | Project Manager |
| Created On | 2026-06-30 |
| Last Updated | 2026-06-30 |

---

# 1. Purpose

This document defines the table-level specifications for the T.N. Memorial School Digital Platform database.

---

# 2. Common Table Standards

Every table shall contain:

- Primary Key (id)
- created_at
- updated_at
- status (where applicable)

---

# 3. Core Tables

## roles

| Column | Type | Constraint |
|---------|------|------------|
| id | BIGINT | PK |
| role_name | VARCHAR(100) | UNIQUE |
| description | TEXT | NULL |

---

## users

| Column | Type | Constraint |
|---------|------|------------|
| id | BIGINT | PK |
| role_id | BIGINT | FK |
| full_name | VARCHAR(150) | NOT NULL |
| email | VARCHAR(150) | UNIQUE |
| password | VARCHAR(255) | NOT NULL |
| phone | VARCHAR(20) | NULL |

---

## students

| Column | Type | Constraint |
|---------|------|------------|
| id | BIGINT | PK |
| admission_no | VARCHAR(30) | UNIQUE |
| roll_no | VARCHAR(30) | UNIQUE |
| class_id | BIGINT | FK |
| section_id | BIGINT | FK |
| full_name | VARCHAR(150) | NOT NULL |
| gender | ENUM | NOT NULL |
| dob | DATE | NOT NULL |

---

## teachers

| Column | Type | Constraint |
|---------|------|------------|
| id | BIGINT | PK |
| employee_code | VARCHAR(30) | UNIQUE |
| full_name | VARCHAR(150) | NOT NULL |
| email | VARCHAR(150) | UNIQUE |
| phone | VARCHAR(20) | NULL |

---

## attendance

| Column | Type | Constraint |
|---------|------|------------|
| id | BIGINT | PK |
| student_id | BIGINT | FK |
| attendance_date | DATE | NOT NULL |
| status | ENUM | Present/Absent |

---

## examinations

| Column | Type | Constraint |
|---------|------|------------|
| id | BIGINT | PK |
| exam_name | VARCHAR(100) | NOT NULL |
| session_id | BIGINT | FK |

---

## results

| Column | Type | Constraint |
|---------|------|------------|
| id | BIGINT | PK |
| student_id | BIGINT | FK |
| examination_id | BIGINT | FK |
| marks | DECIMAL(5,2) | NOT NULL |

---

# 4. Naming Standards

- Primary Key → id
- Foreign Keys → table_id
- Dates → snake_case
- Status Columns → ENUM

---

# 5. Future Tables

- library
- transport
- hostel
- payroll
- inventory
- AI analytics

---

# 6. References

- DB-003 Database Schema
- DB-002 ER Diagram

---

# Revision History

| Version | Date | Author | Changes |
|----------|------|--------|---------|
| 1.0 | 2026-06-30 | Project Team | Initial Version |

---

# Approval

| Role | Name | Status |
|------|------|--------|
| Tech Lead | ChatGPT | Pending |
| Project Manager | You | Pending |

---

© T.N. Memorial School Digital Platform