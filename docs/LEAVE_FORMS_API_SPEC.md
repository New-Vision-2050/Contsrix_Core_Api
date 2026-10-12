# Leave Management — Frontend Form Spec

API contract for the **Leave Type** and **Leave Policy** forms (new design).

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

---

## 1. Leave Type Form

Endpoints: `/leave-types`

### Form fields

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `name` | string | **yes** | max 255 chars, unique per company |
| `is_payed` | boolean | no | default `false` |
| `is_deduct_from_balance` | boolean | no | default `false`. **Controls Leave Policy form variant** |
| `requires_attachment` | boolean | no | default `false` |

> `conditions` and `branch_ids` are **removed** from the API — do not send them.

### Create — `POST /leave-types`

```json
{
  "name": "Annual Leave",
  "is_payed": true,
  "is_deduct_from_balance": true,
  "requires_attachment": false
}
```

### Update — `PUT /leave-types/{id}`

Same body as create — `name` is required; boolean fields are optional (`sometimes`).

### Response shape

```json
{
  "id": "uuid",
  "name": "Annual Leave",
  "is_payed": 1,
  "is_deduct_from_balance": 1,
  "requires_attachment": 0
}
```

### Other endpoints

- `GET /leave-types?page=1&per_page=10` — paginated list
- `GET /leave-types/{id}` — single item
- `DELETE /leave-types/{id}` — delete

---

## 2. Leave Policy Form

Endpoints: `/leave-policies`

The form has **two variants** driven by the selected Leave Type's `is_deduct_from_balance` flag:

| Variant | Trigger |
|---------|---------|
| **برصيد** (with balance) | `is_deduct_from_balance = true` |
| **بدون رصيد** (without balance) | `is_deduct_from_balance = false` |

**UX flow:** user first picks `leave_type_id` (dropdown from `GET /leave-types`). Read `is_deduct_from_balance` on the selected type, then show the matching field set.

### Common fields (both variants)

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `name` | string | **yes** | max 255 chars, unique per company |
| `leave_type_id` | uuid | **yes** | exists in `leave_types`. **Immutable on update** — send only on create |

### "With balance" fields (`is_deduct_from_balance = true`)

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `max_balance_days` | integer | no | min 0 — max balance cap |
| `total_days` | integer | **yes** | min 0 — annual entitlement days |
| `min_notice_days` | integer | no | min 0 — minimum notice before leave |
| `day_calculation_type` | enum | **yes** | `work_day` or `calendar` |
| `entitlement_type` | enum | **yes** | `upfront` or `monthly` |
| `entitlement_start_from` | enum | no | `hire_date` |
| `promotion_type` | enum | no | `after_duration` |
| `promotion_duration_months` | integer | conditional | required when `promotion_type = after_duration` |
| `is_rollover_allowed` | boolean | no | allow balance rollover |
| `rollover_duration_months` | integer | no | months rollover balance stays valid |
| `allow_exceed_balance` | boolean | no | allow requests exceeding balance |

### "Without balance" fields (`is_deduct_from_balance = false`)

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `max_days` | integer | **yes** | min 0 — maximum leave days |

### Create — `POST /leave-policies`

With balance:

```json
{
  "name": "Standard Annual Leave Policy",
  "leave_type_id": "{{leave_type_id}}",
  "max_balance_days": 40,
  "total_days": 21,
  "min_notice_days": 0,
  "day_calculation_type": "work_day",
  "entitlement_type": "upfront",
  "entitlement_start_from": "hire_date",
  "promotion_type": "after_duration",
  "promotion_duration_months": 24,
  "is_rollover_allowed": true,
  "rollover_duration_months": 4,
  "allow_exceed_balance": false
}
```

Without balance:

```json
{
  "name": "Sick Leave Policy",
  "leave_type_id": "{{leave_type_id}}",
  "max_days": 10
}
```

### Update — `PUT /leave-policies/{id}`

Same fields as create, except `leave_type_id` — it cannot be changed after creation.

### Response shape

```json
{
  "id": "uuid",
  "name": "Standard Annual Leave Policy",
  "leave_type_id": "uuid",
  "leave_type_name": "Annual Leave",
  "is_deduct_from_balance": 1,
  "max_balance_days": 40,
  "total_days": 21,
  "min_notice_days": 0,
  "day_calculation_type": "work_day",
  "entitlement_type": "upfront",
  "entitlement_start_from": "hire_date",
  "promotion_type": "after_duration",
  "promotion_duration_months": 24,
  "is_rollover_allowed": 1,
  "rollover_duration_months": 4,
  "allow_exceed_balance": 0,
  "max_days": null
}
```

### Other endpoints

- `GET /leave-policies?page=1&per_page=10` — paginated list
- `GET /leave-policies/{id}` — single item
- `PUT /leave-policies/{id}/rollover-allowed` — toggle rollover flag. Body: `{ "is_rollover_allowed": true }`
- `POST /leave-policies/export` — export to `xlsx`/`csv`. Body: `{ "format": "xlsx" }`, optional filters: `name`, `total_days`, `day_calculation_type`, `leave_type_id`, `ids[]`
- `DELETE /leave-policies/{id}` — delete

---

## 3. Removed fields (do not send / display)

The following fields existed in the old design and are now gone from the API and DB:

- `day_type` → replaced by `day_calculation_type`
- `upgrade_condition` → replaced by `promotion_type` + `promotion_duration_months`
- `max_days_per_request` → removed
- `is_allow_half_day` → removed (half-day feature deleted; route `PUT /{id}/half-day-allowed` no longer exists)
- Leave Type: `conditions`, `branch_ids`

## 4. Postman collections

- `LeaveType_CRUD_API.postman_collection.json`
- `LeavePolicy_CRUD_API.postman_collection.json`
- `Leave_Management_APIs.postman_collection.json` (combined)

## 5. Validation notes

- Validation errors return standard Laravel `422` with field-level messages (localized via `Accept-Language`).
- Conditional rules run server-side: the API reads `is_deduct_from_balance` from the linked leave type, so **send only the fields matching the variant** — mismatched fields are ignored.
- `promotion_duration_months` is required only when `promotion_type = after_duration`.
- `total_days` is required for balance policies; `max_days` is required for non-balance policies.
