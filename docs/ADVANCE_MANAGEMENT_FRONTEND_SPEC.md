# Advance (السلفة) Management — Frontend Spec

For the **frontend team**. This doc covers everything needed to build:

1. **Settings form** — create/edit advance policies (الإعدادات → السلف).
2. **User Profile section** — assign policies to a user + list the user's advances.
3. **Admin list** — all company advance requests (sidebar: طلبات السلف).
4. **Employee flow** — request a سلفة and track it (mobile/self-service).

Sibling docs: `docs/LEAVE_USER_PROFILE_FRONTEND_SPEC.md` and
`docs/LEAVE_REQUEST_API_SPEC.md` (the leave feature follows the exact same pattern —
the screens can share most of their UI components).

## Base URL & Headers

```
Base URL: https://core-be-production.constrix-nv.com/api/v1
```

| Header | Value |
|--------|-------|
| `Authorization` | `Bearer <auth_token>` |
| `X-Tenant` | `<tenant UUID>` |
| `Accept` | `application/json` |
| `Accept-Language` | `en` or `ar` |
| `Content-Type` | `application/json` (PUT/POST) |

> Booleans are sent as `true`/`false` and rendered in responses as `0`/`1`.
>
> Response envelope everywhere: `{ "code", "message", "payload" }` — single objects in
> `payload` (`code = SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT`), lists in `payload` as an
> array (`code = SUCCESS_WITH_LIST_PAYLOAD_OBJECTS`), plus a sibling `pagination`
> object on paginated endpoints: `{ page, next_page, last_page, result_count }`.

---

## 0. What is an advance (السلفة)?

Unlike leave, **advances have no types and no yearly balances**. There is one flat
pool of **advance policies** per company. A policy defines the guardrails of a loan:

| Policy field | Meaning |
|--------------|---------|
| `name` | Display name (unique per company), e.g. "سلفة طارئة" |
| `max_amount` | Maximum amount a single request can ask for |
| `max_installments` | Maximum number of repayment installments (1–120) |
| `max_active_advances` | How many concurrent (pending + approved) requests a user may have under this policy (default 1) |
| `requires_attachment` | If `1`, the request must include at least one attachment |

An employee can only request under a policy that has been **assigned** to them.
"Active" means `pending` or `approved`; `rejected`/`cancelled` requests free the slot.
Availability is computed **live** — there is no year, no balance, and no reset.

---

## 1. Settings — Advance Policies (الإعدادات → السلف)

A table + create/edit dialog. All endpoints require the corresponding
`human-resources.settings*advance-policy.*` permission.

### `GET /advance-policies?page=&per_page=`
Paginated list. Row shape:

```json
{
  "id": "uuid",
  "name": "سلفة طارئة",
  "max_amount": 5000.0,
  "max_installments": 6,
  "max_active_advances": 1,
  "requires_attachment": 0,
  "created_at": "2026-10-13 10:00:00"
}
```

### `POST /advance-policies`
```json
{
  "name": "سلفة طارئة",
  "max_amount": 5000,
  "max_installments": 6,
  "max_active_advances": 1,
  "requires_attachment": false
}
```
Validation (`422`): `name` required, unique per company · `max_amount` required,
0.01–9999999999.99 · `max_installments` required, 1–120 · `max_active_advances`
optional, 1–10 (default 1) · `requires_attachment` optional (default false).

### `GET /advance-policies/{id}` · `PUT /advance-policies/{id}` · `DELETE /advance-policies/{id}`
`PUT` takes the same body as `POST` (all fields). `DELETE` removes the policy —
note that deleting a policy assigned to users also removes their assignment.

**Form suggestion:** 4 inputs + 1 checkbox, no types/categories anywhere.

---

## 2. User Profile — "السلف" section

Same placement pattern as the leave section: a **policies table** (read-only) and an
**assign action** (edit), plus an optional **requests tab**.

> Required permission to view/assign another user's policies:
> `human-resources.settings*advance-policy.assign`. Hide the section without it.

### 2.1 Get a user's assigned policies + availability

### `GET /users/{userId}/advance-policies`

```json
{
  "code": "SUCCESS_WITH_LIST_PAYLOAD_OBJECTS",
  "message": "User advance policies retrieved successfully",
  "payload": [
    {
      "advance_policy_id": "uuid",
      "name": "سلفة طارئة",
      "is_default": 0,
      "assigned_at": "2026-10-10 09:00:00",
      "max_amount": 5000.0,
      "max_installments": 6,
      "max_active_advances": 1,
      "requires_attachment": 0,
      "active_count": 1,
      "pending_amount": 2000.0,
      "active_amount": 0.0,
      "remaining_slots": 0,
      "can_request": 0
    }
  ]
}
```

