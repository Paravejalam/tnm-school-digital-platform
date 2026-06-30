# ARC-004 - Database Architecture

| Field | Value |
|-------|-------|
| Project | T.N. Memorial School Digital Platform |
| Document ID | ARC-004 |
| Version | 1.0 |
| Status | Draft |
| Author | Project Team |
| Reviewer | ChatGPT (Tech Lead) |
| Approver | Project Manager |
| Created On | 2026-06-30 |
| Last Updated | 2026-06-30 |

---

# 1. Purpose

This document defines the database architecture of the T.N. Memorial School Digital Platform, including database design principles, naming conventions, relationships, integrity rules, and scalability considerations.

---

# 2. Database Technology

| Component | Technology |
|-----------|------------|
| Database Engine | MySQL 8.x |
| Storage Engine | InnoDB |
| Character Set | UTF8MB4 |
| Collation | utf8mb4_unicode_ci |

---

# 3. Design Principles

- Normalized database design (up to 3NF where appropriate)
- Use primary and foreign keys
- Maintain referential integrity
- Avoid duplicate data
- Use indexing for performance
- Soft delete for important entities where required

---

# 4. Logical Database Layers

```text
Application Layer
        │
Business Logic Layer
        │
Repository / Data Access
        │
MySQL Database
```

---

# 5. Core Database Domains

- Users & Roles
- Students
- Teachers
- Classes & Sections
- Academic Sessions
- Attendance
- Subjects
- Examinations
- Results
- Fees
- Homework
- Timetables
- Notices
- Audit Logs
- Settings

---

# 6. Naming Conventions

## Tables

- Singular or plural naming shall be used consistently.
- Lowercase with underscores.

Example:

- students
- teachers
- attendance_records
- fee_payments

---

## Columns

Examples:

- student_id
- teacher_id
- created_at
- updated_at
- deleted_at

---

# 7. Key Design Standards

- Every table shall have a Primary Key.
- Foreign Keys shall enforce relationships.
- Appropriate indexes shall be created.
- Timestamps shall be maintained where applicable.

---

# 8. Data Integrity Rules

- No orphan records
- Foreign key validation
- Mandatory field validation
- Unique constraints where applicable
- Transaction support for critical operations

---

# 9. Backup Strategy

- Daily database backup
- Weekly full backup
- Monthly archive backup
- Backup verification process

---

# 10. Security Considerations

- Least privilege access
- Parameterized queries
- Password hashing
- Audit logging
- Regular backups

---

# 11. Future Scalability

The database architecture supports:

- Additional ERP modules
- Mobile applications
- Multi-school deployment
- Cloud migration
- Analytics and BI integration

---

# 12. References

- ARC-001 High-Level Architecture
- ARC-002 System Architecture
- ARC-003 Component Architecture
- REQ-001 Functional Requirements

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