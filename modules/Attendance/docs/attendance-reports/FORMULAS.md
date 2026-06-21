# HR Attendance Reports — Formula Documentation

Implementation class: `Modules\Attendance\Services\AttendanceReportCalculator`

---

## Dashboard — Contract block

### contract.attendance_days
- **Definition:** Required attendance days for the requested reporting period.
- **Source:** Calendar weekdays and active country-scoped public holidays.
- **Formula:** Weekday count in `[from_date, to_date]` minus active weekday public holiday dates for `employment_contracts.country_id`.
- **Fallback:** If the employment contract has no `country_id`, holidays count as `0`.
- **Example:** May 2025 has 22 weekdays; 1 active public holiday gives `21`.

### contract.required_hours
- **Definition:** Total contracted working hours for the period.
- **Source:** Derived `contract.attendance_days`, `employment_contracts.working_hours` (default 8)
- **Formula:** `contract.attendance_days × working_hours`
- **Example:** `21 × 8 = 168`

### contract.leave_allowance
- **Definition:** Annual leave entitlement days.
- **Source:** Earliest official employment contract service date for the employee (`commencement_date`, falling back to `start_date`).
- **Formula:** Full completed service years as of the report period end date. `service_years <= 5` gives `21`; `service_years > 5` gives `30`.
- **Fallback:** If no employment contract start/commencement date exists, use the minimum entitlement `21`.
- **Example:** `21`

---

## Dashboard — Achieved block

### achieved.attendance_days
- **Definition:** Distinct days the employee was present.
- **Source:** `attendances`
- **Formula:** `COUNT(DISTINCT business_date)` where `is_absent = 0` AND `is_holiday = 0` AND `clock_in_time IS NOT NULL`
- **Example:** `5`

### achieved.worked_hours
- **Definition:** Total net worked hours in period.
- **Source:** `attendances.total_work_hours`
- **Formula:** `SUM(total_work_hours)` rounded to 1 decimal
- **Example:** `40.0`

### achieved.used_leaves
- **Definition:** Approved leave days overlapping the filter period.
- **Source:** `leave_requests`
- **Formula:** For each approved leave overlapping `[from_date, to_date]`, add inclusive overlap days only: `DATEDIFF(min(end_date, to_date), max(start_date, from_date)) + 1`
- **Example:** `2`

### achieved.used_holidays
- **Definition:** Distinct holiday attendance days in period.
- **Source:** `attendances.is_holiday`
- **Formula:** `COUNT(DISTINCT business_date)` where `is_holiday = 1`
- **Example:** `1`

---

## Dashboard — Remaining block

### remaining.attendance_days
- **Formula:** `contract.attendance_days − achieved.attendance_days` (min 0)

### remaining.worked_hours
- **Formula:** `contract.required_hours − achieved.worked_hours` (min 0)

### remaining.remaining_leaves
- **Formula:** `contract.leave_allowance − achieved.used_leaves` (min 0)

---

## Monthly table fields

### month
- **Formula:** `Carbon(business_date).format('F Y')`
- **Example:** `May 2025`

### days_in_month
- **Source:** Calendar
- **Formula:** `Carbon::daysInMonth`

### required_attendance_days
- **Formula:** Weekday count in month minus active public holiday days for the employee contract country.
- **Fallback:** If the employment contract has no `country_id`, holidays count as `0`. TODO: extend safely if product adds company-specific holiday scope.

### used_leaves
- **Formula:** Approved leave overlap days inside the month only. Example: May 30 to June 3 counts 2 days in May and 3 days in June.

### earned_leave_days
- **Formula:** `contract.leave_allowance / 12`, rounded to 2 decimals.
- **Examples:** `21 / 12 = 1.75`; `30 / 12 = 2.50`.

### month_holidays
- **Source:** `public_holiday_days` joined to `public_holidays`
- **Formula:** `COUNT(DISTINCT public_holiday_days.date)` where day is a weekday inside the month, `public_holidays.is_active = true`, and `public_holidays.country_id = employment_contracts.country_id`

### required_hours
- **Formula:** `required_attendance_days × employment_contracts.working_hours` (default 8)

### actual_attendance_days / actual_worked_hours
- Same present-day and hour sums as dashboard but scoped to month

### remaining_attendance_days
- **Formula:** `required_attendance_days − actual_attendance_days` (min 0)

### leave_balance_used
- **Formula:** Same as `used_leaves` for the month (days charged to balance)

### remaining_leave_balance
- **Formula:** Derived annual leave entitlement minus cumulative approved leave from period start through month end.

### remaining_hours
- **Formula:** `required_hours − actual_worked_hours` (min 0)

### delays
- **Source:** `attendances.is_late`
- **Formula:** `COUNT(*)` where `is_late = 1` in month

### overtime
- **Source:** `attendances.overtime_hours`
- **Formula:** `SUM(overtime_hours)` in month

### status
- **Formula:** Aggregate of attendance workflow statuses for month (`rejected` > `pending_approval` > `approved`)
