# Mobile API Spec — Leave (الإجازات) & Advance (السلفة) Requests

Complete, self-contained contract for the **mobile employee flows**: leave requests
and salary advance requests. Both features share the same pattern: the employee has
**assigned policies** → submits a **request** → the request goes through an
**approval workflow** → employee tracks/cancels it.

Postman collections (repo root, with saved example responses):
`LeaveRequest_Mobile_API.postman_collection.json`,
`AdvanceRequest_Mobile_API.postman_collection.json`.

---

## Base URL, Headers, Envelope

```
Base URL: https://core-be-production.constrix-nv.com/api/v1
```

| Header | Value |
|--------|-------|
| `Authorization` | `Bearer <auth_token>` |
| `X-Tenant` | `<tenant UUID>` |
| `Accept` | `application/json` |
| `Accept-Language` | `en` or `ar` |
| `Content-Type` | `application/json` (POST/PUT) |

> Booleans are sent as `true`/`false`; responses render booleans as `0`/`1`.

### Response envelope (every endpoint)

```json
{ "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT", "message": "...", "payload": { } }
```

- Single item → `code = SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT`, object in `payload`.
- Lists → `code = SUCCESS_WITH_LIST_PAYLOAD_OBJECTS`, array in `payload`.
- Paginated lists add a sibling `pagination`: `{ "page", "next_page", "last_page", "result_count" }`.
- Business-rule failures → `422` with `{ "message": "..." }` — **display `message` to the user**.
- Field validation failures → `422` with `{ "message", "errors": { "<field>": ["..."] } }`.

### Request statuses (identical for both features)

| `status` | `status_label_ar` | Meaning |
|----------|-------------------|---------|
| `pending` | قيد الانتظار | Waiting on approval steps |
| `approved` | مقبول | Fully approved |
| `rejected` | مرفوض | Rejected — read `rejection_reason` |
| `cancelled` | ملغي | Cancelled by the employee while pending |

### The approval workflow (identical for both features)

- Creating a request starts the company's configured approval chain (e.g. direct
  manager → HR). The request stays `pending` until the **last** step approves.
- **Auto-approve**: if the company has no approval steps configured, the request is
  approved instantly — the create response already has `status = approved`. Never
  assume a pending approver exists; always render from `status`.
- Every request object carries `workflow: { in_progress, pending_step_order }`:
  `in_progress = true` means it is still moving through steps.
- Approvers act from their existing procedures inbox; the mobile app only needs the
  employee side (create / list / cancel).

---

## Key Differences: Leave vs Advance

| | Leave (الإجازة) | Advance (السلفة) |
|---|---|---|
| Policies have **types** | yes (`leave_type_*` fields) | **no types** |
| Limits are | yearly day balances (`entitled/used/pending/available_days`) | per-request caps (`max_amount`, `max_installments`) + concurrent slot cap |
| Time dimension | **per calendar year** — `?year=` param, auto-renews every January | **none** — no year, no reset |
| Availability check | `available_days` > 0 | `can_request = 1` (slot free) |
| Frees up when | new year starts (automatic) | request rejected/cancelled (slot frees immediately); approved counts until repaid |
| Routes | `/leave-requests`, `/my/leave-policies` | `/advance-requests`, `/my/advance-policies` |

---

# PART 1 — LEAVE (الإجازات)

## 1.1 My Leave Policies (dropdown source)

### `GET /my/leave-policies`

The authenticated employee's assigned policies with live balances. **Only these can
be requested.** Query param: `year` (optional, 2000–2100, default current year).

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
| `entitled_days` | Yearly entitlement. `upfront` = full days; `monthly` = accrued so far this year |
| `used_days` | Approved request days in `year` |
| `pending_days` | Pending request days in `year` (reserved) |
| `available_days` | `entitled − used − pending` (floor 0). Enforced on create unless `allow_exceed_balance = 1` |
| `day_calculation_type` | `work_day` (only scheduled work days count) or `calendar` |
| `min_notice_days` | `start_date` must be at least this many days ahead of today |
| `requires_attachment` | If `1`, `attachments` is mandatory on create |

## 1.2 Create Leave Request

### `POST /leave-requests`

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `leave_policy_id` | uuid | **yes** | one of the employee's assigned policies |
| `start_date` | `Y-m-d` | **yes** | |
| `end_date` | `Y-m-d` | **yes** | `>= start_date` |
| `reason` | string | **yes** | max 1000 |
| `attachments` | string[] | conditional | required when `requires_attachment = 1`; each item max 1024 chars |
| `is_emergency` | boolean | no | default `false` |
| `notes` | string | no | max 1000 |

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

`total_days` is computed **server-side** per `day_calculation_type` — display it from
the response.

**Business errors (`422.message`)**:

| Case | Message |
|------|---------|
| Policy not assigned | `This leave policy is not assigned to you.` |
| Missing attachment | `This leave type requires an attachment.` |
| Too short notice | `This leave policy requires at least N day(s) notice before the start date.` |
| Overlap | `You already have a leave request overlapping the selected dates.` |
| No countable days | `The selected range contains no countable leave days.` |
| Balance | `Insufficient leave balance: requested X day(s), available Y day(s) in YYYY.` |

## 1.3 Leave Request Object Shape

