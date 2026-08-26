# Frontend guide: the `tasks` array on calendar days

**Endpoint**
- `GET /api/v1/attendance/user-attendance/calendar?month=8&year=2026`
- (also supports `from_date` / `to_date`)

**Base URL (production)**
`https://core-be-production.constrix-nv.com/api/v1`

---

## What changed

**The `on_task` / `متواجد` day status has been removed.** Holding a task no longer makes
an employee present. An active task now publishes a temporary geofence that the employee
can clock in at, so a task site produces a real attendance row like any other location —
and presence comes only from that row.

Concretely, for a day where the employee had a task but never clocked in:

| | Before | Now |
|---|---|---|
| `status_key` | `on_task` | `absent`, or `required` while the clock-in deadline still stands |
| `status` | `متواجد` | `غائب` / `مطلوب للحضور` |
| `dot_color` | `#00BCD4` | `#F44336` / `#2196F3` |

**Removed fields:** `summary.on_task_count` is gone, and `dot_color` no longer returns
`#00BCD4`. Any UI branch keyed on `status_key === "on_task"` is now dead code.

**Kept:** every day object still carries a `tasks` array. It is now purely informational —
the tasks that had activity on that day, useful as detail next to whatever status the day
has. The array is `[]` on days with no task activity.

## Day shape

```json
{
  "date": "2026-08-24",
  "day_name": "الاثنين",
  "day_number": 24,
  "status_key": "absent",
  "status": "غائب",
  "work_hours": null,
  "duration_formatted": null,
  "dot_color": "#F44336",
  "attendance_count": 2,
  "tasks": [
    {
      "id": "9c1f0b2a-....",
      "title": "صيانة محول - حي النرجس",
      "status": "in_progress",
      "source": "project_notification",
      "task_date": "2026-08-24",
      "project_id": "8b21....",
      "notification_id": "7fa3....",
      "notification_number": "PN-1024",
      "notification_status": "in_progress",
      "work_minutes": 95,
      "work_hours": 1.58,
      "duration_formatted": "01h 35m"
    }
  ]
}
```

## Task fields

| Field | Type | Notes |
|---|---|---|
| `id` | string | `employee_task_request` id |
| `title` | string \| null | Task title; falls back to the notification's `work_description`, then `إشعار {number}` |
| `status` | string \| null | Task status (`approved`, `in_progress`, `paused`, `completed`) |
| `source` | string | `employee_task` or `project_notification` |
| `task_date` | string \| null | `Y-m-d` |
| `project_id` | string \| null | Present for project / notification tasks |
| `notification_id`, `notification_number`, `notification_status` | string \| null | Only for `project_notification` tasks |
| `work_minutes` | int | Minutes worked on that specific day (0 when the task was active but had no session) |
| `work_hours` | float | `work_minutes / 60`, rounded to 2 |
| `duration_formatted` | string \| null | `"HHh MMm"`, `null` when `work_minutes = 0` |

## Which days a task appears on

A task is attached to a day only when it is really related to it:

- any day on which the task had a work session (partial days included);
- the task's own day (`task_date`, or the notification's `task_date`) and the day it ended,
  while the task is active (`in_progress` / `paused`, or a notification that is
  `in_progress` / `completed`).

A session left open by mistake is credited for at most 24 hours after it started (or until
the task's own end time, whichever comes first).

## Notes for the UI

- A day can list more than one task; `work_minutes` is per task per day, clamped to that day.
- `work_minutes = 0` is normal for a task that is active but never produced a work session
  (e.g. a multi-assignee notification started by another assignee).
- The day-level `work_hours` / `duration_formatted` reflect **clock-in attendance only**.
  A task's own time is only in the task-level values, and never contributes to the day's
  worked hours.
- To have a task day count as attendance, the employee must clock in at the task's
  location. See the task temporary-geofence entries in the constraint payload
  (`additional_locations[]` with `source: "employee_task"`).
