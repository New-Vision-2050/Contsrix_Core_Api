# Leave Requests — Mobile API Spec

API contract for the **employee leave-request flow**: assigned policies with live
balances, creating requests, tracking status, and the approver actions.

Pairs with `docs/LEAVE_FORMS_API_SPEC.md` (leave type / policy admin forms) and the
`PROCEDURE_WORKFLOW_DEEP_GUIDE.md` (approval workflow internals, §40.8).

## Base URL & Headers

```
Base URL: https://core-be-production.constrix-nv.com/api/v1
```

Every request must send:

| Header | Value |
|--------|-------|
| `Authorization` | `Bearer <auth_token>` |
| `X-Tenant` | `<tenant UUID>` |
| `Accept` | `application/json` |
| `Accept-Language` | `en` or `ar` |
| `Content-Type` | `application/json` (POST/PUT) |

> All booleans are sent/returned as `true`/`false`; API responses render boolean fields as `0`/`1`.

### Response envelope

All endpoints below return the shared envelope:

```json
{ "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT", "message": "...", "payload": { } }
```

- Single item → `code = SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT`, the object is in `payload`.
- Lists → `code = SUCCESS_WITH_LIST_PAYLOAD_OBJECTS`, rows in `payload` (array).
- Paginated lists add a sibling `pagination` object: `{ "page", "next_page", "last_page", "result_count" }`.
- Business-rule failures return `422` with `{ "message": "..." }` — display it to the user. Field validation returns `422` with `{ "message", "errors": { field: [...] } }`.

Postman collection: `LeaveRequest_Mobile_API.postman_collection.json` (repo root) —
the full mobile flow with saved example responses.

---

## Concepts

- Every employee is assigned one or more **leave policies** (a policy = leave type +
  entitlement rules). A 21-day **"Annual Year"** policy is the company default and is
  assigned automatically to every user.
- **Balances renew every calendar year automatically.** `entitled_days` /
  `used_days` / `pending_days` / `available_days` are computed live for a given year
  (query param `year`, default = current year). When January starts, the new year's
  entitlement is available with **no reset job and no manual action**.
- Creating a request starts an **approval workflow** (procedure settings). If the
  company has no approval steps configured, the request is **auto-approved** instantly.
- Once **approved**, the leave days show as **"اجازه"** in the calendar, the
  attendance/today API (no clock-in periods), and attendance reports (counted as used leave).

### Request statuses

| `status` | `status_label_ar` | Meaning |
|----------|-------------------|---------|
| `pending` | قيد الانتظار | Waiting on approval steps |
| `approved` | مقبول | Fully approved — days blocked as إجازة |
| `rejected` | مرفوض | Rejected (see `rejection_reason`) |
| `cancelled` | ملغي | Cancelled by the employee while pending |

---

## 1. My Leave Policies (balances)

### `GET /my/leave-policies`

The authenticated employee's assigned policies with live balances. Feeds the
leave-request form's policy dropdown — **only these policies can be requested**.

Query params: `year` (optional, integer 2000–2100, default current year).

Response:

```json
{
  "code": "SUCCESS_WITH_LIST_PAYLOAD_OBJECTS",
  "message": "Your leave policies retrieved successfully",
  "payload": [
    {
      "leave_policy_id": "uuid",
      "name": "Annual Year",
      "is_default": 1,
      "assigned_at": "2026-01-05 09:00:00",
      "leave_type_id": "uuid",
      "leave_type_name": "Annual Leave",
      "requires_attachment": 0,
      "is_deduct_from_balance": 1,
      "day_calculation_type": "work_day",
      "entitlement_type": "upfront",
      "min_notice_days": 0,
      "allow_exceed_balance": 0,
      "year": 2026,
      "entitled_days": 21,
      "used_days": 5,
      "pending_days": 2,
      "available_days": 14
    }
  ]
}
```

