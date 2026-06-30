# BRD-002 - Business Rules

| Field | Value |
|-------|-------|
| Project | T.N. Memorial School Digital Platform |
| Document ID | BRD-002 |
| Version | 1.0 |
| Status | Draft |
| Author | Project Team |
| Reviewer | ChatGPT (Tech Lead) |
| Approver | Project Manager |
| Created On | 2026-06-30 |
| Last Updated | 2026-06-30 |

---

# 1. Purpose

This document defines the business rules that govern academic, administrative, and financial operations within the T.N. Memorial School Digital Platform.

---

# 2. Scope

These rules apply to all ERP users, modules, and business processes.

---

# 3. General Rules

- Every user must have a unique account.
- Every user must log in before accessing ERP modules.
- All actions must be recorded in audit logs.
- Users can access only authorized modules.
- System data must remain consistent and secure.

---

# 4. Student Management Rules

- Every student shall have a unique Admission Number.
- One student belongs to one active class and section in an academic session.
- Student records cannot be permanently deleted once academic records exist.
- Student status shall be Active, Inactive, Graduated, or Transferred.

---

# 5. Teacher Management Rules

- Every teacher shall have a unique Employee ID.
- Teachers may teach multiple classes and subjects.
- Teachers can only manage their assigned classes.

---

# 6. Attendance Rules

- Attendance shall be recorded once per student per day.
- Attendance cannot be entered for future dates.
- Attendance can only be modified by authorized users.
- Attendance status shall be Present, Absent, Leave, or Holiday.

---

# 7. Examination Rules

- Every examination belongs to an academic session.
- Marks cannot exceed the maximum marks defined.
- Results can only be published after verification.
- Published results require authorization before modification.

---

# 8. Fee Management Rules

- Every payment shall generate a unique receipt.
- Duplicate receipt numbers are not allowed.
- Outstanding dues shall be calculated automatically.
- Fee concessions require administrative approval.

---

# 9. User & Security Rules

- Passwords must be securely stored.
- Role-Based Access Control (RBAC) is mandatory.
- Unauthorized access attempts shall be logged.
- User sessions shall expire after inactivity.

---

# 10. Reporting Rules

- Reports must use real-time data.
- Exported reports shall respect user permissions.
- Financial reports are available only to authorized users.

---

# 11. Notification Rules

- Notices shall have a publication date.
- Expired notices shall be archived automatically.
- Notifications shall be visible only to intended users.

---

# 12. Data Integrity Rules

- Duplicate primary records are prohibited.
- Mandatory fields cannot be empty.
- Foreign key relationships must remain valid.
- Data validation is required before saving.

---

# 13. Exception Handling

- Invalid data shall be rejected.
- System errors shall be logged.
- Permission violations shall generate access denied responses.
- Critical failures shall notify administrators.

---

# 14. Assumptions

- Users follow school policies.
- Administrators maintain master data.
- Academic sessions are configured before operations begin.

---

# 15. References

- BRD-001 Business Requirements
- DOC-002 Stakeholder Analysis
- Future Functional Requirements (FRD)

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