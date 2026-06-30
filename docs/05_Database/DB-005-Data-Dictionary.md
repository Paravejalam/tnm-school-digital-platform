# DB-005 - Data Dictionary

| Field | Value |
|-------|-------|
| Project | T.N. Memorial School Digital Platform |
| Document ID | DB-005 |
| Version | 1.0 |
| Status | Draft |
| Author | Project Team |
| Reviewer | ChatGPT (Tech Lead) |
| Approver | Project Manager |
| Created On | 2026-06-30 |
| Last Updated | 2026-06-30 |

---

# 1. Purpose

This document defines the meaning, usage, validation rules, and examples of the major database fields used throughout the T.N. Memorial School Digital Platform.

---

# 2. Common Fields

| Field | Description | Example |
|--------|-------------|---------|
| id | Primary Key | 1 |
| created_at | Record creation timestamp | 2026-06-30 10:00:00 |
| updated_at | Last modification timestamp | 2026-06-30 11:30:00 |
| status | Active/Inactive record status | Active |

---

# 3. User Fields

| Field | Description | Example |
|--------|-------------|---------|
| full_name | Complete user name | Amit Kumar |
| email | Login email | amit@gmail.com |
| phone | Mobile number | 9876543210 |
| password | Encrypted password | ******** |

---

# 4. Student Fields

| Field | Description | Example |
|--------|-------------|---------|
| admission_no | Unique admission number | ADM2026001 |
| roll_no | Student roll number | 15 |
| class_id | Linked class | 8 |
| section_id | Linked section | A |
| dob | Date of birth | 2012-05-16 |
| gender | Student gender | Male |

---

# 5. Teacher Fields

| Field | Description | Example |
|--------|-------------|---------|
| employee_code | Teacher code | EMP001 |
| full_name | Teacher name | Priya Sharma |
| email | Official email | priya@tnmschool.in |

---

# 6. Attendance Fields

| Field | Description | Example |
|--------|-------------|---------|
| attendance_date | Attendance date | 2026-07-01 |
| status | Attendance status | Present |

---

# 7. Examination Fields

| Field | Description | Example |
|--------|-------------|---------|
| exam_name | Examination title | Half Yearly |
| marks | Marks obtained | 92.5 |

---

# 8. Validation Rules

- Email must be unique.
- Admission number must be unique.
- Roll number must be unique within a class.
- Foreign key references must exist.
- Required fields cannot be NULL.

---

# 9. References

- DB-003 Database Schema
- DB-004 Table Specifications

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