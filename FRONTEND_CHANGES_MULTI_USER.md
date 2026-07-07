# Frontend Integration Guide — Multi-User Project Notifications

## Overview

Project Notifications now support **multiple assigned users** instead of a single `assigned_user_id`. Two new boolean flags control how workflow approvals and progress work:

- `all_users_can_approve` — Whether any assigned user can approve/reject workflow steps
- `independent_progress` — Whether each assigned user progresses through lifecycle procedures independently

---

## Breaking Changes

### `assigned_user_id` → `assigned_user_ids`

| Before | After |
|---|---|
| `assigned_user_id: "uuid-string"` (single UUID) | `assigned_user_ids: ["uuid1", "uuid2", ...]` (array of UUIDs) |
| `assigned_user: { id, name }` (single object) | `assigned_users: [{ id, name }, ...]` (array of objects) |
| — | `assigned_user: { id, name }` (first user, kept for backward compat) |

### Removed Fields
- `assigned_user_id` — **removed** from response, replaced by `assigned_user_ids`
- `assignedUser` relation — **removed** from response, replaced by `assigned_users` array

### New Fields in Response
```json
{
  "assigned_user_ids": ["uuid1", "uuid2"],
  "all_users_can_approve": false,
  "independent_progress": true,
  "assigned_users": [
    { "id": "uuid1", "name": "Ahmed" },
    { "id": "uuid2", "name": "Mohamed" }
  ],
  "assigned_user": { "id": "uuid1", "name": "Ahmed" }
}
```

> **Note**: `assigned_user` is the first user in the array, kept for convenience. Use `assigned_users` for the full list.

---

## API Changes

### 1. Create Project Notification

**`POST /projects/notifications`**

#### New Request Fields

| Field | Type | Required | Default | Description |
|---|---|---|---|---|
| `assigned_user_ids` | `string[]` (UUID array) | **Yes** (min 1) | — | Array of user UUIDs to assign |
| `all_users_can_approve` | `boolean` | No | `false` | Whether all assigned users can approve/reject workflow steps |
| `independent_progress` | `boolean` | No | `true` | Whether each user progresses through lifecycle procedures independently |

#### Removed Request Fields
- `assigned_user_id` — replaced by `assigned_user_ids`

#### Example Request
```json
{
  "project_id": "uuid",
  "assigned_user_ids": ["uuid-ahmed", "uuid-mohamed"],
  "all_users_can_approve": false,
  "independent_progress": true,
  "task_date": "2026-07-08",
  "duration_hours": 8,
  "work_description": "Site inspection",
  "notification_type": "uuid",
  "task_latitude": 25.1972,
  "task_longitude": 55.2744
}
```

---

### 2. Update Project Notification

**`PUT /projects/notifications/{id}`**

#### New Request Fields

| Field | Type | Required | Default | Description |
|---|---|---|---|---|
| `assigned_user_ids` | `string[]` (UUID array) | No | `null` (unchanged) | Replace assigned users |
| `all_users_can_approve` | `boolean` | No | `null` (unchanged) | Update flag |
| `independent_progress` | `boolean` | No | `null` (unchanged) | Update flag |

#### Removed Request Fields
- `assigned_user_id` — replaced by `assigned_user_ids`

---

### 3. List / My Tasks / My Inbox / Charts

All list endpoints now return `assigned_user_ids` (array), `assigned_users` (array of objects), and `assigned_user` (first user) instead of `assigned_user_id` / `assigned_user`.

#### Filter Change: `assigned_user_id`

The `assigned_user_id` filter now uses `whereJsonContains` — it matches any notification where the given user ID is **in** the `assigned_user_ids` array.

```
GET /projects/notifications?assigned_user_id=uuid-ahmed
```
Returns all notifications where Ahmed is one of the assigned users (not just the only one).

---

### 4. Available Actions

**`GET /projects/notifications/{id}/available-actions`**

