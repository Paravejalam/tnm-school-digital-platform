# DB-003 - Database Schema Specification

| Field | Value |
|-------|-------|
| Project | T.N. Memorial School Digital Platform |
| Document ID | DB-003 |
| Version | 1.0 |
| Status | Draft |
| Author | Project Team |
| Reviewer | ChatGPT (Tech Lead) |
| Approver | Project Manager |
| Created On | 2026-06-30 |
| Last Updated | 2026-06-30 |

---

# 1. Purpose

This document defines the logical database schema for the T.N. Memorial School Digital Platform.

---

# 2. Database Information

| Item | Value |
|------|-------|
| Database | tnm_school |
| DBMS | MySQL 8.x |
| Character Set | utf8mb4 |
| Collation | utf8mb4_unicode_ci |

---

# 3. Master Tables

- roles
- users
- academic_sessions
- classes
- sections
- subjects
- teachers
- students
- parents

---

# 4. Transaction Tables

- attendance
- examinations
- results
- fee_structures
- fee_payments
- homework
- notices
- audit_logs

---

# 5. Primary Key Strategy

Every table shall contain:

- Auto Increment Primary Key
- Created At
- Updated At

---

# 6. Foreign Key Strategy

- User → Role
- Student → Class
- Student → Section
- Subject → Teacher
- Result → Examination
- Attendance → Student
- Fee Payment → Student

---

# 7. Naming Convention

Tables

- plural lowercase

Example

students

teachers

attendance

Columns

snake_case

Example

student_name

created_at

updated_at

---

# 8. Indexing Strategy

Indexes shall be created for:

- Foreign Keys
- Admission Number
- Roll Number
- Email
- Username
- Session
- Class

---

# 9. Future Expansion

Schema supports:

- Multi School
- Transport
- Hostel
- Library
- AI Analytics
- Mobile Apps

---

# 10. References

- DB-001 Entity Relationship Design
- DB-002 Entity Relationship Diagram

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