| Field | Notes |
|-------|-------|
| `active_count` | This user's pending + approved requests under the policy |
| `pending_amount` / `active_amount` | Sum of pending / approved request amounts |
| `remaining_slots` | `max_active_advances − active_count` (floor 0) |
| `can_request` | `1` when the user may submit a new request under this policy right now |

**Suggested columns:** Policy name · Max amount · Max installments · Active ·
Remaining slots · Can request badge.

### 2.2 Assign policies to a user (edit)

### `PUT /users/{userId}/advance-policies`

**Sync semantics — important:** the sent set **replaces** the user's assignments.
Send the full list of policy ids the user should end up with. An empty array removes
all assignments.

```json
{ "advance_policy_ids": ["uuid-1", "uuid-2"] }
```

Response: the user's updated rows (same shape as 2.1) — re-render directly from it.

**Building the picker:** fetch all company policies from `GET /advance-policies`
(§1) and pre-check the ones already in the user's list. On save, send the checked ids.

Validation (`422`): each id must be a uuid of an existing policy in the current company.

### 2.3 The user's advance requests (list his advances)

### `GET /advance-requests?user_id={userId}&status=&advance_policy_id=&page=&per_page=`

Admin list filtered to this user (permission
`human-resources.advance-requests*advance-request.list`). Paginated rows of the
advance-request object — full shape in §5.3 (includes `status_label_ar` and the
`workflow` block).

**Suggested columns:** Amount · Installments · Policy · Status · Created at.
Row click → `GET /advance-requests/{id}` for the detail view (reason, notes,
attachments, rejection reason, workflow progress).

---

## 3. Sidebar — طلبات السلف (Admin list)

### `GET /advance-requests?status=&user_id=&advance_policy_id=&page=&per_page=`

All company requests, newest first, paginated. Filters: `status`
(`pending|approved|rejected|cancelled`), `user_id`, `advance_policy_id`.

| Endpoint | Permission |
|----------|-----------|
| `GET /advance-requests` | `human-resources.advance-requests*advance-request.list` |
| `GET /advance-requests/{id}` | `human-resources.advance-requests*advance-request.view` |

Rows include `user_name` and `advance_policy_name` — no extra lookups needed.

---

## 4. Approver Inbox (بانتظار موافقتي) — dedicated advance inbox

A mirror of `GET /admin/employee-tasks/inbox` scoped to advance requests only.
It lists **pending requests whose current workflow step authorizes the caller** —
i.e. "advances waiting on MY approval". Same criteria as the unified admin inbox,
so these routes also carry **no permission middleware**.

| Endpoint | Purpose |
|----------|---------|
| `GET /admin/advance-requests/inbox?page=1&per_page=15&date_from=&date_to=` | Paginated pending-for-me list |
| `GET /admin/advance-requests/inbox-counts` | Badge counts: `{ "pending_advance_requests": n, "total": n }` |
| `PATCH /admin/advance-requests/{id}/approve` | Approve my pending step |
| `PATCH /admin/advance-requests/{id}/reject` | Reject — body `{ "rejection_reason": "..." }` (required, ≤1000) |

Inbox rows use the **same unified shape** as `admin/employee-tasks/inbox`
(`type: "advance_request"`, `type_label`, `category: "advance"`, `employee`,
`status`, `current_step: { id, name, step_order, is_approve, action_takers[] }`,
`summary: { advance_policy, amount, installments, reason }`, `created_at`).
Pagination keys: `{ current_page, last_page, per_page, total }`.

> The unified `admin/employee-tasks/inbox` also includes advance requests
> (`type = advance_request`) and its `inbox-counts` payload has
> `pending_advance_requests` — use whichever view fits the screen.

---

## 5. Employee flow (mobile / self-service)

These endpoints require **no special permission** — any authenticated user can use
them for themselves.

### 5.1 My policies + availability

### `GET /my/advance-policies`
Same row shape as 2.1, for the logged-in employee. Use it to populate the request
form's policy selector — show `max_amount` / `max_installments` hints and disable
policies where `can_request = 0`.

### 5.2 Create a request

### `POST /advance-requests`
```json
{
  "advance_policy_id": "uuid",
  "amount": 2000,
  "installments": 4,
  "reason": "ظروف عائلية طارئة",
  "attachments": ["<file-reference>", "..."],
  "notes": "optional"
}
```

