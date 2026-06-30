# API-002 - Authentication API Specification

| Field | Value |
|-------|-------|
| Project | T.N. Memorial School Digital Platform |
| Document ID | API-002 |
| Version | 1.0 |
| Status | Draft |
| Author | Project Team |
| Reviewer | ChatGPT (Tech Lead) |
| Approver | Project Manager |
| Created On | 2026-06-30 |
| Last Updated | 2026-06-30 |

---

# 1. Purpose

This document defines the authentication APIs used by the T.N. Memorial School Digital Platform.

---

# 2. Authentication Method

- JWT (JSON Web Token)
- HTTPS Only
- Role-Based Access Control (RBAC)

---

# 3. Login API

| Item | Value |
|------|-------|
| Method | POST |
| Endpoint | /api/v1/auth/login |

### Request

```json
{
  "email": "admin@tnmschool.in",
  "password": "password"
}
```

### Success Response

```json
{
  "success": true,
  "token": "jwt_token",
  "user": {}
}
```

---

# 4. Logout API

| Method | POST |
| Endpoint | /api/v1/auth/logout |

---

# 5. Profile API

| Method | GET |
| Endpoint | /api/v1/auth/profile |

---

# 6. Change Password

| Method | PUT |
| Endpoint | /api/v1/auth/change-password |

---

# 7. Forgot Password

| Method | POST |
| Endpoint | /api/v1/auth/forgot-password |

---

# 8. Reset Password

| Method | POST |
| Endpoint | /api/v1/auth/reset-password |

---

# 9. Security Rules

- Passwords must be hashed.
- JWT expires after configurable duration.
- Invalid tokens return HTTP 401.
- All protected APIs require Authorization header.

---

# 10. References

- API-001 REST API Standards
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