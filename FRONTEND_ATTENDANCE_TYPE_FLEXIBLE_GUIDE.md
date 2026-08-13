# Frontend guide: Employee attendance type (`regular` / `flexible`)

**Endpoints**
- `POST /api/v1/user_professional_data`
- `PUT  /api/v1/user_professional_data/{id}`
- `GET  /api/v1/user_professional_data/user/{id}`

**Also returned on**
- `GET /api/v1/attendance/user-constraint/today` (and `getUserConstraints`) → `work_rules.attendance_type`

**Base URL (production)**  
`https://core-be-production.constrix-nv.com/api/v1`

---

## Field

| Field | Type | Values | Default |
|---|---|---|---|
| `attendance_type` | string | `regular`, `flexible` | `regular` |

Alias accepted on write only: `flexable` / `flex` → stored and returned as **`flexible`**.

Existing employees with no value behave as **`regular`**.

---

## POST body (create / upsert professional data)

```json
{
  "user_id": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
  "branch_id": "…",
  "management_id": "…",
  "job_type_id": "…",
  "job_title_id": "…",
  "job_code": "EMP-001",
  "attendance_constraint_id": "…",
  "attendance_type": "flexible"
}
```

Omit `attendance_type` → `regular`.

## PUT body

Same as today, plus optional:

```json
{
  "branch_id": "…",
  "management_id": "…",
  "job_type_id": "…",
  "job_title_id": "…",
  "job_code": "EMP-001",
  "attendance_type": "regular"
}
```

## GET / POST response

```json
{
  "payload": {
    "id": "…",
    "job_code": "EMP-001",
    "attendance_type": "flexible",
    "attendance_constraint": { }
  }
}
```

---

## What each type means

### `regular` (default)

Unchanged. Shift **start / end** matter.

- Clock-in only inside the V2 window (`can_clock_in_from` → `can_clock_out_until`)
- Late if `clock_in_time` > shift start
- Absent if they never clock in before `can_clock_in_before` / period close
- Locations apply
- Auto clock-out at expected hours **+ max overtime** (existing behaviour)

### `flexible`

Shift start / end are **not** used for when they may clock in.

| Rule | Behaviour |
|---|---|
| Clock-in time | Any time during the **calendar day** (branch timezone, 00:00–23:59) |
| Locations | **Still applied** (geofence / branch / additional locations) |
| Working hours | From the assigned constraint (`total_work_hours` / `working_hours`, fallback 9h) |
| Auto clock-out | When **accumulated work time** (all sessions that day, breaks excluded as usual) reaches required working hours |
| Multiple sessions | Allowed — clock out and clock in again until hours are completed |
| After hours complete | If constraint `is_after_finish_working_hours` is **true**, they may clock in again (overtime session, capped by `max_over_time`). If **false**, clock-in is blocked (`working_hours_completed`) |
| Late | **Never** late (no shift start) |
| Absent | Still **غائب** if they **never clock in** that work day |
| Holiday / مطلوب للحضور | Unchanged (manual holiday range + weekly off days still apply) |

---

## Today / clock-in APIs (mobile)

`work_rules` now includes:

```json
{
  "attendance_type": "flexible",
  "flexible_required_work_minutes": 480,
  "all_work_periods": [
    {
      "date": "2026-08-13",
      "start_time": "00:00",
      "end_time": "23:59",
      "can_clock_in_from": "…T00:00:00…",
      "can_clock_out_until": "…T23:59:59…",
      "required_work_minutes": 480,
      "expected_clock_out_at": "…"
    }
  ]
}
```

### UI checklist

- [ ] Professional data form: toggle **Regular / Flexible** (send `attendance_type`).
- [ ] Default selection = Regular.
- [ ] For **flexible**, do **not** tell the user they must wait for shift start.
- [ ] Still show / enforce **location** (same as regular).
- [ ] Show remaining working hours / `expected_clock_out_at` so they know when auto clock-out will happen.
- [ ] After auto clock-out: if overtime is enabled on the constraint, allow another clock-in; otherwise show “working hours completed”.
- [ ] Calendar / history: flexible + clocked in → **حاضر / نشط**, never **متأخر** just because they started late in the day.
- [ ] Calendar / history: flexible + never clocked in on a work day → **غائب**.

---

## Clock-in errors to handle (flexible)

| `type` | Meaning |
|---|---|
| `clock_in_not_allowed` | Holiday / non-work day |
| `working_hours_completed` | Required hours done and overtime not allowed |
| location violations | Same as regular (still blocking) |

Do **not** expect `clock_in_too_early` / `clock_in_deadline_passed` for flexible on a work day (unless it is not a work day).

---

## Notes

- Locations, overtime flags, `max_over_time`, and holiday status still come from the **attendance constraint**.
- Flexible only changes **when** during the day they may start; it does not skip geofence.
- Auto clock-out stores the time working hours were completed (not “now” on the queue).