| Field | Rule |
|-------|------|
| `advance_policy_id` | required, uuid, must exist in the company **and be assigned to the employee** |
| `amount` | required, 0.01+ |
| `installments` | required, integer 1–120 |
| `reason` | required, ≤1000 chars |
| `attachments` | optional array of strings; **required** when the policy's `requires_attachment = 1` |
| `notes` | optional, ≤1000 chars |

Business validations — all returned as `422` with a plain message, surface it to the
user:

- `This advance policy is not assigned to you.`
- `Amount exceeds the policy maximum of {max_amount}.`
- `Installments exceed the policy maximum of {max_installments}.`
- `This advance policy requires an attachment.`
- `You already have {n} active advance request(s) under this policy (maximum {max}).`

### 5.3 Request object (response shape)

```json
{
  "id": "uuid",
  "user_id": "uuid",
  "user_name": "Ahmed Ali",
  "advance_policy_id": "uuid",
  "advance_policy_name": "سلفة طارئة",
  "amount": 2000.0,
  "installments": 4,
  "reason": "ظروف عائلية طارئة",
  "status": "pending",
  "status_label_ar": "قيد الانتظار",
  "attachments": ["..."],
  "notes": null,
  "rejection_reason": null,
  "approved_at": null,
  "rejected_at": null,
  "workflow": { "in_progress": true, "pending_step_order": 1 },
  "created_at": "2026-10-13 10:30:00"
}
```

### 5.4 My requests

### `GET /advance-requests/my?status=&advance_policy_id=&page=&per_page=`
Paginated, newest first.

### 5.5 Cancel

### `POST /advance-requests/{id}/cancel`
Owner only, only while `status = pending`. Cancelling frees the policy slot
immediately (`remaining_slots` goes back up).

---

## 6. The Approval Workflow (what happens behind a request)

```
Employee creates request
        │
        ▼
Server validates: assigned policy? amount? installments?
                  attachment? active-advance cap?
        │
        ▼
Approval workflow starts (steps configured in Procedure Settings,
form "إنشاء طلب سلفة" / createAdvanceRequest, type "طلب سلفة")
        │
   ┌────┴─────────────────────────┐
   │ No steps configured /        │ Steps configured
   │ no resolvable approvers      │ (e.g. direct manager → HR)
   ▼                              ▼
AUTO-APPROVED              Each approver approves/rejects
(status = approved         in turn. Request stays `pending`
 immediately)              until the LAST step approves.
```

### Request statuses

| `status` | `status_label_ar` | Meaning |
|----------|-------------------|---------|
| `pending` | قيد الانتظار | Waiting on approval steps |
| `approved` | مقبول | Fully approved (counts as active until repaid — repayment tracking is out of scope of this API) |
| `rejected` | مرفوض | Rejected — read `rejection_reason` |
| `cancelled` | ملغي | Cancelled by the employee while pending |

### Approver actions

Approver authorization is resolved from the workflow step itself (no permission
gate), so any configured approver can act on their pending step:

- `POST /advance-requests/{id}/approve`
- `POST /advance-requests/{id}/reject` — body: `{ "reason": "..." }` (required, ≤1000 chars);
  it is stored and returned to the requester as `rejection_reason`

Approver actions also surface in the approver's existing procedures inbox — the web
approvals UI keeps working unchanged. `422` if the request is no longer pending.

> **Auto-approve**: if the company hasn't configured approval steps, requests are
> approved instantly. Don't assume there's always a pending approver — render from
> `status`, not from the presence of a workflow.

---

## 7. Quick integration checklist

1. **Settings → السلف**: table from `GET /advance-policies` + create/edit dialog
   (5 fields, no types) → `POST/PUT/DELETE /advance-policies`.
2. **User Profile → السلف section** (gated by `advance-policy.assign`):
   - On mount: `GET /users/{userId}/advance-policies` → policies table with
     `active_count` / `remaining_slots` / `can_request`.
   - Edit mode: `GET /advance-policies` → multi-select pre-checked from the user's
     list → save with `PUT /users/{userId}/advance-policies` (**full id list**,
     sync semantics — an empty array unassigns everything).
   - Requests tab: `GET /advance-requests?user_id={userId}` with status filter.
3. **Sidebar → طلبات السلف**: `GET /advance-requests` + status/user/policy filters,
   detail drawer from `GET /advance-requests/{id}`.
4. **Employee (mobile)**: `GET /my/advance-policies` → form → `POST /advance-requests`
   → `GET /advance-requests/my` (status chips using `status_label_ar`) →
   `POST /advance-requests/{id}/cancel` while pending.
5. Remember: availability is **live** — no year selector, no "reset" button; a slot
   frees as soon as a request is rejected or cancelled.
