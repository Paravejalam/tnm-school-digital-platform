# API-005 - Academic API Specification

| Field | Value |
|-------|-------|
| Project | T.N. Memorial School Digital Platform |
| Document ID | API-005 |
| Version | 1.0 |
| Status | Draft |
| Author | Project Team |
| Reviewer | ChatGPT (Tech Lead) |
| Approver | Project Manager |
| Created On | 2026-06-30 |
| Last Updated | 2026-06-30 |

---

# 1. Purpose

This document defines the REST APIs used for academic management including classes, sections, subjects, attendance, examinations, marks, report cards, and timetables.

---

# 2. Base Endpoints

```
/api/v1/classes
/api/v1/subjects
/api/v1/attendance
/api/v1/exams
/api/v1/results
/api/v1/timetable
```

---

# 3. Major APIs

| Module | Endpoint |
|---------|----------|
| Classes | /api/v1/classes |
| Subjects | /api/v1/subjects |
| Attendance | /api/v1/attendance |
| Timetable | /api/v1/timetable |
| Exams | /api/v1/exams |
| Marks | /api/v1/results |

---

# 4. Example Request

```json
{
  "class":"10",
  "section":"A",
  "subject":"Mathematics"
}
```

---

# 5. Example Response

```json
{
  "success": true,
  "message": "Academic data retrieved successfully",
  "data": {
    "class": "10",
    "section": "A",
    "subject": "Mathematics"
  }
}
```

---

# 6. Validation Rules

- Class must exist.
- Subject must belong to selected class.
- Attendance cannot exceed total students.
- Marks must be within maximum marks.
- Duplicate timetable entries are not allowed.

---

# 7. Security

- JWT Authentication Required.
- Teachers can update attendance and marks.
- Students have read-only access to their academic records.
- Admin manages master academic data.

---

# 8. HTTP Status Codes

| Code | Meaning |
|------|----------|
|200|Success|
|201|Created|
|400|Validation Error|
|401|Unauthorized|
|403|Forbidden|
|404|Not Found|
|500|Server Error|

---

# 9. References

- API-001 REST API Standards
- API-002 Authentication API
- API-003 Student API
- API-004 Teacher API
- REQ-001 Functional Requirements

---

# Revision History

| Version | Date | Author | Changes |
|----------|------|--------|---------|
|1.0|2026-06-30|Project Team|Initial Version|

---

# Approval

| Role | Name | Status |
|------|------|--------|
| Tech Lead | ChatGPT | Pending |
| Project Manager | You | Pending |

---

© T.N. Memorial School Digital Platform