# API-004 - Teacher API Specification

| Field | Value |
|-------|-------|
| Project | T.N. Memorial School Digital Platform |
| Document ID | API-004 |
| Version | 1.0 |
| Status | Draft |
| Author | Project Team |
| Reviewer | ChatGPT (Tech Lead) |
| Approver | Project Manager |
| Created On | 2026-06-30 |
| Last Updated | 2026-06-30 |

---

# 1. Purpose

This document defines the REST APIs used for teacher management.

---

# 2. Base Endpoint

```
/api/v1/teachers
```

---

# 3. Get All Teachers

| Method | GET |
|---------|-----|
| Endpoint | /api/v1/teachers |

### Success Response

```json
[
  {
    "teacherId": 1,
    "name": "Amit Singh",
    "department": "Science"
  }
]
```

---

# 4. Get Teacher by ID

| Method | GET |
|---------|-----|
| Endpoint | /api/v1/teachers/{id} |

---

# 5. Create Teacher

| Method | POST |
|---------|------|
| Endpoint | /api/v1/teachers |

### Request

```json
{
  "name": "Amit Singh",
  "department": "Science",
  "designation": "PGT",
  "email": "amit@tnmschool.in"
}
```

---

# 6. Update Teacher

| Method | PUT |
|---------|-----|
| Endpoint | /api/v1/teachers/{id} |

---

# 7. Delete Teacher

| Method | DELETE |
|---------|--------|
| Endpoint | /api/v1/teachers/{id} |

---

# 8. Validation Rules

- Teacher ID must be unique.
- Name is mandatory.
- Department is mandatory.
- Email must be unique.
- Designation is mandatory.

---

# 9. Security

- JWT Authentication Required
- Only Admin and HR can create or delete teacher records.
- Teachers can update only their own profile.
- Read access based on role permissions.

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
- API-003 Student API
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