# ARC-002 - System Architecture

| Field | Value |
|-------|-------|
| Project | T.N. Memorial School Digital Platform |
| Document ID | ARC-002 |
| Version | 1.0 |
| Status | Draft |
| Author | Project Team |
| Reviewer | ChatGPT (Tech Lead) |
| Created On | 2026-06-30 |

---

# 1. Purpose

This document describes the logical system architecture, major software layers, communication flow, and responsibilities of each component.

---

# 2. Architecture Overview

The platform follows a layered architecture.

```text
Users
   │
   ▼
Next.js Frontend
   │
REST API
   │
PHP Backend
   │
Business Services
   │
Repository Layer
   │
MySQL Database
```

---

# 3. Frontend Layer

Responsibilities:

- UI Rendering
- Routing
- Forms
- Validation
- API Calls
- Authentication Handling

Technology

- Next.js
- TypeScript
- TailwindCSS

---

# 4. Backend Layer

Responsibilities

- Authentication
- Business Logic
- Validation
- Authorization
- API Processing

Technology

- PHP

---

# 5. Database Layer

Responsibilities

- Persistent Storage
- Relationships
- Constraints
- Transactions
- Backup

Technology

- MySQL

---

# 6. Security Layer

- Login Authentication
- Role Based Access
- Password Hashing
- Session Validation
- Input Validation
- Audit Logging

---

# 7. Logging

System shall log

- Login
- Logout
- CRUD Operations
- Permission Changes
- Errors

---

# 8. Deployment Model

```text
Browser
      │
      ▼
Next.js Application
      │
REST API
      ▼
Apache + PHP
      │
MySQL
```

---

# 9. Advantages

- Modular
- Scalable
- Secure
- Easy Maintenance
- Easy Testing
- Future Cloud Ready

---

# 10. References

- ARC-001 High Level Architecture
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