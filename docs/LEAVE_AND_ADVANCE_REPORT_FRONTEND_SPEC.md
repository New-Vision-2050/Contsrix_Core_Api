# Leave & Advance Reports — Frontend Spec

Two report screens, built the same way as the employee-tasks report
(`GET /admin/employee-tasks/report`):

1. **تقرير طلبات الإجازة** — every leave request in the company
2. **تقرير طلبات السلف** — every advance request in the company

Each screen is a **paginated table**. Clicking a row opens a **detail panel**
that shows the request and the **full approval workflow**: every step, who was
allowed to act, and who actually approved or rejected it (and when).

These are company-wide reports. They are **not** the approver inbox
(`admin/leave-requests/inbox`) — that one only shows requests waiting on the
logged-in user.

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

Labels (`status_label`, `action_label`, `form_label`, …) follow `Accept-Language`.

Envelope: `{ "code", "message", "payload" }`.

Paginated lists add a sibling `pagination` with the **same keys as the
employee-tasks report**:

```json
{ "page": 3, "next_page": 4, "last_page": 8, "result_count": 112 }
```

### Permissions

| Screen | List | Detail |
|--------|------|--------|
| Leave report | `human-resources.leave-requests*leave-request.list` | `human-resources.leave-requests*leave-request.view` |
| Advance report | `human-resources.advance-requests*advance-request.list` | `human-resources.advance-requests*advance-request.view` |

`403` / `"غير مصرح به"` means the key is missing from the user's role. Hide the
menu item when the list permission is absent.

---

## 1. Leave report

### Table — `GET /admin/leave-requests/report`

```
GET /admin/leave-requests/report?page=1&per_page=15
```

| Query | Notes |
|-------|-------|
| `page` | default 1 |
| `per_page` | default 15, max 100 |
| `user_id` | uuid — one employee |
| `status` | `pending` \| `approved` \| `rejected` \| `cancelled` |
| `leave_policy_id` | uuid |
| `date_from` / `date_to` | `Y-m-d`, filters `created_at` |
| `search` | matches employee name or reason |

Row:

```json
{
  "id": "uuid",
  "employee": { "id": "uuid", "name": "أحمد محمد", "phone": "+9665..." },
  "leave_policy": { "id": "uuid", "name": "Annual Year" },
  "leave_type": { "id": "uuid", "name": "Annual Leave" },
  "start_date": "2026-11-02",
  "end_date": "2026-11-05",
  "total_days": 4,
  "is_emergency": false,
  "reason": "إجازة سنوية",
  "status": "pending",
  "status_label": "قيد الانتظار",
  "workflow": {
    "auto_approved": false,
    "process_status": "in_progress",
    "process_status_label": "قيد التنفيذ",
    "current_step": {
      "name": "اعتماد المدير المباشر",
      "order": 1,
      "action_takers": [{ "id": "uuid", "name": "محمد المدير" }]
    }
  },
  "created_at": "2026-10-13 10:30:00"
}
```

**Suggested columns:** employee · leave type / policy · dates · total days ·
emergency badge · status · current step (`workflow.current_step.name`, or
"اعتماد تلقائي" when `workflow.auto_approved`) · created at.

`workflow.current_step` is `null` when nobody is waiting (already finished, or
auto-approved).

### Detail — `GET /admin/leave-requests/report/{id}`

Same object as the row, plus:

| Field | Use |
|-------|-----|
| `notes`, `attachments[]` | extra request data |
| `rejection_reason` | show when `status = rejected` |
| `requested_by`, `approved_by` + `approved_at`, `rejected_by` + `rejected_at` | final actors (`{ id, name, phone }` or `null`) |
| `processes[]` | the workflow, one entry per procedure, each with `steps[]` |
| `timeline[]` | only the steps someone already acted on, oldest first — render this as the "سجل الإجراءات" list |

`processes[]` item:

```json
{
  "id": "uuid",
  "form": "createLeaveRequest",
  "form_label": "إنشاء طلب إجازة",
  "status": "in_progress",
  "status_label": "قيد التنفيذ",
  "steps": [
    {
      "id": "uuid",
      "name": "اعتماد المدير المباشر",
      "order": 1,
      "is_approve": true,
      "status": "approved",
      "status_label": "تمت الموافقة",
      "action": "approve",
      "action_label": "وافق",
      "assigned_to": { "id": "uuid", "name": "محمد المدير", "phone": null },
      "authorized_users": [{ "id": "uuid", "name": "محمد المدير" }],
      "action_by": { "id": "uuid", "name": "محمد المدير", "phone": "+9665..." },
      "acted_at": "2026-10-13 14:02:00"
    },
    {
      "id": "uuid",
      "name": "اعتماد الموارد البشرية",
      "order": 2,
      "is_approve": true,
      "status": "pending",
      "status_label": "بانتظار الإجراء",
      "action": null,
      "action_label": null,
      "assigned_to": { "id": "uuid", "name": "سارة HR", "phone": null },
      "authorized_users": [{ "id": "uuid", "name": "سارة HR" }],
      "action_by": null,
      "acted_at": null
    }
  ]
}
```

`timeline[]` item (derived from steps that have `action_by`):

```json
{
  "process_id": "uuid",
  "step_name": "اعتماد المدير المباشر",
  "step_order": 1,
  "action": "approve",
  "action_label": "وافق",
  "action_by": { "id": "uuid", "name": "محمد المدير", "phone": "+9665..." },
  "acted_at": "2026-10-13 14:02:00"
}
```

---

## 2. Advance report

### Table — `GET /admin/advance-requests/report`

```
GET /admin/advance-requests/report?page=1&per_page=15
```

Same query params as leave, except the policy filter is `advance_policy_id`.

Row:

```json
{
  "id": "uuid",
  "employee": { "id": "uuid", "name": "أحمد محمد", "phone": "+9665..." },
  "advance_policy": { "id": "uuid", "name": "سلفة طارئة" },
  "amount": 2000.0,
  "installments": 4,
  "reason": "ظروف عائلية",
  "status": "approved",
  "status_label": "مقبول",
  "workflow": {
    "auto_approved": false,
    "process_status": "completed",
    "process_status_label": "مكتمل",
    "current_step": null
  },
  "created_at": "2026-10-13 09:15:00"
}
```

**Suggested columns:** employee · policy · amount · installments · status ·
current step · created at.

### Detail — `GET /admin/advance-requests/report/{id}`

Same extra fields as the leave detail (`notes`, `attachments`,
`rejection_reason`, `requested_by` / `approved_by` / `rejected_by`,
`processes[]`, `timeline[]`).

Step and timeline shapes are **identical** to leave. `form_label` for the
advance workflow is `"إنشاء طلب سلفة"` (`form = createAdvanceRequest`).

---

## 3. How to render the workflow

Draw `processes[].steps` in `order` as a vertical stepper:

| `step.status` | Visual |
|---------------|--------|
| `approved` | done — "{action_by.name} {action_label} · {acted_at}" |
| `rejected` | failed — same line, plus the request `rejection_reason` |
| `pending` | current — "بانتظار: {authorized_users names}" |

Under the stepper, render `timeline` as a simple activity log (already sorted).

### Status reference

Request `status`: `pending` قيد الانتظار · `approved` مقبول · `rejected` مرفوض · `cancelled` ملغي.

Process `status`: `pending` · `in_progress` قيد التنفيذ · `completed` مكتمل · `failed` مرفوض.

Step `action`: `approve` وافق · `reject` رفض · `null` while the step is still waiting.

### Auto-approve

If the company has no approval steps configured, the request is approved
immediately: `processes` is `[]`, `timeline` is `[]`, and
`workflow.auto_approved` is `true`. Show "تم الاعتماد تلقائياً — لا توجد خطوات"
instead of an empty stepper. This is normal, not an error.

### Cancelled by the employee

`status = cancelled`. Open steps were closed without an approver, so a step may
show `status = rejected` with `action_by` equal to the employee. Prefer the
request status chip ("ملغي") over the step label in that case.

---

## 4. Checklist

1. Menu item **تقرير الإجازات** gated by `leave-request.list`; **تقرير السلف** gated by `advance-request.list`.
2. Table from `GET /admin/{leave|advance}-requests/report?page=&per_page=15` with status / employee / date / search filters.
3. Paginate with `page`, `last_page`, `result_count`.
4. Row click → `GET /admin/{leave|advance}-requests/report/{id}`.
5. Detail header from the request fields; stepper from `processes[].steps`; activity log from `timeline`.
6. When `workflow.auto_approved` is true, show the auto-approve empty state.
7. Send `Accept-Language: ar` so every `*_label` comes back in Arabic.
