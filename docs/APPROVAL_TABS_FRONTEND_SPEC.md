# Approval Tabs — Leave & Advance Requests (Frontend Spec)

Build a **"طلبات الاعتماد" (Approval Requests)** screen with **two tabs**:

1. **طلبات الإجازة** — leave requests waiting on the logged-in user's approval
2. **طلبات السلف** — advance requests waiting on the logged-in user's approval

Each tab is powered by its own dedicated approver-inbox API (same criteria as the
existing `admin/employee-tasks/inbox`): only **pending** requests whose **current
workflow step authorizes the caller** appear. If the user is not an approver in any
workflow, both tabs are simply empty.

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
| `Content-Type` | `application/json` (PATCH bodies) |

> No special permission is required for any of these endpoints — authorization
> comes from the workflow step itself (same as the employee-tasks inbox).
>
> Envelope: `{ "code", "message", "payload" }`. Lists add a sibling
> `pagination` object with keys **`current_page`, `last_page`, `per_page`, `total`**
> (note: different keys from other list endpoints — matches the employee-tasks inbox).

---

## 1. Screen Layout

```
┌──────────────────────────────────────────────────────┐
│  طلبات الاعتماد                                       │
│  ┌────────────────────┐ ┌────────────────────┐       │
│  │ طلبات الإجازة  (3) │ │ طلبات السلف    (1) │  ← tabs with count badges
│  └────────────────────┘ └────────────────────┘       │
│  [ date filter: from ____ to ____ ]                  │
│  ┌──────────────────────────────────────────────┐    │
│  │ request card / row …            [✓ Approve] [✗ Reject] │
│  └──────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────┘
```

- **Badges**: call both `inbox-counts` endpoints on screen mount (and after every
  action) to fill the tab counters.
- **Tabs are independent**: each tab fetches its own inbox with its own pagination
  and filters.

### Counts

```
GET /admin/leave-requests/inbox-counts    → payload: { "pending_leave_requests": 3, "total": 3 }
GET /admin/advance-requests/inbox-counts  → payload: { "pending_advance_requests": 1, "total": 1 }
```

Both accept optional `date_from` / `date_to` (`Y-m-d`, applied to `created_at`) if
you want the badge to reflect the active filter.

---

## 2. Tab 1 — طلبات الإجازة (Leave)

### `GET /admin/leave-requests/inbox?page=1&per_page=15&date_from=&date_to=`

Query params: `page` (default 1), `per_page` (default 15, max 100),
`date_from` / `date_to` (optional, filter on request creation date).

Response — rows in the **unified inbox shape** (identical structure to
`admin/employee-tasks/inbox` items):

```json
{
  "code": "SUCCESS_WITH_LIST_PAYLOAD_OBJECTS",
  "message": "Leave requests inbox retrieved successfully",
  "pagination": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 3 },
  "payload": [
    {
      "id": "aa11bb22-cc33-4dd4-9e5f-555555555555",
      "type": "leave_request",
      "type_label": "طلب إجازة",
      "category": "leave",
      "category_label": "إجازة",
      "task": null,
      "employee": { "id": "bb22cc33-dd44-4ee5-8f6a-666666666666", "name": "أحمد محمد" },
      "status": "pending",
      "current_step": {
        "id": 12,
        "name": "اعتماد المدير المباشر",
        "step_order": 1,
        "is_approve": true,
        "action_takers": [
          { "user_id": "cc33dd44-ee55-4ff6-8a7b-777777777777", "name": "محمد المدير" }
        ]
      },
      "duration_hours": null,
      "summary": {
        "leave_policy": "Annual Year",
        "leave_type": "Annual Leave",
        "start_date": "2026-11-02",
        "end_date": "2026-11-05",
        "total_days": 4,
        "is_emergency": false,
        "reason": "إجازة سنوية - سفر عائلي"
      },
      "created_at": "2026-10-13 10:30:00"
    }
  ]
}
```

**Suggested columns / card fields:** employee name · leave type + policy ·
`start_date` → `end_date` · `total_days` · emergency badge (`summary.is_emergency`) ·
reason (truncated) · current step name · created_at.

---

## 3. Tab 2 — طلبات السلف (Advance)

### `GET /admin/advance-requests/inbox?page=1&per_page=15&date_from=&date_to=`

Same query params and envelope. Row shape:

