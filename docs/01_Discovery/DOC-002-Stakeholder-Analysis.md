# DOC-002 - Stakeholder Analysis

| Field | Value |
|-------|-------|
| Project | T.N. Memorial School Digital Platform |
| Document ID | DOC-002 |
| Version | 1.0 |
| Status | Draft |
| Author | Project Team |
| Reviewer | ChatGPT (Tech Lead) |
| Approver | Project Manager |
| Created On | 2026-06-30 |
| Last Updated | 2026-06-30 |

---

# 1. Purpose

This document identifies all stakeholders involved in the T.N. Memorial School Digital Platform and defines their responsibilities, expectations, system access, and interactions with the ERP.

---

# 2. Scope

This document covers internal and external stakeholders who will use or interact with the School ERP and Website.

---

# 3. Stakeholder Overview

| Stakeholder | Category | System Access |
|-------------|----------|---------------|
| Super Admin | Internal | Full Access |
| Principal | Internal | Administrative |
| Vice Principal | Internal | Administrative |
| Office Staff | Internal | Office Operations |
| Teachers | Internal | Academic |
| Accountant | Internal | Finance |
| Librarian | Internal | Library |
| Receptionist | Internal | Visitor & Enquiry |
| Students | External | Student Portal |
| Parents | External | Parent Portal |
| Visitors | External | Website Only |

---

# 4. Stakeholder Details

## 4.1 Super Admin

### Responsibilities

- Manage entire ERP
- User Management
- System Configuration
- Backup & Restore
- Security Management

### Goals

- Maintain system availability
- Ensure data security
- Manage users efficiently

### Permissions

- Full CRUD Access
- User Role Assignment
- System Settings
- Reports
- Database Backup

---

## 4.2 Principal

### Responsibilities

- Academic Monitoring
- Staff Management
- Student Performance
- Reports Review

### Goals

- Improve academic performance
- Monitor school operations

### Permissions

- Dashboard
- Attendance Reports
- Examination Reports
- Student Records
- Notices Approval

---

## 4.3 Vice Principal

### Responsibilities

- Assist Principal
- Daily Operations
- Discipline Monitoring

### Permissions

- Attendance
- Timetable
- Student Reports
- Teacher Reports

---

## 4.4 Office Staff

### Responsibilities

- Admissions
- Student Records
- Certificates
- Document Verification

### Permissions

- Student CRUD
- Admission Module
- Certificate Generation

---

## 4.5 Teachers

### Responsibilities

- Attendance
- Marks Entry
- Homework
- Student Progress

### Permissions

- Assigned Classes
- Attendance
- Results Entry
- Homework
- Notices

---

## 4.6 Accountant

### Responsibilities

- Fee Collection
- Receipt Generation
- Financial Reports

### Permissions

- Fee Module
- Payment Reports
- Due Reports

---

## 4.7 Librarian

### Responsibilities

- Book Issue
- Book Return
- Inventory

### Permissions

- Library Module
- Member Management

---

## 4.8 Receptionist

### Responsibilities

- Visitor Management
- Enquiries
- Admissions Support

### Permissions

- Visitor Register
- Admission Enquiry

---

## 4.9 Students

### Responsibilities

- View academic information

### Permissions

- Dashboard
- Attendance
- Timetable
- Homework
- Results
- Notices

---

## 4.10 Parents

### Responsibilities

- Monitor student progress

### Permissions

- Attendance
- Fees
- Results
- Notices
- Homework

---

## 4.11 Visitors

### Responsibilities

- Access public information

### Permissions

- Website Only

---

# 5. Stakeholder Communication Matrix

| Stakeholder | ERP | Website | Reports | Notifications |
|-------------|-----|----------|----------|---------------|
| Super Admin | ✅ | ✅ | ✅ | ✅ |
| Principal | ✅ | ❌ | ✅ | ✅ |
| Teachers | ✅ | ❌ | ✅ | ✅ |
| Students | ✅ | ❌ | Limited | ✅ |
| Parents | ✅ | ❌ | Limited | ✅ |
| Visitors | ❌ | ✅ | ❌ | ❌ |

---

# 6. Stakeholder Priority

| Stakeholder | Priority |
|-------------|----------|
| Super Admin | Critical |
| Principal | Critical |
| Teachers | High |
| Office Staff | High |
| Accountant | High |
| Students | High |
| Parents | High |
| Librarian | Medium |
| Receptionist | Medium |
| Visitors | Low |

---

# 7. Assumptions

- Every user will have role-based access.
- Authentication is mandatory.
- User activities will be logged.

---

# 8. Risks

| Risk | Mitigation |
|------|------------|
| Unauthorized Access | Role-Based Access Control |
| Incorrect Permissions | Approval Workflow |
| Data Leakage | Access Restriction |

---

# 9. References

- DOC-001 Vision & Scope
- Functional Requirements
- Authentication Design

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