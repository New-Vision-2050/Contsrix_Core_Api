# Frontend Guide: Project Notification Task Reassignment

## Overview

When a project notification task needs to be reassigned to one or more employees, the dashboard should expose a **Reassign** action. This action resets the linked employee task, replaces the notification's assigned users with the selected list, and starts a fresh per-user lifecycle when each employee confirms receipt.

This is commonly used after a task is ended with `shift_handover` status, so the next employee can take over with a clean lifecycle.

---

## UI Entry Points

Add a **Reassign Task** button or menu item in:

- Project notification detail page (admin/dashboard view)
- Task card actions in the notification list
- Shift-handover completion flow (optional — auto-suggest reassignment after handover)

Show the action only when the notification has a linked employee task (`employee_task_request_id` is not null).

---

## 1. Present Assigned Users

### API

```http
GET /api/v1/projects/notifications/{id}
```

### Data to read

Use the existing notification response fields:

- `assigned_users` — list of employees already assigned to the notification
- `employee_task` — linked task summary

### UI behavior

- Render the assigned users as a multi-selectable list (checkboxes or multi-select chips), matching the user selector used in notification creation.
- The currently assigned users should be pre-selected or highlighted.
- The admin can select any employees (existing or new). The reassign API replaces `assigned_user_ids` with the submitted list, exactly like the creation form.
- Optionally call `/api/v1/projects/notifications/employees-with-locations` if you want to filter by available/nearby employees.

---

## 2. Present Location on Map

### API

```http
GET /api/v1/projects/notifications/{id}
```

### Data to read

- `employee_task.task_latitude`
- `employee_task.task_longitude`
- `project.name`

### UI behavior

- Show a map centered on the notification's task coordinates (`task_latitude`, `task_longitude`).
- Place a marker at the exact task location.
- If employee locations are available, render employee markers near the task so the admin can visually pick the closest available employee.
- Use the map component already used in project notification creation for consistency.

---

## 3. Call Reassign API

### Endpoint

```http
POST /api/v1/projects/notifications/{id}/reassign
```

### Headers

```http
Accept: application/json
Authorization: Bearer {token}
X-Tenant: {company_identifier}
Content-Type: application/json
```

### Request body

```json
{
  "assigned_user_ids": [
    "uuid-of-selected-employee-1",
    "uuid-of-selected-employee-2"
  ]
}
```

### Success response

The API returns the refreshed notification payload:

```json
{
  "data": { /* full notification object */ },
  "message": "Task reassigned successfully"
}
```

### UI feedback

- Show a success toast: "Task reassigned successfully. The employee can now confirm receipt to start a new lifecycle."
- Refresh the notification detail/list.
- The notification status will be `in_progress` and the linked task status will be `approved`.

### Error cases to handle

- `422` validation error — invalid, missing, or empty `assigned_user_ids`
- `404` — notification not found
- `409`/generic error — linked task not found or one of the selected users not found

---

## 4. Lifecycle After Reassignment

After reassigning:

1. Each new employee sees the notification in their **Inbox** immediately because the backend creates a pending per-user `CreateProjectNotificationTask` process for them (the same creation workflow used during notification creation).
2. Each employee calls:
   ```http
   POST /api/v1/projects/notifications/{id}/confirm-receive
   ```
   with `latitude`, `longitude`, and optional `notes`.
3. The employee's pending creation workflow step is approved and the task moves to `in_progress` on the first confirmation.
4. Each confirmed employee begins their own independent lifecycle.

Do **not** block reassignment based on the current task status. The backend accepts any status so admins can reassign freely, including from `approved` (never started) or `completed` (after shift handover).

---

## 5. Suggested Flow Wireframe

```
[Notification Detail]
        │
        ▼
[Reassign Task button]
        │
        ▼
[Reassign Modal]
  ┌─────────────────────────────┐
  │ Map with task location marker│
  ├─────────────────────────────┤
  │ Select employee(s):          │
  │ ☑ Employee A                 │
  │ ☑ Employee B (selected)      │
  │ ☑ Employee C (selected)      │
  ├─────────────────────────────┤
  │ [Cancel]  [Confirm Reassign]  │
  └─────────────────────────────┘
```

---

## Notes for Frontend AI

- Reuse the existing multi-user selector and map components used in notification creation.
- The reassignment target list replaces the previous `assigned_user_ids`; send the full desired list, not just the delta.
- The map is informational only; no new coordinates are submitted during reassignment.
- After success, refresh the notification state from the API response before updating the UI.
