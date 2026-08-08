# Frontend guide: Employee attendance status (holiday date range)

**Endpoint**
- `PATCH /api/v1/sub_entities/records/attendance-status`
- List (read): `GET /api/v1/sub_entities/records/list` (includes attendance status fields per employee)

**Base URL (production)**
- `https://core-be-production.constrix-nv.com/api/v1`

**What changed:** setting `holiday` can take a **date range**. After `date_to`, the employee automatically returns to **مطلوب للحضور** (`required_attendance`). No second PATCH is required.

---

## Status values

| `status` | Label (UI) | Meaning |
|---|---|---|
| `holiday` | اجازه | Employee is on holiday for the given range |
| `required_attendance` | مطلوب للحضور | Employee must attend (clears holiday window) |

---

## PATCH body

### Holiday with range (preferred)

```json
{
  "company_user_id": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
  "status": "holiday",
  "date_from": "2026-08-01",
  "date_to": "2026-08-05"
}
```

- Inclusive range: holiday applies on every day from `date_from` through `date_to`.
- Day after `date_to` → list/API treat the employee as `required_attendance` automatically.
- Max range: **366 days**.

### Accepted date aliases

Any of these work (datetimes are normalized to a calendar date):

| Preferred | Aliases |
|---|---|
| `date_from` | `time_from`, `start_date` |
| `date_to` | `time_to`, `end_date` |

Example with datetime aliases:

```json
{
  "company_user_id": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
  "status": "holiday",
  "time_from": "2026-08-01 00:00:00",
  "time_to": "2026-08-05 23:59:59"
}
```

### Holiday without end date (legacy)

```json
{
  "company_user_id": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
  "status": "holiday"
}
```

- Open-ended: stays holiday from today onward until you send `required_attendance`.
- Prefer always sending `date_from` + `date_to` in new UI.

### Clear holiday → مطلوب للحضور

```json
{
  "company_user_id": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
  "status": "required_attendance"
}
```

---

## PATCH response

```json
{
  "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT",
  "message": "Attendance status updated successfully",
  "payload": {
    "attendance_id": "…",
    "attendance_work_date": "2026-08-01",
    "attendance_status_code": "holiday",
    "attendance_status_label": "اجازه",
    "attendance_date_from": "2026-08-01",
    "attendance_date_to": "2026-08-05"
  }
}
```

| Field | When holiday | When required_attendance |
|---|---|---|
| `attendance_status_code` | `holiday` | `required_attendance` |
| `attendance_status_label` | `اجازه` | `مطلوب للحضور` |
| `attendance_date_from` | start of range | `null` |
| `attendance_date_to` | end of range, or `null` if open-ended | `null` |

---

## List response fields

Same fields are merged onto each employee row in records list (use `start_date` query for the day you are viewing):

```
GET /api/v1/sub_entities/records/list?sub_entity_id=…&registration_form_id=…&start_date=2026-08-03
```

| Field | Use |
|---|---|
| `attendance_status_code` | `holiday` \| `required_attendance` for that `start_date` |
| `attendance_status_label` | `اجازه` \| `مطلوب للحضور` |
| `attendance_work_date` | Day being viewed |
| `attendance_date_from` | Holiday window start (only when status is holiday) |
| `attendance_date_to` | Holiday window end, or `null` if open-ended |

### UI behaviour

1. User sets holiday `2026-08-01` → `2026-08-05`.
2. List for `2026-08-01` … `2026-08-05` → **اجازه** (show `date_from` / `date_to` in the row if useful).
3. List for `2026-08-06` (and later) → **مطلوب للحضور** automatically.
4. No need to call PATCH again after the range ends.

---

## Frontend checklist

- [ ] Holiday form: require **from** and **to** dates (send as `date_from` / `date_to`).
- [ ] Validate `date_to >= date_from` before submit (API also validates).
- [ ] Show range on holiday rows via `attendance_date_from` / `attendance_date_to`.
- [ ] Do **not** schedule a follow-up PATCH to clear holiday after `date_to` — backend handles it.
- [ ] “مطلوب للحضور” action still uses `status: "required_attendance"` to clear early.
- [ ] Display labels: `اجازه` / `مطلوب للحضور` (or map from `attendance_status_code`).

---

## Validation errors to handle

| Case | HTTP |
|---|---|
| Invalid `status` (not `holiday` / `required_attendance`) | 422 |
| `date_to` before `date_from` | 422 |
| Range longer than 366 days | 422 |
| `company_user_id` missing / invalid | 422 |
| Tenant user not found for company user | 404 |
