# Employee Tasks — Detailed Report Dashboard (Frontend Spec)

This document describes how to build the frontend dashboard page that lists
all employee tasks with full lifecycle details, backed by the new
`GET /admin/employee-tasks/report` (table) and
`GET /admin/employee-tasks/report/{id}` (click-to-detail) endpoints.

## Endpoints

### 1. List (table) — `GET /admin/employee-tasks/report`

Query params (filters):

| Param       | Type   | Description                                   |
|-------------|--------|------------------------------------------------|
| `user_id`   | uuid   | Filter by employee                            |
| `status`    | string | `pending`, `approved`, `rejected`, `in_progress`, `paused`, `completed`, `cancelled` |
| `task_date` | date   | Exact task date (`YYYY-MM-DD`)                |
| `date_from` | date   | Range start (inclusive)                       |
| `date_to`   | date   | Range end (inclusive)                         |
| `search`    | string | Matches title or serial number                |
| `per_page`  | int    | Page size (default 15)                        |
| `page`      | int    | Page number                                   |

Response `items[]` row shape:

```json
{
  "id": "uuid",
  "serial_number": "TASK-0001",
  "title": "Site inspection",
  "employee": { "id": "uuid", "name": "Ahmed Ali", "phone": "0555..." },
  "task_type": { "id": "uuid", "name": "Field Visit" },
  "task_date": "2026-09-16",
  "time_from": "2026-09-16 08:00:00",
  "time_to": "2026-09-16 16:00:00",
  "duration_hours": "8:00",
  "total_task_hours": "7:45",
  "status": "completed",
  "status_label": "مكتملة",
  "final_status": "completed",
  "final_status_label": "مكتملة",
  "task_location": { "latitude": 24.71, "longitude": 46.67, "radius_meters": 150 },
  "created_at": "2026-09-16 07:50:00"
}
```

Plus standard pagination metadata: `current_page`, `last_page`, `per_page`, `total`.

### 2. Detail (click row) — `GET /admin/employee-tasks/report/{id}`

Returns everything in the row shape above, plus:

```json
{
  "description": "...",
  "notes": "...",
  "rejection_reason": null,
  "cancellation_reason": null,
  "locations": {
    "task_location": { "latitude": 24.71, "longitude": 46.67, "radius_meters": 150 },
    "start_location": { "latitude": 24.7101, "longitude": 46.6701 },
    "end_location": { "latitude": 24.7102, "longitude": 46.6702 }
  },
  "approved_by": { "id": "uuid", "name": "Manager X" },
  "approved_at": "2026-09-16 07:55:00",
  "rejected_by": null,
  "rejected_at": null,
  "cancelled_by": null,
  "cancelled_at": null,
  "work_sessions": [
    {
      "id": "uuid",
      "start_time": "2026-09-16 08:00:00",
      "end_time": "2026-09-16 12:00:00",
      "duration_minutes": 240,
      "source": "manual",
      "start_location": { "latitude": 24.71, "longitude": 46.67 },
      "end_location": { "latitude": 24.71, "longitude": 46.67 },
      "notes": null
    }
  ],
  "processes": [
    {
      "type": "create",
      "type_label": "Create Task",
      "status": "approved",
      "requested_by": { "id": "uuid", "name": "Ahmed Ali" },
      "requested_at": "2026-09-16 07:50:00",
      "reviewed_by": { "id": "uuid", "name": "Manager X" },
      "reviewed_at": "2026-09-16 07:55:00",
      "notes": null,
      "location": { "latitude": 24.71, "longitude": 46.67 },
      "steps": [
        {
          "id": "uuid",
          "name": "Manager Approval",
          "order": 1,
          "is_approve": true,
          "status": "approved",
          "assigned_to": { "id": "uuid", "name": "Manager X" },
          "action_by": { "id": "uuid", "name": "Manager X" },
          "acted_at": "2026-09-16 07:55:00"
        }
      ]
    },
    {
      "type": "start",
      "type_label": "Start Task",
      "status": "approved",
      "status_label": "معتمدة",
      "requested_by": { "id": "uuid", "name": "Ahmed Ali" },
      "requested_at": "2026-09-16 07:58:00",
      "reviewed_by": { "id": "uuid", "name": "Manager X" },
      "reviewed_at": "2026-09-16 08:00:00",
      "notes": null,
      "review_notes": null,
      "location": { "latitude": 24.71, "longitude": 46.67 },
      "steps": [ /* same shape as above */ ]
    },
    { "type": "end", "...": "same shape as start" },
    { "type": "approval", "...": "completion approval, no steps (single reviewer)" },
    { "type": "extension", "additional_hours": "2:00", "...": "duration extension request" }
  ]
}
```

