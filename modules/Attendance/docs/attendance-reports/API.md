# HR Attendance Reports — API Documentation

Base URL: `/api/v1/hr/attendance/reports`

Authentication: Bearer JWT (`auth:api`)

Tenancy: company resolved via `InitializeTenancyByRequestData`

---

## GET /

**Permission:** `ATTENDANCE_REPORTS_VIEW`

### Query parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| employee_id | string | yes | Current-company user id (`users.id`) |
| from_date | date | no | Period start (defaults to year start) |
| to_date | date | no | Period end (defaults to current month end) |
| year | int | no | Calendar year shortcut |
| month | int | no | 1-12; requires `year` |
| page | int | no | Monthly report page number; defaults to `1` |
| per_page | int | no | Monthly rows per page; defaults to `12`, maximum `100` |

### Response 200

```json
{
  "code": 200,
  "message": "success",
  "payload": {
    "employee": {
      "id": "6a780f41-b5db-4fd9-9daa-900b9c7f0a4d",
      "name": "John Doe"
    },
    "contract": {
      "attendance_days": 21,
      "required_hours": 168,
      "leave_allowance": 21
    },
    "achieved": {
      "attendance_days": 5,
      "worked_hours": 40,
      "used_leaves": 2,
      "used_holidays": 1
    },
    "remaining": {
      "attendance_days": 16,
      "worked_hours": 128,
      "remaining_leaves": 19
    },
    "monthly_reports": {
      "data": [
        {
          "month": "May 2025",
          "days_in_month": 31,
          "required_attendance_days": 21,
          "used_leaves": 2,
          "earned_leave_days": 1.75,
          "month_holidays": 1,
          "required_hours": 168,
          "actual_attendance_days": 5,
          "remaining_attendance_days": 16,
          "leave_balance_used": 2,
          "remaining_leave_balance": 19,
          "actual_worked_hours": 40,
          "remaining_hours": 128,
          "delays": 2,
          "overtime": 2,
          "status": "approved"
        }
      ],
      "pagination": {
        "current_page": 1,
        "per_page": 12,
        "total": 1,
        "last_page": 1
      }
    }
  }
}
```

---

## Errors

| Code | Meaning |
|------|---------|
| 401 | Unauthenticated |
| 403 | Authorized route user requested an employee outside the current company |
| 404 | Missing route permission (`ATTENDANCE_REPORTS_VIEW`) or resource not found |
| 422 | Validation failed |

See [FORMULAS.md](./FORMULAS.md) for field definitions.
