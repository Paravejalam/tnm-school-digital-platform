# REQ-001 - Functional Requirements Specification (FRS)

| Field | Value |
|-------|-------|
| Project | T.N. Memorial School Digital Platform |
| Document ID | REQ-001 |
| Version | 1.0 |
| Status | Draft |
| Author | Project Team |
| Reviewer | ChatGPT (Tech Lead) |
| Approver | Project Manager |
| Created On | 2026-06-30 |
| Last Updated | 2026-06-30 |

---

# 1. Purpose

This document defines the functional requirements of the T.N. Memorial School Digital Platform. It specifies the expected behavior of every module, feature, and user interaction to ensure consistent implementation across the application.

---

# 2. Scope

This specification applies to all functional modules of Version 1.0.

---

# 3. Functional Modules

| Module ID | Module |
|------------|--------------------------|
| FR-01 | Authentication |
| FR-02 | Dashboard |
| FR-03 | Student Management |
| FR-04 | Teacher Management |
| FR-05 | Class & Section Management |
| FR-06 | Attendance |
| FR-07 | Examination |
| FR-08 | Results |
| FR-09 | Fee Management |
| FR-10 | Homework |
| FR-11 | Timetable |
| FR-12 | Notice Board |
| FR-13 | Reports |
| FR-14 | User Management |
| FR-15 | Audit Logs |
| FR-16 | Settings |

---

# 4. Authentication (FR-01)

## Description

The system shall authenticate users before allowing access to protected modules.

### Functional Requirements

- User login
- User logout
- Forgot password
- Change password
- Session management
- Role validation

---

# 5. Dashboard (FR-02)

The dashboard shall display:

- Total Students
- Total Teachers
- Today's Attendance
- Fee Collection Summary
- Recent Notices
- Upcoming Events

---

# 6. Student Management (FR-03)

The system shall allow authorized users to:

- Add student
- Edit student
- View student
- Search student
- Promote student
- Transfer student
- Archive student
- Generate student profile

---

# 7. Teacher Management (FR-04)

The system shall support:

- Teacher registration
- Subject allocation
- Class allocation
- Attendance tracking
- Teacher profile management

---

# 8. Attendance (FR-06)

The system shall:

- Mark attendance
- Update attendance
- View attendance
- Generate daily reports
- Generate monthly reports

---

# 9. Examination (FR-07)

The system shall:

- Create examination
- Manage subjects
- Configure maximum marks
- Schedule examinations

---

# 10. Results (FR-08)

The system shall:

- Enter marks
- Calculate totals
- Calculate percentages
- Assign grades
- Publish results
- Print report cards

---

# 11. Fee Management (FR-09)

The system shall:

- Generate fee records
- Accept fee payments
- Generate receipts
- Track dues
- Generate fee reports

---

# 12. Homework (FR-10)

The system shall:

- Create homework
- Assign homework
- View homework
- Download homework

---

# 13. Timetable (FR-11)

The system shall:

- Create timetable
- Update timetable
- Publish timetable

---

# 14. Notice Board (FR-12)

The system shall:

- Create notices
- Publish notices
- Archive notices

---

# 15. Reports (FR-13)

The system shall generate:

- Attendance reports
- Fee reports
- Examination reports
- Student reports
- Teacher reports

---

# 16. User Management (FR-14)

The system shall:

- Create users
- Assign roles
- Reset passwords
- Disable users

---

# 17. Audit Logs (FR-15)

The system shall record:

- Login history
- Data modifications
- Permission changes
- Administrative actions

---

# 18. Settings (FR-16)

The system shall manage:

- Academic Session
- School Information
- Roles
- Permissions
- Backup

---

# 19. Functional Dependencies

- Authentication
- Role-Based Access Control
- Master Data
- Academic Session
- Database Connectivity

---

# 20. References

- Business Analysis Documents
- Discovery Documents

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