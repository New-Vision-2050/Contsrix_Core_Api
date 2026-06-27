# Frontend Update Guide — Project Notification Form

> **Scope**: Changes required on the frontend for the project notification create/update flow (إضافة إشعار).
> **Backend tickets covered**: contractor list seeding, `magdy_number` rename, `contractor_technical_name` field, employee status in location list.

---

## 1. Contractor dropdown (new list endpoint)

### New endpoint

```
GET /api/v1/projects/notifications/contractors
```

**Permission**: same as create notification (`PROJECT_NOTIFICATION_CREATE`).

**Response shape** (inside `data.items`):

```json
[
  {
    "id": "uuid",
    "name": "الشركة السعودية للتجارة والمقاولات ساتيك",
    "number": "CNT-001",
    "mobile": null,
    "notes": null
  }
]
```

### UI changes

- **Step 2 — بيانات المقاول**: `contractor_name` must become a dropdown populated from the endpoint above.
- When the user selects a contractor, auto-fill `contractor_number` from the selected item's `number` field.
- Send the selected contractor's `id` as `contractor_id` in the create/update payload.
- `contractor_name` and `contractor_number` are still accepted, but if you send `contractor_id`, the backend will auto-fill them when they are empty.

---

## 2. Rename `magdy_number` → `feeder_number`

The field labeled **الرقم المغذي** in Step 1 (بيانات الإشعار) must be sent as:

```json
{
  "feeder_number": "..."
}
```

**Stop sending `magdy_number`.** The backend no longer accepts or stores it under that name.

---

## 3. Add `contractor_technical_name` field

In Step 2 — بيانات المقاول, the dropdown **فني المقاول** must be sent as:

```json
{
  "contractor_technical_name": "..."
}
```

The existing `contractor_technical_number` field remains unchanged and is still accepted for the **رقم الفني** field.

### Updated contractor section payload example

```json
{
  "contractor_id": "uuid",
  "contractor_name": "الشركة السعودية للتجارة والمقاولات ساتيك",
  "contractor_number": "CNT-001",
  "contractor_technical_name": "فني المقاول المختار",
  "contractor_technical_number": "...",
  "contractor_category": "...",
  "contractor_notes": "...",
  "contractor_mobile": "..."
}
```

---

## 4. Employees-with-locations status field

`GET /api/v1/projects/notifications/employees-with-locations` already returns an employee status. Ensure the UI uses it in Step 4 (الإسناد لموظف).

**Status values returned**:

| Status        | Arabic label | Meaning |
|---------------|--------------|---------|
| `available`   | متاح         | On duty, has recent GPS location, no active task |
| `busy`        | مشغول / في مهمة | On duty and has an active `in_progress` or `approved` task today |
| `offline`     | غير متصل      | No attendance clock-in today |
| `no_location` | لا يوجد موقع  | On duty but no/fresh GPS location available |

**Response item shape** (relevant keys):

```json
{
  "user_id": "uuid",
  "name": "...",
  "status": "available",
  "status_label": "متاح",
  "distance_meters": 1200,
  "distance_label": "1.2 كم",
  "location": {
    "latitude": 24.7136,
    "longitude": 46.6753,
    "source": "GPS"
  }
}
```

Use `status` (or `status_label`) to color the employee row and decide whether they can be assigned.

---

## 5. Summary of renamed/new fields

| Old field name                | New field name                | Notes |
|-------------------------------|-------------------------------|-------|
| `magdy_number`                | `feeder_number`               | Mandatory rename |
| —                             | `contractor_id`               | Send selected contractor UUID |
| `contractor_name` (free text) | `contractor_name` (auto-fill) | Should come from selected contractor |
| `contractor_number` (free text) | `contractor_number` (auto-fill) | Should come from selected contractor |
| —                             | `contractor_technical_name`   | New dropdown field |
| `contractor_technical_number` | `contractor_technical_number` | Unchanged |

---

## 6. Migration / seeding commands for the backend team

These are provided for reference; the backend must run them before the frontend changes go live:

```bash
# Run module migrations (creates contractors table + project_notifications changes)
php artisan migrate

# Seed contractors for each tenant (already registered in TenantDatabaseSeeder)
php artisan tenants:seed --class=Database\\Seeders\\TenantDatabaseSeeder
```

---

## 7. Test checklist

- [ ] `GET /notifications/contractors` returns 23 active contractors.
- [ ] Selecting a contractor auto-fills `contractor_number` from the API response.
- [ ] Create notification succeeds with `contractor_id` only.
- [ ] Create notification succeeds with `feeder_number` instead of `magdy_number`.
- [ ] Create notification includes `contractor_technical_name`.
- [ ] Employee list shows colored status badges (`available`, `busy`, `offline`, `no_location`).
- [ ] Update notification works with the same contractor payload changes.