```json
{
  "id": "dd44ee55-ff66-4aa7-9b8c-888888888888",
  "type": "advance_request",
  "type_label": "طلب سلفة",
  "category": "advance",
  "category_label": "سلفة",
  "task": null,
  "employee": { "id": "bb22cc33-dd44-4ee5-8f6a-666666666666", "name": "أحمد محمد" },
  "status": "pending",
  "current_step": {
    "id": 7,
    "name": "اعتماد الموارد البشرية",
    "step_order": 2,
    "is_approve": true,
    "action_takers": [
      { "user_id": "cc33dd44-ee55-4ff6-8a7b-777777777777", "name": "سارة HR" }
    ]
  },
  "duration_hours": null,
  "summary": {
    "advance_policy": "سلفة طارئة",
    "amount": 2000.0,
    "installments": 4,
    "reason": "ظروف عائلية طارئة"
  },
  "created_at": "2026-10-13 09:15:00"
}
```

**Suggested columns / card fields:** employee name · policy · **amount** (formatted
with currency) · **installments** (e.g. "4 أقساط" → monthly installment =
`amount / installments` if you want to show it) · reason · current step name · created_at.

---

## 4. Actions (both tabs)

### Approve

```
PATCH /admin/leave-requests/{id}/approve
PATCH /admin/advance-requests/{id}/approve
```

No body required. Show a simple confirm dialog ("تأكيد الموافقة؟").

### Reject

```
PATCH /admin/leave-requests/{id}/reject       body: { "rejection_reason": "..." }
PATCH /admin/advance-requests/{id}/reject     body: { "rejection_reason": "..." }
```

`rejection_reason` is **required** (string, ≤1000). Open a modal with a textarea —
do not allow submitting empty. The reason is stored on the request and shown to the
employee.

### Action responses

Both return `200` with the **updated row in the same inbox shape** in `payload`,
e.g. message `"Leave request approved successfully"`.

**Important — multi-step workflows:** approving one step may not finish the request.
Check the returned row:

- `status = "approved"` → fully approved, remove the row from the list.
- `status = "pending"` and `current_step.id` changed → moved to the next approver;
  it no longer concerns the caller → remove the row from the list.
- Rejections always end the workflow (`status = "rejected"`) → remove the row.

**Simplest correct behavior: after any successful approve/reject, remove the row and
re-fetch that tab's `inbox` + both `inbox-counts`.**

### Action errors (`422` / `404`) — display `message` verbatim

| Case | Message |
|------|---------|
| Request no longer pending | `Only pending leave requests can be approved.` (or `...advance...` / `...rejected.`) |
| Caller has no pending step on it | `No pending workflow step assigned to you for this leave request.` (or `...advance request.`) |
| Missing reject reason | validation envelope: `{ "message", "errors": { "rejection_reason": ["The rejection reason field is required."] } }` |
| Unknown id | `404` |

These happen mostly when two approvers act on the same request simultaneously or the
employee cancels while the screen is open — on `422`, just refetch the tab.

---

## 5. Behaviors & Edge Cases

- **Who sees what:** a request appears in a tab ONLY while the caller is an
  authorized action-taker of its current step. When they approve, it moves to the
  next approver's inbox (or disappears if it was the last step).
- **Auto-approve:** if the company has NOT configured approval steps for the form
  (إنشاء طلب إجازة / إنشاء طلب سلفة in Procedure Settings), requests are approved
  instantly and **never appear in any inbox**. Empty tabs are normal in that case.
- **Cancellation:** if the employee cancels while pending, the request vanishes from
  the inbox on next fetch; acting on it returns `422` (see table).
- **`current_step` may be all-null** in the action response after a final approval —
  only the list rows (always pending with an active step) reliably have it filled.
- **`type_label` / `category_label`** are localized by `Accept-Language` (`ar`/`en`) —
  use them directly for chips/badges.

### Optional: combined view

If you later want a single "all requests" tab, `GET /admin/employee-tasks/inbox`
already merges leave + advance (plus task items) in this exact same row shape —
filter client-side by `type` (`leave_request` / `advance_request`) or pass
`?type=leave_request`. Its `inbox-counts` returns all counters including
`pending_leave_requests` and `pending_advance_requests`.

---

## 6. Integration Checklist

1. Screen mount → `GET /admin/leave-requests/inbox-counts` +
   `GET /admin/advance-requests/inbox-counts` → tab badges.
2. Tab activate → `GET /admin/{leave|advance}-requests/inbox?page=1&per_page=15`
   → render rows (columns per §2/§3), paginate with `current_page`/`last_page`.
3. Optional date filter → pass `date_from`/`date_to` to both inbox and counts.
4. Approve → confirm dialog → `PATCH …/{id}/approve`.
5. Reject → modal with required `rejection_reason` → `PATCH …/{id}/reject`.
6. After either action → remove row, refetch tab list + both counts.
7. On `422` → toast the `message`, refetch the tab.
8. Empty tab → friendly empty state ("لا توجد طلبات بانتظار موافقتك").
