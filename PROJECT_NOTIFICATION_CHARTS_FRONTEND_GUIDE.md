# Project Notification Charts API — Frontend Implementation Guide

## Endpoint

```
GET /api/projects/notifications/charts
```

### Required Headers

| Header | Value |
|--------|-------|
| `Authorization` | `Bearer {JWT token}` |
| `X-Tenant` | `{tenant UUID}` |
| `Accept-Language` | `ar` or `en` |

### Permission

`PROJECT_NOTIFICATION_LIST`

---

## Query Parameters (all optional)

All parameters act as filters. When a filter is applied, every chart dimension is re-aggregated **excluding its own dimension's filter** (cross-filtering). This means:

- If you filter by `status=pending`, the **status** chart still shows the full distribution of all statuses, but all other charts (notification_type, work_type, etc.) are narrowed to only records with `status=pending`.
- This lets the user see what other filter values are available within the current selection.

| Parameter | Type | Description |
|-----------|------|-------------|
| `project_id` | UUID | Filter by project |
| `status` | string | Filter by notification status (`pending`, `received`, `in_progress`, `completed`). Comma-separated for multiple (e.g. `pending,received`) |
| `notification_type` | string | Filter by notification type |
| `work_type` | string | Filter by work type |
| `contractor_name` | string | Partial match on contractor name |
| `contractor_id` | UUID | Filter by specific contractor |
| `contractor_category` | string | Filter by contractor category |
| `assigned_user_id` | UUID | Filter by assigned user |
| `task_date` | date (`Y-m-d`) | Filter by exact task date |
| `date_from` | date (`Y-m-d`) | Filter task date >= |
| `date_to` | date (`Y-m-d`) | Filter task date <= |
| `search` | string | Search across notification_number, contractor_name, work_description, repair_point |
| `contractual_engagement_key` | string | Filter by contractual engagement code |

---

## Response Structure

The response uses the standard envelope via `Json::item()`:

```json
{
  "payload": {
    "status": { ... },
    "notification_type": { ... },
    "severity": { ... },
    "work_type": { ... },
    "contractor_category": { ... },
    "project": { ... },
    "assigned_employee": { ... },
    "contractor": { ... },
    "trend": { ... }
  },
  "message": "...",
  "status": 200
}
```

### Each dimension chart has this shape:

```json
{
  "chart_type": "status",
  "total": 150,
  "data": [
    {
      "code": "pending",
      "label": "قيد الانتظار",
      "count": 45,
      "percentage": 30.0
    },
    {
      "code": "approved",
      "label": "موافق عليه",
      "count": 60,
      "percentage": 40.0
    }
  ]
}
```

### Trend chart has this shape:

```json
{
  "chart_type": "trend",
  "total": 150,
  "data": [
    { "month": "2026-01", "count": 20 },
    { "month": "2026-02", "count": 35 }
  ]
}
```

---

## Available Charts

| Chart Key | `chart_type` | Description | Suggested Visualization |
|-----------|-------------|-------------|------------------------|
| `status` | `status` | Distribution by notification status | Donut/Pie chart |
| `notification_type` | `notification_type` | Distribution by notification type | Bar chart |
| `severity` | `severity` | Distribution by severity | Bar chart |
| `work_type` | `work_type` | Distribution by work type | Bar chart |
| `contractor_category` | `contractor_category` | Distribution by contractor category | Bar chart |
| `project` | `project` | Distribution by project | Horizontal bar chart |
| `assigned_employee` | `assigned_employee` | Each assigned employee with their notification count | Horizontal bar chart |
| `contractor` | `contractor` | Each contractor with their assigned notification count | Horizontal bar chart |
| `trend` | `trend` | Monthly count of notifications created | Line chart |

---

## Cross-Filtering UX Pattern

### How it works

1. **Initial load**: Call `GET /charts` with no filters. All charts show full distributions.
2. **User clicks a value in one chart** (e.g. clicks "pending" in the status chart):
   - Add `status=pending` to the query params.
   - Re-fetch `GET /charts?status=pending`.
   - The **status** chart will still show all statuses (because it excludes its own filter), but the **selected value** should be highlighted.
   - All **other** charts will update to show distributions within `status=pending` records only.
3. **User clicks a value in another chart** (e.g. clicks a notification type):
   - Add `notification_type={code}` to the query params.
   - Re-fetch with both `status=pending&notification_type={code}`.
   - Status chart excludes status filter, so shows full distribution. Notification type chart excludes notification_type filter, so shows full distribution. All other charts reflect both filters.

### Frontend implementation tips

- Maintain a `filters` state object: `{ status: null, notification_type: null, ... }`.
- When a user clicks a chart segment, toggle that filter value (click again to clear).
- On any filter change, call the API with all non-null filters as query params.
- Highlight the selected segment(s) in each chart based on the current filter state.
- The `code` field in each data item is the value to use as the filter parameter.
- The `label` field is the localized display text (already translated by the backend based on `Accept-Language` header).

### Example API calls

```bash
# No filters — full distributions
GET /api/projects/notifications/charts

# Filter by status only
GET /api/projects/notifications/charts?status=pending

# Filter by status + notification type
GET /api/projects/notifications/charts?status=pending&notification_type=إصلاح عاجل

# Filter by project + date range
GET /api/projects/notifications/charts?project_id={uuid}&date_from=2026-01-01&date_to=2026-06-30
```

---

## Status Codes & Location Confirmation

| Code | Label (AR) | Label (EN) | Raw Status | `location_confirmed_at` | Meaning |
|------|------------|------------|------------|-------------------------|---------|
| `pending` | بانتظار الرد | Pending | `pending` | — | Pending approval |
| `received` | تم الاستلام | Received | `in_progress` | `NULL` | Employee received the notification but has not yet confirmed location |
| `in_progress` | قيد التنفيذ | In Progress | `in_progress` | NOT NULL | Employee confirmed location and task is in progress |
| `completed` | مكتمل | Completed | `completed` | — | Task completed |

### How Location Confirmation Works

The `received` and `in_progress` codes **both map to the same raw database status** (`in_progress`). They are distinguished by the `location_confirmed_at` timestamp:

- **`received`** → `status = 'in_progress'` AND `location_confirmed_at IS NULL`
  - The employee has acknowledged/received the notification but has not yet arrived at the site to confirm their location.
- **`in_progress`** → `status = 'in_progress'` AND `location_confirmed_at IS NOT NULL`
  - The employee has confirmed their location (arrived at site) and the task is actively in progress.

### Status Filtering

When filtering by `status`, the backend automatically applies the correct logic:

| Filter Value | Query Logic |
|-------------|-------------|
| `?status=pending` | `WHERE status = 'pending'` |
| `?status=received` | `WHERE status = 'in_progress' AND location_confirmed_at IS NULL` |
| `?status=in_progress` | `WHERE status = 'in_progress' AND location_confirmed_at IS NOT NULL` |
| `?status=completed` | `WHERE status = 'completed'` |
| `?status=pending,received` | `WHERE (status = 'pending') OR (status = 'in_progress' AND location_confirmed_at IS NULL)` |

> Multiple statuses can be passed as a comma-separated string (e.g. `?status=pending,received`).

These match the statuses returned by the `map-tasks` endpoint's `statusLookup`.

---

## Error Responses

Standard error envelope:

```json
{
  "error": "Error message",
  "status": 422
}
```

Common errors:
- `401 Unauthorized` — Missing/invalid JWT token
- `403 Forbidden` — Missing `PROJECT_NOTIFICATION_LIST` permission
- `422 Unprocessable Entity` — Invalid filter parameter values
