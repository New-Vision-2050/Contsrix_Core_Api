# Leave Workflow + User Leave Assignment — Frontend Spec

For the **web frontend team**. This doc covers:

1. The **leave approval workflow** we just shipped (what happens behind a leave request).
2. **Get leave balance** for a user (with yearly renewal).
3. **Assign leave policies to a user** — this UI goes inside the **User Profile** page.

Pairs with `docs/LEAVE_FORMS_API_SPEC.md` (leave type / policy settings forms) and
`docs/LEAVE_REQUEST_API_SPEC.md` (mobile employee flow).

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
> object on paginated endpoints.

---

## 1. The Leave Workflow (what we built)

Every employee is assigned one or more **leave policies**. When an employee submits a
leave request (from mobile), it goes through the **procedure approval workflow**:

```
Employee creates request (mobile)
        │
        ▼
Server validates: policy assigned? attachment? min notice? overlap? balance?
        │
        ▼
Approval workflow starts (steps configured in Procedure Settings,
form "إنشاء طلب إجازة" / createLeaveRequest)
        │
   ┌────┴─────────────────────────┐
   │ No steps configured /        │ Steps configured
   │ no resolvable approvers      │ (e.g. direct manager → HR)
   ▼                              ▼
AUTO-APPROVED              Each approver approves/rejects
(status = approved         in turn. Request stays `pending`
 immediately)              until the LAST step approves.
        │                              │
        ▼                              ▼
                 status = approved
        │
        ▼
Days appear as "اجازه" in: attendance calendar, today/constraint API
(no clock-in periods), attendance history, and reports (counted as used leave).
```

### Request statuses

| `status` | `status_label_ar` | Meaning |
|----------|-------------------|---------|
| `pending` | قيد الانتظار | Waiting on approval steps |
| `approved` | مقبول | Fully approved — days blocked as إجازة |
| `rejected` | مرفوض | Rejected (see `rejection_reason` on the request) |
| `cancelled` | ملغي | Cancelled by the employee while pending |

### Key behaviors to know

- **Auto-approve**: if the company hasn't configured approval steps, requests are
  approved instantly. Don't assume there's always a pending approver.
- **Approver actions** also surface in the approver's existing procedures inbox —
  the web approvals UI keeps working unchanged.
- **Balances renew every calendar year automatically.** All balance numbers are
  computed live for a given year; when January starts, the new year's entitlement is
  available with no reset job. Use the `?year=` query param to view past/future years.

---

## 2. User Profile — "Leave Policies" section (new)

Add a new section/tab in the **User Profile** page (e.g. **"الإجازات" / Leave**).
It has two parts: a **balances table** (read-only) and an **assign action** (edit).

> Required permission to view/assign another user's policies:
> `human-resources.settings*leave-policy.assign`. Hide the section for users
> without it.

### 2.1 Get a user's policies + balances

### `GET /users/{userId}/leave-policies?year=`

Query params: `year` (optional, integer 2000–2100 — default current year).

Response:

```json
{
  "code": "SUCCESS_WITH_LIST_PAYLOAD_OBJECTS",
  "message": "User leave policies retrieved successfully",
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

**Suggested table columns:** Policy name · Leave type · Year · Entitled · Used ·
Pending · **Available** (highlight this one) · Default badge (`is_default`).

| Field | Notes |
|-------|-------|
| `entitled_days` | Yearly entitlement. `upfront` = full `total_days`; `monthly` = accrued so far this year (capped by `max_balance_days`) |
| `used_days` | Approved request days in `year` |
| `pending_days` | Pending request days in `year` (reserved, not yet used) |
| `available_days` | `entitled − used − pending` (floor 0) |
| `is_default` | `1` = the company default policy (21-day "Annual Year") assigned automatically to every user |
| `day_calculation_type` | `work_day` = only scheduled work days count; `calendar` = every day counts |

Add a **year selector** that re-calls the endpoint with `?year=` — balances are
per-year by design.

### 2.2 Assign policies to a user (edit)

### `PUT /users/{userId}/leave-policies`

**Sync semantics — important:** the sent set **replaces** the user's assignments.
Send the full list of policy ids the user should end up with. An empty array removes
all assignments.

```json
{ "leave_policy_ids": ["uuid-1", "uuid-2"] }
```

Response: the user's updated rows with balances (same shape as 2.1) — re-render the
table directly from it.

**Building the picker:** fetch all company policies from `GET /leave-policies`
(paginated — see `docs/LEAVE_FORMS_API_SPEC.md` §2) and pre-check the ones already in
the user's list from 2.1. On save, send the checked ids.

Validation (`422`): each id must be a uuid of an existing policy in the current company.

### 2.3 (Optional) The user's leave requests

### `GET /leave-requests?user_id={userId}&status=&year=&page=&per_page=`

Admin list filtered to this user (permission `human-resources.leave-requests*leave-request.list`).
Handy as a second tab in the profile section: "طلبات الإجازة". Response is paginated
rows of the leave-request object — full shape in `docs/LEAVE_REQUEST_API_SPEC.md` §3
(includes `status_label_ar`, `workflow.in_progress`, `pending_step_order`).

---

## 3. Quick integration checklist

1. Add **"الإجازات"** section to User Profile, gated by `leave-policy.assign` permission.
2. On mount: `GET /users/{userId}/leave-policies` → render balances table + year selector.
3. Edit mode: `GET /leave-policies` → multi-select pre-checked from step 2 →
   save with `PUT /users/{userId}/leave-policies` (full id list, sync semantics).
4. (Optional) Requests tab: `GET /leave-requests?user_id={userId}` with status/year filters.
5. Remember: balances are **live per year** — no "reset balance" button exists or is needed;
   the new year's entitlement appears automatically.

---

## 4. Dedicated Leave Approver Inbox (بانتظار موافقتي)

A mirror of `GET /admin/employee-tasks/inbox` scoped to leave requests only.
It lists **pending requests whose current workflow step authorizes the caller** —
"leave requests waiting on MY approval". Same criteria as the unified admin inbox,
so these routes also carry **no permission middleware**.

| Endpoint | Purpose |
|----------|---------|
| `GET /admin/leave-requests/inbox?page=1&per_page=15&date_from=&date_to=` | Paginated pending-for-me list |
| `GET /admin/leave-requests/inbox-counts` | Badge counts: `{ "pending_leave_requests": n, "total": n }` |
| `PATCH /admin/leave-requests/{id}/approve` | Approve my pending step |
| `PATCH /admin/leave-requests/{id}/reject` | Reject — body `{ "rejection_reason": "..." }` (required, ≤1000) |

Inbox rows use the **same unified shape** as `admin/employee-tasks/inbox`
(`type: "leave_request"`, `category: "leave"`, `employee`, `status`,
`current_step: { id, name, step_order, is_approve, action_takers[] }`,
`summary: { leave_policy, leave_type, start_date, end_date, total_days, is_emergency, reason }`,
`created_at`). Pagination keys: `{ current_page, last_page, per_page, total }`.

> The unified `admin/employee-tasks/inbox` also includes leave requests
> (`type = leave_request`) and its `inbox-counts` payload has
> `pending_leave_requests` — use whichever view fits the screen.