Returned by create / show / lists / cancel in `payload`:

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
  "workflow": { "in_progress": true, "pending_step_order": 1 },
  "created_at": "2026-10-12 15:00:00"
}
```

## 1.4 My Leave Requests

### `GET /leave-requests/my`

Paginated, newest first. Query params: `page`, `per_page` (max 100),
`status` (`pending|approved|rejected|cancelled`), `leave_policy_id`, `year`.

### `GET /leave-requests/{id}` — single request details.

## 1.5 Cancel Leave Request

### `POST /leave-requests/{id}/cancel`

Owner only, only while `pending`. `422` if not pending · `403` if not the owner.

## 1.6 Approved side-effect

Once `approved`, the days show as **اجازه** in the attendance calendar / today API
(no clock-in periods) and count as used in reports — nothing extra to call.

---

# PART 2 — ADVANCE (السلفة)

## 2.1 My Advance Policies (dropdown source)

### `GET /my/advance-policies`

The authenticated employee's assigned advance policies with **live availability**
(no year param — availability is computed on the fly).

```json
{
  "code": "SUCCESS_WITH_LIST_PAYLOAD_OBJECTS",
  "message": "Your advance policies retrieved successfully",
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
      "active_count": 0,
      "pending_amount": 0.0,
      "active_amount": 0.0,
      "remaining_slots": 1,
      "can_request": 1
    }
  ]
}
```

| Field | Notes |
|-------|-------|
| `max_amount` | Max amount a single request may ask for |
| `max_installments` | Max repayment installments (1–120) |
| `max_active_advances` | Concurrent (pending + approved) requests allowed under this policy |
| `active_count` | Employee's current pending + approved requests under this policy |
| `pending_amount` / `active_amount` | Sum of pending / approved request amounts |
| `remaining_slots` | `max_active_advances − active_count` (floor 0) |
| `can_request` | `1` = employee may submit a new request under this policy now — **disable the dropdown item when `0`** |
| `requires_attachment` | If `1`, `attachments` is mandatory on create |

## 2.2 Create Advance Request

### `POST /advance-requests`

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `advance_policy_id` | uuid | **yes** | one of the employee's assigned policies |
| `amount` | number | **yes** | `0.01` – policy `max_amount` |
| `installments` | integer | **yes** | `1` – policy `max_installments` |
| `reason` | string | **yes** | max 1000 |
| `attachments` | string[] | conditional | required when `requires_attachment = 1`; each item max 1024 chars |
| `notes` | string | no | max 1000 |

```json
{
  "advance_policy_id": "uuid",
  "amount": 2000,
  "installments": 4,
  "reason": "ظروف عائلية طارئة",
  "attachments": [],
  "notes": null
}
```

**Business errors (`422.message`)**:

| Case | Message |
|------|---------|
| Policy not assigned | `This advance policy is not assigned to you.` |
| Amount over cap | `Amount exceeds the policy maximum of {max_amount}.` |
| Installments over cap | `Installments exceed the policy maximum of {max_installments}.` |
| Missing attachment | `This advance policy requires an attachment.` |
| Slot cap reached | `You already have {n} active advance request(s) under this policy (maximum {max}).` |

## 2.3 Advance Request Object Shape

```json
{
  "id": "uuid",
  "user_id": "uuid",
  "user_name": "أحمد محمد",
  "advance_policy_id": "uuid",
  "advance_policy_name": "سلفة طارئة",
  "amount": 2000.0,
  "installments": 4,
  "reason": "ظروف عائلية طارئة",
  "status": "pending",
  "status_label_ar": "قيد الانتظار",
  "attachments": [],
  "notes": null,
  "rejection_reason": null,
  "approved_at": null,
  "rejected_at": null,
  "workflow": { "in_progress": true, "pending_step_order": 1 },
  "created_at": "2026-10-13 10:30:00"
}
```

## 2.4 My Advance Requests

### `GET /advance-requests/my`

Paginated, newest first. Query params: `page`, `per_page` (max 100),
`status` (`pending|approved|rejected|cancelled`), `advance_policy_id`.

### `GET /advance-requests/{id}` — single request details.

## 2.5 Cancel Advance Request

### `POST /advance-requests/{id}/cancel`

Owner only, only while `pending`. Cancelling **frees the policy slot immediately**
(`remaining_slots` goes back up on the next `GET /my/advance-policies`).
`422` if not pending · `403` if not the owner.

---

# UX Checklist (both flows)

1. **Create screen**: load the `/my/…-policies` endpoint → policy dropdown. Leave:
   show `available_days`. Advance: show `max_amount`, disable items with
   `can_request = 0`.
2. After picking a policy, adjust the form client-side from its flags:
   `requires_attachment = 1` → show upload field; leave `min_notice_days > 0` →
   disable too-early dates; advance → clamp amount/installments inputs to the caps.
3. Status chips: render `status_label_ar` for Arabic UI.
4. Detail screen: show `rejection_reason` when `status = rejected`; show a
   **Cancel** button only when `status = pending` (and the request is mine).
5. After create/cancel, re-fetch the policies endpoint so balances/availability
   refresh (leave balances renew automatically every January; advance slots free
   on reject/cancel).
6. On any `422`, display the returned `message` verbatim — it is the
   business-friendly error.
