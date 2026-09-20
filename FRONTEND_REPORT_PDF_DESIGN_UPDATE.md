# Frontend Guide — Attendance Report PDF Design Update

**Endpoint affected:** `POST /api/v1/reports` (report creation wizard) → generated PDF for the
`attendance_absence` report type.

**Effective date:** September 2026
**Scope:** PDF export **only**. Excel/CSV exports emit summary metrics and are unchanged.

---

## 1. What Changed in the PDF Design

### Removed table columns (PDF)

The following 4 columns are **no longer rendered** in the detailed attendance table, in both
display modes (`employee_per_page` and `by_day`), regardless of what the report config contains:

| Column ID (step3)    | Arabic label | English label |
| -------------------- | ------------ | ------------- |
| `branch`             | الفرع        | Branch        |
| `management`         | الادارة      | Management    |
| `official_in`        | دخول رسمي    | Official in   |
| `official_out`       | خروج رسمي    | Official out  |

### Added to the employee header

- **Employee-per-page view:** the employee's **branch** and **management** are appended next to
  the employee **avatar image and name** in the dark blue header row of each employee table:
  `[image] Employee Name | Branch / Management` followed by the Present/Absent/Day Off/Leave badges.
- **By-day view:** branch and management appear as a small subtitle directly under the
  employee **name** in the Employee column.

---

## 2. Required Frontend Action

In the report wizard **Step 3** ("Attendance data / column selection"):

- **Remove** (or at minimum, stop sending) the following options from the
  `attendanceDataTypeIds` payload:
  - `branch`
  - `management`
  - `official_in`
  - `official_out`

Sending them is **harmless** — the backend ignores these IDs when rendering the PDF — but they
should be hidden from the UI so users don't expect them to affect the output.

### Still-valid step-3 column IDs (unchanged)

`day`, `actual_in`, `actual_out`, `clock_out_cause`, `clock_in_location`, `clock_out_location`,
`delay`, `overtime`, `total_hours`, `calculated_hours`

---

## 3. Response / Contract Changes

- The `GET /api/v1/reports/lookups` response may still include the removed IDs under the
  attendance detail columns list until the backend cleans them up. Do **not** render checkboxes
  for the 4 removed IDs.
- No changes to the `downloadUrl` / media response shape.

---

## 4. Visual Summary

**Before (per-employee view):** columns included `الفرع`, `الادارة`, `دخول رسمي`, `خروج رسمي`
alongside actual in/out.

**After:** those columns are gone; the header reads:
`[avatar] John Doe | Riyadh Branch / Sales Management` with the status badges on the opposite side.
