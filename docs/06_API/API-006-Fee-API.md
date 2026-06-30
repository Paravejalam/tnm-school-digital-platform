# API-006 - Fee Management API Specification

| Field | Value |
|-------|-------|
| Project | T.N. Memorial School Digital Platform |
| Document ID | API-006 |
| Version | 1.0 |
| Status | Draft |
| Author | Project Team |
| Reviewer | ChatGPT (Tech Lead) |
| Approver | Project Manager |
| Created On | 2026-06-30 |
| Last Updated | 2026-06-30 |

---

# 1. Purpose

This document defines REST APIs for fee management including fee structure, fee collection, online payments, receipts, pending dues, concessions, and reports.

---

# 2. Base Endpoints

```
/api/v1/fees
/api/v1/payments
/api/v1/receipts
/api/v1/dues
```

---

# 3. Major APIs

| Module | Endpoint |
|---------|----------|
| Fee Structure | /api/v1/fees |
| Fee Collection | /api/v1/payments |
| Fee Receipt | /api/v1/receipts |
| Pending Dues | /api/v1/dues |

---

# 4. Example Request

```json
{
  "studentId": 101,
  "amount": 5000,
  "paymentMode": "Online"
}
```

---

# 5. Example Response

```json
{
  "success": true,
  "message": "Fee payment successful",
  "data": {
    "receiptNo": "RCPT-2026-001",
    "amount": 5000,
    "status": "Paid"
  }
}
```

---

# 6. Validation Rules

- Student must exist.
- Fee amount cannot be negative.
- Payment mode is mandatory.
- Receipt number must be unique.
- Duplicate payment transactions are not allowed.

---

# 7. Security

- JWT Authentication Required.
- Admin and Accounts Staff can manage fee records.
- Students and Parents can view only their own payment history.
- Online payments require secure transaction validation.

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