`processes` is already sorted chronologically by `requested_at` — render it
directly as a vertical timeline.

Step `status` values: `pending`, `approved`, `rejected`.
Process/request `status` values: `pending`, `approved`, `rejected` (create
uses the task's own status: `pending`/`approved`/`rejected`).

## UI Design

### A. Table page (`/dashboard/employee-tasks/report`)

**Filters bar** (top, sticky):
- Employee picker (searchable select, `user_id`)
- Status select (`status`)
- Date range picker → maps to `date_from` / `date_to`
- Search input (title / serial number)
- "Reset filters" button

**Table columns:**
| Serial # | Employee | Task | Type | Date | Time (from → to) | Duration | Status (badge) | Actions |

- Status badge colors: `pending`=amber, `approved`/`completed`=green,
  `rejected`/`cancelled`=red, `in_progress`=blue, `paused`=gray.
- Row is clickable → opens the **Detail Drawer/Modal** (fetch
  `/admin/employee-tasks/report/{id}` on click, show a loading skeleton
  while fetching).
- "Actions" column: an eye icon button ("View Details") as an explicit
  affordance in addition to row-click.
- Pagination controls at the bottom (page size selector + page numbers),
  bound to `current_page`/`last_page`/`total`.

Use a component library already in the project (e.g. shadcn/ui `Table`,
`Badge`, `Sheet`/`Dialog`, `DatePickerWithRange`, `Combobox`) for consistency
with the rest of the admin dashboard.

### B. Detail panel (Drawer/Modal, opens on row click)

Layout — top to bottom:

1. **Header**: task title + serial number, status badge, close button.
2. **Summary grid** (2–3 columns): employee (avatar/name/phone), task type,
   task date, duration, total worked hours, description/notes.
3. **Map card**: show `task_location` as the target pin with `radius_meters`
   circle; overlay `start_location` and `end_location` as separate markers
   (different colors/icons) if present. If a mapping library isn't already
   used in the project, render lat/lng as text with a "Open in Google Maps"
   link (`https://maps.google.com/?q={lat},{lng}`) as a fallback.
4. **Work sessions table** (if `work_sessions` not empty): start/end time,
   duration, start/end location links.
5. **Process timeline** (main feature): a vertical stepper/timeline
   component. For each entry in `processes[]`:
   - Icon + colored dot per `type` (create/start/end/approval/extension).
   - Header row: `type_label`, status badge, `requested_by.name`,
     `requested_at`.
   - Expandable sub-list of `steps[]` (if any): each step shows
     `name`, `assigned_to.name`, final `status` badge, and — if acted —
     `action_by.name` + `acted_at` ("Accepted/Rejected by X on Y").
     Steps with `status = pending` and no `action_by` show "Awaiting
     action".
   - If no `steps` (e.g. `approval`/`extension`), show `reviewed_by.name` +
     `reviewed_at` + `review_notes` directly on the entry.
6. **Footer**: final status banner ( derived from `status`/`status_label` at
   the top level — e.g. "Final Status: Completed").

### Suggested component breakdown (React example)

```
EmployeeTaskReportPage
├── ReportFiltersBar
├── ReportTable
│   └── ReportTableRow (onClick → openDetail(id))
├── PaginationBar
└── TaskDetailDrawer (Sheet/Dialog)
    ├── TaskSummaryHeader
    ├── TaskLocationsMap
    ├── WorkSessionsTable
    └── ProcessTimeline
        └── ProcessTimelineItem
            └── ProcessStepRow
```

### API calls

```ts
// List
GET /admin/employee-tasks/report?user_id=&status=&date_from=&date_to=&search=&page=&per_page=

// Detail
GET /admin/employee-tasks/report/{id}
```

Both return the standard project JSON envelope (`{ success, message, data,
pagination? }` — follow the existing `Json::items` / `Json::item` /
`Json::error` response shape already used across the API).
