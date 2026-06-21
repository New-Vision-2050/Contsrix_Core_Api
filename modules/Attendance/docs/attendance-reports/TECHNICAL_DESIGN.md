# HR Attendance Reports — Technical Design

## Overview

Production backend for the **HR Attendance & Contract Reports** dashboard inside `modules/Attendance`. Exposes one employee's basic info, summary widgets (contract / achieved / remaining), and monthly report rows.

## Architecture

```
GET /api/v1/hr/attendance/reports
  → AttendanceReportController
    → AttendanceReportRequest (validation + DTO)
    → AttendanceDashboardService (summary)
    → AttendanceReportService (employee + monthly table)
    → Presenters (`code` / `message` / `payload` JSON shape)
```

## Reused entities

| Spec name | Actual table/model |
|-----------|-------------------|
| employee | `users` (`Modules\User\Models\User`) |
| employee_contracts | `employment_contracts` (`EmploymentContract`) |
| attendances | `attendances` (`Attendance`) |
| employee_leaves | `leave_requests` + `leave_balances` |
| employee_holidays | `attendances.is_holiday` + `public_holiday_days` |

## Schema

Attendance Reports introduces no report-specific tables or employment contract columns. It reuses `employment_contracts.annual_leave` as leave allowance and `employment_contracts.working_hours` as the daily-hours source.

## Reporting rules

- All employee, attendance, leave, and contract lookups are scoped to the authenticated user's `company_id`.
- `employee_id` is explicitly authorized before the service layer runs.
- Supported filters are `employee_id`, `from_date`, `to_date`, `year`, and `month`.
- Approved leave requests count only the inclusive overlap with the requested period/month.
- Monthly public holidays count only active weekday holidays for `employment_contracts.country_id`; if the contract has no country, holidays are treated as `0` until a safe company-specific scope is available.
- Required attendance days are derived from weekdays minus applicable public holidays for both dashboard and monthly rows.
- `delays` is the count of late attendance records, not summed delay hours.

## Calculation source of truth

`Modules\Attendance\Services\AttendanceReportCalculator`

Documented field-by-field in [FORMULAS.md](./FORMULAS.md).

## Permissions

| Key | Route |
|-----|-------|
| `ATTENDANCE_REPORTS_VIEW` | `GET /hr/attendance/reports` |

## Tenancy & auth

All routes use `auth:api` + `InitializeTenancyByRequestData`. Report queries are scoped by the authenticated user's `company_id`.

## Tests

Located under `modules/Attendance/Tests/Feature/Reports/` and `modules/Attendance/Tests/Unit/Services/AttendanceReportCalculatorTest.php`.