| Field | Notes |
|-------|-------|
| `entitled_days` | Yearly entitlement. `upfront` = full `total_days`; `monthly` = accrued so far this year, capped by `max_balance_days` |
| `used_days` | Approved request days in `year` |
| `pending_days` | Pending request days in `year` (reserved, not yet used) |
| `available_days` | `entitled − used − pending` (floor 0). Enforced on create unless `allow_exceed_balance = 1` |
| `day_calculation_type` | `work_day` (only the employee's scheduled work days count) or `calendar` (every day counts) |
| `min_notice_days` | Request `start_date` must be at least this many days ahead of today |
| `requires_attachment` | If `1`, `attachments` is mandatory on create |

---

## 2. Create Leave Request

### `POST /leave-requests`

Body:

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `leave_policy_id` | uuid | **yes** | must be one of the employee's assigned policies |
| `start_date` | `Y-m-d` | **yes** | |
| `end_date` | `Y-m-d` | **yes** | `>= start_date` |
| `reason` | string | **yes** | max 1000 chars |
| `attachments` | string[] | conditional | required when the policy's leave type has `requires_attachment = 1`; each item max 1024 chars |
| `is_emergency` | boolean | no | default `false` |
| `notes` | string | no | max 1000 chars |

```json
{
  "leave_policy_id": "uuid",
  "start_date": "2026-11-02",
  "end_date": "2026-11-05",
  "reason": "Family trip",
  "attachments": [],
  "is_emergency": false,
  "notes": null
}
```

Response `200` — the created request in `payload` (see §3 for the object shape). `total_days` is
computed server-side from the policy's `day_calculation_type`. The workflow starts
immediately: `workflow.in_progress = true` means it is waiting on approvers;
`status = approved` right away means no workflow is configured (auto-approved).

**Validation errors (`422`)** — surface `message` to the user:

| Case | Message |
|------|---------|
| Policy not assigned to the employee | `This leave policy is not assigned to you.` |
| `end_date` before `start_date` | `end_date must be on or after start_date.` |
| Missing required attachment | `This leave type requires an attachment.` |
| Too short notice | `This leave policy requires at least N day(s) notice before the start date.` |
| Overlaps an existing pending/approved request | `You already have a leave request overlapping the selected dates.` |
| Range has no countable days (all weekends/holidays under `work_day`) | `The selected range contains no countable leave days.` |
| Not enough balance (and policy disallows exceeding) | `Insufficient leave balance: requested X day(s), available Y day(s) in YYYY.` |

---

## 3. Leave Request Object Shape

The object inside `payload`, returned by create / show / lists / approve / reject / cancel:

```json
{
  "id": "uuid",
  "user_id": "uuid",
  "user_name": "Ahmed Ali",
  "leave_policy_id": "uuid",
  "leave_policy_name": "Annual Year",
  "leave_type_id": "uuid",
  "leave_type_name": "Annual Leave",
  "start_date": "2026-11-02",
  "end_date": "2026-11-05",
  "total_days": 4,
  "reason": "Family trip",
  "status": "pending",
  "status_label_ar": "قيد الانتظار",
  "is_emergency": 0,
  "attachments": [],
  "notes": null,
  "rejection_reason": null,
  "approved_at": null,
  "rejected_at": null,
  "workflow": {
    "in_progress": true,
    "pending_step_order": 1
  },
  "created_at": "2026-10-12 15:00:00"
}
```

---

## 4. My Requests

### `GET /leave-requests/my`

Paginated list of the authenticated employee's own requests, newest first.

Query params: `page`, `per_page` (max 100), `status` (`pending|approved|rejected|cancelled`),
`leave_policy_id`, `year` (requests overlapping that year).

```json
{
  "code": "SUCCESS_WITH_LIST_PAYLOAD_OBJECTS",
  "message": "Your leave requests retrieved successfully",
  "pagination": { "page": 1, "next_page": 1, "last_page": 1, "result_count": 3 },
  "payload": [ { "...": "leave request object" } ]
}
```

### `GET /leave-requests/{id}`

Single request. (Requires the `leave_request.view` permission for non-owners —
employees should use `/leave-requests/my` and keep the id from the list.)

---

## 5. Cancel My Request

### `POST /leave-requests/{id}/cancel`

Owner only, and only while `status = pending`. Fails any open workflow steps so no
stale approvals remain.

Errors: `422` if not pending · `403` if not the owner.

---

## 6. Approver Actions (manager / HR)

The approver's pending leave steps also appear in their **procedures inbox**
(the same workflow engine). These endpoints are a shortcut acting directly on the
step assigned to the caller — **no special permission**; authorization comes from the
workflow step configuration (multi-step chains supported: the request stays
`pending` until the last step approves).

### `POST /leave-requests/{id}/approve`

Approves the caller's current pending step. Returns the updated request — check
`status` / `workflow.in_progress` to know if more steps remain.

### `POST /leave-requests/{id}/reject`

```json
{ "reason": "Peak season, please reschedule" }
```

`reason` is required (max 1000). Rejects the request immediately (workflow fails);
`rejection_reason` is stored on the request.

Errors for both: `422` if the request is not pending, or if the caller has no
authorized pending step on it.

---

## 7. Admin: All Requests

### `GET /leave-requests`

Paginated list of all company requests, newest first.
Permission: `leave_request.list`.

Query params: `page`, `per_page`, `status`, `leave_policy_id`, `user_id`,
`start_date` / `end_date` (range overlap filter).

Same response envelope as §4.

---

## 8. Admin: User Policy Assignment

### `GET /users/{userId}/leave-policies?year=`

A user's assigned policies with live balances (same row shape as §1).
Permission: `leave_policy.assign`.

### `PUT /users/{userId}/leave-policies`

**Sync-style**: the sent set *replaces* the user's assignments. Send the full list of
policy ids the user should have (empty array removes all).

```json
{ "leave_policy_ids": ["uuid-1", "uuid-2"] }
```

Response: the user's updated policy rows with balances (same as §1).

---

## UX Notes for the Mobile Form

1. Load `GET /my/leave-policies` → populate the policy dropdown. Show
   `available_days` next to each option and `status_label_ar` for Arabic UI.
2. After picking a policy, read its flags client-side for instant feedback:
   `min_notice_days` (disable too-early dates in the picker), `requires_attachment`
   (show the upload field), `day_calculation_type` (hint how days are counted).
3. `total_days` is always computed server-side — display it from the create response.
4. On `approved`, the days automatically appear as **اجازه** in the attendance
   calendar and today screen; nothing extra to call.
5. At the start of a new year, balances refresh automatically — re-fetch
   `GET /my/leave-policies?year=<newYear>`; no migration/reset endpoint exists.
