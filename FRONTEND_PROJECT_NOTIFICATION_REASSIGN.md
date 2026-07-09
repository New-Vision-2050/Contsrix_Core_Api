# Frontend Guide: Project Notification Task Reassignment

## Overview

When a project notification task needs to be reassigned to another employee, the dashboard should expose a **Reassign** action. This action resets the linked employee task for the selected user and starts a fresh lifecycle when that user confirms receipt.

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

- Render the assigned users as a selectable list (radio buttons or list tiles).
- The currently assigned user should be pre-selected or highlighted.
- If the desired user is not in `assigned_users`, allow the admin to pick any employee, because the reassign API will append the selected user to `assigned_user_ids` automatically.
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
  "user_id": "uuid-of-selected-employee"
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

- `422` validation error — invalid or missing `user_id`
- `404` — notification not found
- `409`/generic error — linked task not found or selected user not found

---

## 4. Lifecycle After Reassignment

After reassigning:

1. The new employee sees the notification in **My Tasks** as `approved`.
2. The employee calls:
   ```http
   POST /api/v1/projects/notifications/{id}/confirm-receive
   ```
   with `latitude`, `longitude`, and optional `notes`.
3. A new `ConfirmProjectNotificationPresence` lifecycle process is created for that employee.
4. The task moves to `in_progress` and the employee's independent lifecycle begins.

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
  │ Select employee:             │
  │ ○ Employee A                │
  │ ● Employee B (selected)       │
  │ ○ Employee C                  │
  ├─────────────────────────────┤
  │ [Cancel]  [Confirm Reassign]  │
  └─────────────────────────────┘
```

---

## Notes for Frontend AI

- Reuse the existing user selector and map components used in notification creation.
- The reassignment target can be any existing assigned user or a new employee; the API appends them automatically.
- The map is informational only; no new coordinates are submitted during reassignment.
- After success, refresh the notification state from the API response before updating the UI.
