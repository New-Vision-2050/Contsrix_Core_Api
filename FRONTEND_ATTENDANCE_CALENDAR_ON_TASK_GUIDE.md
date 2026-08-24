# Frontend guide: which task made the day "متواجد" (`on_task`)

**Endpoint**
- `GET /api/v1/attendance/user-attendance/calendar?month=8&year=2026`
- (also supports `from_date` / `to_date`)

**Base URL (production)**
`https://core-be-production.constrix-nv.com/api/v1`

---

## What changed

Every day object in `payload.days` now carries a `tasks` array: the tasks/notifications
that had activity on that day. On days where `status_key = "on_task"` (`متواجد`), this array
is exactly the reason the employee is not counted absent, so the UI can show it.

The array is `[]` on days with no task activity. All other fields are unchanged.

## Day shape

```json
{
  "date": "2026-08-24",
  "day_name": "الاثنين",
  "day_number": 24,
  "status_key": "on_task",
  "status": "متواجد",
  "work_hours": null,
  "duration_formatted": null,
  "dot_color": "#00BCD4",
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

Nothing else counts — including today. A task that is still open from an earlier date does
not make the employee `متواجد` today; without a session or clock-in, today is `غائب`. A
session left open by mistake is credited for at most 24 hours after it started (or until the
task's own end time, whichever comes first).

## Notes for the UI

- A day can list more than one task; `work_minutes` is per task per day, clamped to that day.
- `work_minutes = 0` is normal for a task that is active but never produced a work session
  (e.g. a multi-assignee notification started by another assignee). The day is still `on_task`.
- The day-level `work_hours` / `duration_formatted` still reflect **clock-in attendance only**,
  so they stay `null` on pure `on_task` days. Use the task-level values to show task time.
- `tasks` is also populated on `present` / `late` days when the employee worked on a task that
  day, so it can be shown as extra detail there too.
