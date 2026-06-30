# DB-001 - Entity Relationship Design (ERD)

| Field | Value |
|-------|-------|
| Project | T.N. Memorial School Digital Platform |
| Document ID | DB-001 |
| Version | 1.0 |
| Status | Draft |
| Author | Project Team |
| Reviewer | ChatGPT (Tech Lead) |
| Approver | Project Manager |
| Created On | 2026-06-30 |
| Last Updated | 2026-06-30 |

---

# 1. Purpose

This document defines the logical entities, relationships, and data model for the T.N. Memorial School Digital Platform before physical database implementation.

---

# 2. Core Entities

- Users
- Roles
- Students
- Parents
- Teachers
- Classes
- Sections
- Academic Sessions
- Subjects
- Attendance
- Examinations
- Results
- Fee Structures
- Fee Payments
- Homework
- Timetables
- Notices
- Audit Logs

---

# 3. High-Level Relationships

- One Role → Many Users
- One Class → Many Students
- One Section → Many Students
- One Teacher → Many Subjects
- One Teacher → Many Classes
- One Student → Many Attendance Records
- One Examination → Many Results
- One Student → Many Fee Payments
- One Student → Many Homework Records
- One Academic Session → Many Classes

---

# 4. Relationship Summary

| Parent Entity | Child Entity | Relationship |
|---------------|--------------|--------------|
| Roles | Users | One-to-Many |
| Classes | Students | One-to-Many |
| Sections | Students | One-to-Many |
| Teachers | Subjects | One-to-Many |
| Students | Attendance | One-to-Many |
| Students | Results | One-to-Many |
| Students | Fee Payments | One-to-Many |
| Students | Homework | One-to-Many |
| Academic Sessions | Classes | One-to-Many |

---

# 5. Entity Design Principles

- Every entity shall have a primary key.
- Foreign keys shall enforce relationships.
- Duplicate master records are not permitted.
- Audit fields shall be maintained where applicable.

---

# 6. Future Expansion

The data model supports:

- Multi-school deployment
- Mobile applications
- Payment gateway integration
- AI analytics
- Cloud deployment

---

# 7. References

- ARC-004 Database Architecture
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