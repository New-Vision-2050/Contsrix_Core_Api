# Frontend guide: بدء مهمة / نهاية مهمة in the attendance report

**Affected**
- Attendance & absence report — PDF download, and the `__daily` payload behind Excel/CSV
- Report columns `task_in` / `task_out` (`ReportEnums::ATT_COL_TASK_IN` / `ATT_COL_TASK_OUT`)

---

## What changed

These two columns used to be filled from **task work sessions** — every task that had
activity on a day added a sub-row, and the times were always blank, so the cells only ever
printed `-`. Alongside that, a worked task silently flipped the day from غياب to حضور and
added its minutes to the worked hours.

All of that is gone. The columns are now filled from **real punches taken at a task site**.

An active task publishes a temporary geofence, so an employee can clock in and out at the
task location. When a punch lands inside that geofence:

- the day counts as **حضور** with its hours, exactly like any other punch;
- its time is printed under **بدء مهمة / نهاية مهمة** and is **absent from the
  حضور/انصراف columns** — that is how the report shows the employee worked away from their
  own location.

A punch taken at the employee's own branch, or at an additional location attached to their
constraint, stays in the حضور/انصراف columns as before. If a task geofence happens to
overlap the office, the office wins.

## The two sides are independent

Clock-in and clock-out are attributed separately, so a shift that starts at the office and
ends at a task site splits across the two column pairs:

| Scenario | حضور | انصراف | بدء مهمة | نهاية مهمة |
|---|---|---|---|---|
| Whole shift at the office | `08:00` | `17:00` | — | — |
| Whole shift at a task site | — | — | `08:00` | `17:00` |
| In at the office, out at a task site | `08:00` | — | — | `17:00` |

## When a task time becomes visible

| Task kind | Shown |
|---|---|
| **Project notification** (`is_project_notification = true`) | Immediately, as soon as the punch exists |
| **A task the employee raised themselves** | Only once the task is finished (`status = completed`), which happens after the End-Task procedure has run — auto-approved when no procedure applies, otherwise after the admin steps |

While an employee-raised task is still unfinished, its punch time appears in **neither**
column pair, but the day still reads **حضور** with its hours. Presence is never in
question — only where the time is printed. Once the task completes, the times appear.

## Payload shape (`__daily`)

`task_sessions` is index-aligned with `attendance_sessions` so the PDF's sub-rows line up.
`null` marks a sub-row whose punches were both at ordinary work locations, and the whole
array is `[]` when no sub-row had a task punch at all.

```json
{
  "date": "2026-08-25",
  "display_status": "present",
  "sub_row_count": 1,
  "attendance_sessions": [
    { "clock_in_time": "", "clock_out_time": "" }
  ],
  "task_sessions": [
    {
      "task_time_in": "2026-08-25 08:00:00",
      "task_time_out": "2026-08-25 17:00:00",
      "title": "صيانة محول - حي النرجس"
    }
  ]
}
```

Times are full `Y-m-d H:i:s` strings; the PDF renders them as `HH:MM`. An empty string means
"not reported in this column", which the PDF prints as `-` on a sub-row that has a task and
leaves blank on one that does not.

## Known limitation

A task geofence only exists while the task is `in_progress` and inside its duration window,
so attribution has to happen at the moment of the punch. If a shift is auto-closed *after*
the task's window has already expired, the clock-out cannot be attributed and its time falls
back to the انصراف column. The clock-in, taken while the geofence was live, stays under
بدء مهمة.
