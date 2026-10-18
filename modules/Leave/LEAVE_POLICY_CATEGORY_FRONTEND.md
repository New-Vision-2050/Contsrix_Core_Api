# Leave Policy Category – Frontend Changes

## Summary
- New CRUD: **Leave Policy Categories** (`/api/v1/leave-policy-categories`).
- `LeavePolicy` now has **`leave_category_id`** (required on create, optional on update).
- `LeavePolicy` responses now return `leave_category_id` and `leave_category_name`.

## Required headers (all endpoints)
```
Authorization: Bearer <token>
X-Tenant: <tenant-id>
Accept: application/json
```

## 1. Leave Policy Category API

Base: `/api/v1/leave-policy-categories`

| Method | Path | Permission | Description |
|---|---|---|---|
| GET | `/` | `human-resources.settings*leave-policy-category.list` | Paginated list (`page`, `per_page`) |
| POST | `/` | `...leave-policy-category.create` | Create |
| GET | `/{id}` | `...leave-policy-category.view` or `.update` | Show |
| PUT | `/{id}` | `...leave-policy-category.update` | Update |
| DELETE | `/{id}` | `...leave-policy-category.delete` | Delete |

### Create / Update body
```json
{ "name": "Annual Leaves" }
```
- `name`: required, string, max 255, unique per company.

### Item response
```json
{
  "id": "uuid",
  "name": "Annual Leaves"
}
```

### Deleting a category
Policies linked to the deleted category are **kept**, and their `leave_category_id` becomes `null`.

## 2. Leave Policy API changes

Base: `/api/v1/leave-policies`

### POST `/` (create)
New **required** field:

| Field | Type | Rules |
|---|---|---|
| `leave_category_id` | uuid | required, must exist in leave policy categories of the current company |

Example:
```json
{
  "name": "Annual Policy",
  "leave_type_id": "uuid",
  "leave_category_id": "uuid",
  "max_balance_days": 30,
  "total_days": 21,
  "day_calculation_type": "work_day",
  "entitlement_type": "upfront"
}
```

### PUT `/{id}` (update)
- `leave_category_id` is **optional**. If sent it must be a valid category uuid; if omitted the current value is unchanged.

### Responses (list / show / create / update)
Two new keys:
```json
{
  "leave_category_id": "uuid|null",
  "leave_category_name": "Annual Leaves|null"
}
```
They are `null` for old policies created before this change.

### Export
`POST /api/v1/leave-policies/export` accepts an optional `leave_category_id` filter.

## 2b. Employment contract – leave category (automatic assignment)

`POST /api/v1/employment_contracts` (multipart) accepts optional `leave_category_id` (uuid|null, must exist in the company's categories; error: "The leave policy category is invalid."). Sending it empty clears the category; omitting it leaves it unchanged.

`GET /employment_contracts/user/{userId}` and the POST response now include:
```json
{ "leave_category_id": "uuid|null", "leave_category": { "id": "uuid", "name": "Annual Leaves" } }
```
(`leave_category` is `null` when not set.)

Backend behavior:
- When `leave_category_id` is sent, the user's leave policies are synced to all policies of that category (missing ones added, others removed). Clearing the category removes the category-based assignments. The company default policy (`is_default = 1`) is never removed.
- Re-sync also happens automatically when a policy is created, when a policy's `leave_category_id` changes, and when a category is deleted.
- `GET /users/{id}/leave-policies?year=` is unchanged and rows now also include `leave_category_id` and `leave_category_name`.
- `PUT /users/{id}/leave-policies` is no longer used by the frontend (still available, deprecated).
- `GET /leave-policy-categories` is also allowed for users with the contract update permission (`PROFILE_CONTRACT_WORK_UPDATE`).

## 3. UI to-do
- Add a **Leave Policy Categories** settings page (list, create, edit, delete).
- Add a **Category** dropdown to the Leave Policy form (source: `GET /leave-policy-categories`), required on create.
- Show `leave_category_name` in the leave policy table/details.

## 4. Validation error messages
Errors follow the usual 422 format, localized (en/ar), e.g. `leave_category_id`: "The leave policy category is required."

## 5. Backend notes
- Migrations: `create_leave_policy_categories_table` (module `LeavePolicyCategory`) and `add_leave_category_id_to_leave_policies_table` (module `LeavePolicy`). Run tenant migrations.
- New permissions `LEAVE_POLICY_CATEGORY_{LIST,VIEW,CREATE,UPDATE,DELETE}` must be assigned to roles.
