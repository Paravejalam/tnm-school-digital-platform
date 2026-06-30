# API-007 - Administration API Specification

| Field | Value |
|-------|-------|
| Project | T.N. Memorial School Digital Platform |
| Document ID | API-007 |
| Version | 1.0 |
| Status | Draft |
| Author | Project Team |
| Reviewer | ChatGPT (Tech Lead) |
| Approver | Project Manager |
| Created On | 2026-06-30 |
| Last Updated | 2026-06-30 |

---

# 1. Purpose

This document defines REST APIs for administration including user management, role management, permissions, school settings, notifications, audit logs, and system configuration.

---

# 2. Base Endpoints

```
/api/v1/users
/api/v1/roles
/api/v1/permissions
/api/v1/settings
/api/v1/notifications
/api/v1/audit-logs
```

---

# 3. Major APIs

| Module | Endpoint |
|---------|----------|
| Users | /api/v1/users |
| Roles | /api/v1/roles |
| Permissions | /api/v1/permissions |
| School Settings | /api/v1/settings |
| Notifications | /api/v1/notifications |
| Audit Logs | /api/v1/audit-logs |

---

# 4. Example Request

```json
{
  "role": "Teacher",
  "permissions": [
    "attendance.read",
    "attendance.write"
  ]
}
```

---

# 5. Example Response

```json
{
  "success": true,
  "message": "Role updated successfully",
  "data": {
    "role": "Teacher",
    "permissionsAssigned": 2
  }
}
```

---

# 6. Validation Rules

- Username must be unique.
- Email address must be unique.
- Role name cannot be duplicated.
- Permission names must be predefined.
- System settings require administrator approval before modification.

---

# 7. Security

- JWT Authentication Required.
- Only Super Admin can manage roles and permissions.
- Audit logs cannot be modified or deleted.
- Sensitive settings require elevated privileges.

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
- API-005 Academic API
- API-006 Fee API
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