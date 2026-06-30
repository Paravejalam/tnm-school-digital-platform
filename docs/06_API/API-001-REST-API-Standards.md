# API-001 - REST API Standards

| Field | Value |
|-------|-------|
| Project | T.N. Memorial School Digital Platform |
| Document ID | API-001 |
| Version | 1.0 |
| Status | Draft |
| Author | Project Team |
| Reviewer | ChatGPT (Tech Lead) |
| Approver | Project Manager |
| Created On | 2026-06-30 |
| Last Updated | 2026-06-30 |

---

# 1. Purpose

This document defines the REST API standards and conventions for the T.N. Memorial School Digital Platform.

---

# 2. Base URL

```
/api/v1
```

---

# 3. HTTP Methods

| Method | Purpose |
|---------|---------|
| GET | Retrieve data |
| POST | Create new resource |
| PUT | Update complete resource |
| PATCH | Partial update |
| DELETE | Delete resource |

---

# 4. Request Format

- Content-Type: application/json
- UTF-8 Encoding
- HTTPS Only

---

# 5. Response Format

```json
{
  "success": true,
  "message": "Request Successful",
  "data": {}
}
```

---

# 6. HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Internal Server Error |

---

# 7. Naming Standards

- Endpoints use lowercase.
- Resources use plural nouns.
- JSON keys use snake_case.

Example:

```
/students
/classes
/teachers
```

---

# 8. Security Standards

- JWT Authentication
- HTTPS
- Role-Based Access Control (RBAC)
- Input Validation
- SQL Injection Protection

---

# 9. Versioning

```
/api/v1/
/api/v2/
```

---

# 10. References

- DB-005 Data Dictionary
- ARC-002 System Architecture

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