No change to the endpoint URL or response format. However, the behavior changes when `independent_progress = true`:

- Each user sees only **their own** taken procedures
- A procedure that Ahmed completed will NOT show as "taken" for Mohamed
- Each user gets their own independent list of available next actions

---

### 5. End Task

**`POST /projects/notifications/{id}/end`**

No change to request body. When `independent_progress = true`, the end-task workflow process is created per-user — the authenticated user gets their own end-task approval workflow.

---

### 6. Take Action

**`POST /projects/notifications/{id}/take-action`**

No change to request body. When `independent_progress = true`, the `WorkflowProcedureTaken` event is scoped to the authenticated user.

---

### 7. Export

**`POST /projects/notifications/export`**

The "Assigned User" column now shows all assigned user names (comma-separated) instead of a single name.

---

## Behavior: `all_users_can_approve`

| Value | Behavior |
|---|---|
| `true` | All assigned users are added to `authorized_user_ids` on every pending workflow step. Any of them can approve/reject any step. First-come-first-served. |
| `false` (default) | Only the hierarchy-resolved action taker (from procedure settings) can approve/reject each step. Assigned users cannot approve unless they are the designated approver. |

### Frontend UI Suggestion
- Show as a toggle/checkbox in the create/edit form
- Label: "Allow any assigned user to approve workflow steps"
- Default: **unchecked** (`false`)

---

## Behavior: `independent_progress`

| Value | Behavior |
|---|---|
| `true` (default) | Each assigned user gets their **own** workflow process for lifecycle procedures (site status update, fine, location confirmation, work stoppage, work resumption, task postponement, end task). Users progress independently — Ahmed finishing his site status update does NOT complete it for Mohamed. |
| `false` | All assigned users share **one** workflow process. When the process completes, the action is recorded once for the task. |

### What's shared vs independent when `independent_progress = true`

| Workflow | Shared or Independent |
|---|---|
| Task creation approval (`CreateProjectNotificationTask`) | **Shared** — one approval workflow for the task |
| Confirm-receive (`ConfirmProjectNotificationPresence`) | **Shared** — each user confirms their own receipt, but the task starts once |
| Site status update | **Independent** — each user has their own |
| Fine | **Independent** |
| Location confirmation | **Independent** |
| Work stoppage report | **Independent** |
| Work resumption | **Independent** |
| Task postponement | **Independent** |
| End task | **Independent** |

### Frontend UI Suggestion
- Show as a toggle/checkbox in the create/edit form
- Label: "Each assigned user progresses independently through procedures"
- Default: **checked** (`true`)

---

## My Tasks vs My Inbox Behavior

### `/my-tasks`
- Shows tasks where the user is in `assigned_user_ids` (JSON array contains check)
- **Always shows for all assigned users** regardless of `independent_progress`
- Status filter: `received`, `in_progress`, `completed`

### `/my-inbox`
- Shows tasks with pending workflow steps assigned to the current user
- When `independent_progress = true`:
  - **Creation workflow**: appears for all assigned users (shared process)
  - **Lifecycle workflows** (site status, fine, etc.): appears **only** for the user who triggered it (per-user process)
- When `independent_progress = false`:
  - All workflows appear for all assigned users (shared processes)

---

## Summary of All Response Changes

| Old Field | New Field | Type |
|---|---|---|
| `assigned_user_id` | `assigned_user_ids` | `string[]` |
| `assigned_user` (single) | `assigned_users` | `array<{id, name}>` |
| — | `assigned_user` (first) | `{id, name} or null` |
| — | `all_users_can_approve` | `boolean` |
| — | `independent_progress` | `boolean` |

## Summary of All Request Changes

| Old Field | New Field | Type | Required |
|---|---|---|---|
| `assigned_user_id` | `assigned_user_ids` | `string[]` | Yes (create), No (update) |
| — | `all_users_can_approve` | `boolean` | No (default: `false`) |
| — | `independent_progress` | `boolean` | No (default: `true`) |
