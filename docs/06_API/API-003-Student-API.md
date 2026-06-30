# API-003 - Student API Specification

| Field | Value |
|-------|-------|
| Project | T.N. Memorial School Digital Platform |
| Document ID | API-003 |
| Version | 1.0 |
| Status | Draft |
| Author | Project Team |
| Reviewer | ChatGPT (Tech Lead) |
| Approver | Project Manager |
| Created On | 2026-06-30 |
| Last Updated | 2026-06-30 |

---

# 1. Purpose

This document defines the REST APIs related to student management.

---

# 2. Base Endpoint

```
/api/v1/students
```

---

# 3. Get All Students

| Method | GET |
|---------|-----|
| Endpoint | /api/v1/students |

### Success Response

```json
[
  {
    "studentId": 1,
    "name": "Rahul Sharma"
  }
]
```

---

# 4. Get Student by ID

| Method | GET |
|---------|-----|
| Endpoint | /api/v1/students/{id} |

---

# 5. Create Student

| Method | POST |
|---------|------|
| Endpoint | /api/v1/students |

### Request

```json
{
  "name": "Rahul Sharma",
  "class": "10",
  "section": "A"
}
```

---

# 6. Update Student

| Method | PUT |
|---------|-----|
| Endpoint | /api/v1/students/{id} |

---

# 7. Delete Student

| Method | DELETE |
|---------|----------|
| Endpoint | /api/v1/students/{id} |

---

# 8. Validation Rules

- Student ID must be unique.
- Name is mandatory.
- Class is mandatory.
- Section is mandatory.
- Duplicate admission numbers are not allowed.

---

# 9. Security

- JWT Authentication Required
- Admin and Office Staff can create/update.
- Teachers have read-only access.
- Students can access only their own profile.

---

# 10. HTTP Status Codes

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

# 11. References

- API-001 REST API Standards
- API-002 Authentication API
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