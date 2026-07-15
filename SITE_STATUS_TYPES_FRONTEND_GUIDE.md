# Site Status Types — Frontend Guide

> **Module:** Project → ProjectNotifications  
> **Scope:** Maintenance & Emergency (الصيانة والطوارئ)  
> **Date:** 2026-07-15

## 1. What changed

A new independent lookup called **Site Status Types** (انواع حالة الموقع) is added under the Maintenance & Emergency section. Each type can define a dynamic set of input fields (keys). When a project notification is created or updated, the user can pick a site status type from a dropdown; the fields belonging to that type then render as inputs. The values are stored as key-value pairs on the project notification and are also shown in the site status updates tab for reference.

### Key concepts

- **Site Status Type**: a category such as `كابل`, `محول`, `طول التوصيله`, etc.
- **Site Status Type Key**: one dynamic field inside a type (e.g. "طول التوصيله"). It has a display name, a machine `key`, a `field_type`, and a flag `show_in_site_status_updates`.
- **Site Status Value**: the value stored for a specific notification + key.
- **request-site-status-update**: unchanged; it does **not** save or edit these dynamic values. Values are only edited through the notification create/update forms.

---

## 2. Admin UI — Maintenance & Emergency schema

### 2.0 Placement

The site status types configuration UI is **not** a separate schema/tab. It is rendered inside the existing **الصيانة والطوارئ** (Maintenance & Emergency) tab — schema `12` — using the frontend's own mechanism.

When the user opens the Maintenance & Emergency tab for a project type, show a section/button named **انواع حالة الموقع** (Site Status Types). The frontend decides where exactly to place it within that tab.

The CRUD actions can be gated with the dedicated permissions listed in the permissions config:

- `PROJECT_NOTIFICATION_SITE_STATUS_TYPE_LIST`
- `PROJECT_NOTIFICATION_SITE_STATUS_TYPE_VIEW`
- `PROJECT_NOTIFICATION_SITE_STATUS_TYPE_CREATE`
- `PROJECT_NOTIFICATION_SITE_STATUS_TYPE_UPDATE`
- `PROJECT_NOTIFICATION_SITE_STATUS_TYPE_DELETE`

### 2.1 Types list

Fetch the list from:

```text
GET /projects/notifications/site-status-types
```

Response (`items`):

```json
{
  "id": "uuid",
  "name_ar": "كابل",
  "name_en": "Cable",
  "sort_order": 1,
  "is_active": true
}
```

### 2.2 Create / edit a type

```text
POST /projects/notifications/site-status-types
PUT  /projects/notifications/site-status-types/{id}
```

Body:

```json
{
  "name_ar": "كابل",
  "name_en": "Cable",
  "sort_order": 1,
  "is_active": true
}
```

### 2.3 Manage keys inside a type

Open a type to see its keys. Fetch keys with:

```text
GET /projects/notifications/site-status-types/{id}/keys
```

Response (`items`):

```json
{
  "id": "uuid",
  "site_status_type_id": "uuid",
  "name_ar": "طول التوصيله",
  "name_en": "Cable length",
  "key": "cable_length",
  "field_type": "text",
  "options": null,
  "show_in_site_status_updates": true,
  "sort_order": 1,
  "is_active": true
}
```

Create a key:

```text
POST /projects/notifications/site-status-types/{id}/keys
```

Body:

```json
{
  "name_ar": "طول التوصيله",
  "name_en": "Cable length",
  "key": "cable_length",
  "field_type": "text",
  "show_in_site_status_updates": true,
  "sort_order": 1,
  "is_active": true
}
```

> **Note:** `key` is optional. If omitted, the backend will auto-generate a Latin snake_case key from the Arabic name. If you want stable API keys, provide one explicitly (`a-z`, `0-9`, `_`).

Update a key:

```text
PUT /projects/notifications/site-status-types/{id}/keys/{key_id}
```

Delete a key:

```text
DELETE /projects/notifications/site-status-types/{id}/keys/{key_id}
```

### 2.4 Field types

The backend supports four field types:

| `field_type` | Frontend render | Extra payload |
|---|---|---|
| `text` | text input | — |
| `number` | number input | — |
| `date` | date picker | — |
| `select` | select/dropdown | `options: ["Option 1", "Option 2"]` |

### 2.5 “Show in site status updates” flag

Each key has a boolean `show_in_site_status_updates`. When `true`, the key and its value must appear in the site status updates tab as read-only reference data (with a copy button).

---

## 3. Project Notification form

### 3.1 New dropdown

In the **create** and **edit** project notification forms, add a new dropdown:

- Label: **نوع حالة الموقع** (Site Status Type)
- Data source: `GET /projects/notifications/site-status-types`
- Value: `id`
- Display: `name_ar` (or `name_en` if locale is English)

### 3.2 Dynamic fields

When a type is selected, fetch its keys:

```text
GET /projects/notifications/site-status-types/{id}/keys
```

Render one input per active key, using the `field_type` and `options`.

### 3.3 Submit format

Add two new fields to the existing notification create/update payload:

```json
{
  "site_status_type_id": "uuid-or-null",
  "site_status_type_values": [
    {
      "key_id": "uuid-of-key",
      "value": "value-entered-by-user"
    }
  ]
}
```

Rules:

- If `site_status_type_id` is `null`, the backend clears all stored values.
- `site_status_type_values` is optional. When provided, it **fully replaces** the existing values.
- Only send values for keys that belong to the selected `site_status_type_id`.

### 3.4 Edit form

When editing a notification, the detail API now returns:

```json
{
  "site_status_type_id": "uuid",
  "site_status_type": {
    "id": "uuid",
    "name_ar": "كابل",
    "name_en": "Cable"
  },
  "site_status_values": [
    {
      "id": "uuid",
      "key_id": "uuid",
      "key": "cable_length",
      "name_ar": "طول التوصيله",
      "name_en": "Cable length",
      "field_type": "text",
      "options": null,
      "show_in_site_status_updates": true,
      "value": "120 متر"
    }
  ]
}
```

Use this to pre-fill the dropdown and dynamic inputs.

---

## 4. Site status updates tab

The endpoint for the site status updates tab is unchanged:

```text
GET /projects/notifications/{id}/site-status-updates
```

But the response now includes two extra top-level keys:

```json
{
  "items": [...],
  "summary": {...},
  "timezone": "Africa/Cairo",
  "site_status_type": {
    "id": "uuid",
    "name_ar": "كابل",
    "name_en": "Cable"
  },
  "notification_values": [
    {
      "id": "uuid",
      "key_id": "uuid",
      "key": "cable_length",
      "name_ar": "طول التوصيله",
      "name_en": "Cable length",
      "field_type": "text",
      "options": null,
      "value": "120 متر"
    }
  ]
}
```

### 4.1 How to display

- Show `site_status_type` as a read-only label at the top of the tab.
- Show `notification_values` as a read-only list of key-value pairs.
- For each value, add a **copy button** that copies the `value` to the clipboard. The user can then paste it into any field they need.
- These values are **not editable** here; they are only edited from the notification update form.

### 4.2 Copied updates tab

The same `site_status_type` and `notification_values` are also included in:

```text
GET /projects/notifications/{id}/site-status-updates/copied
```

---

## 5. Summary of new API endpoints

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/projects/notifications/site-status-types` | List active types |
| GET | `/projects/notifications/site-status-types/with-keys` | List active types with their active keys |
| POST | `/projects/notifications/site-status-types` | Create a type |
| GET | `/projects/notifications/site-status-types/{id}` | Show a type with keys |
| PUT | `/projects/notifications/site-status-types/{id}` | Update a type |
| DELETE | `/projects/notifications/site-status-types/{id}` | Delete a type |
| GET | `/projects/notifications/site-status-types/{id}/keys` | List keys of a type |
| POST | `/projects/notifications/site-status-types/{id}/keys` | Create a key |
| PUT | `/projects/notifications/site-status-types/{id}/keys/{key_id}` | Update a key |
| DELETE | `/projects/notifications/site-status-types/{id}/keys/{key_id}` | Delete a key |

Changed existing endpoints:

- `POST /projects/notifications` — accepts `site_status_type_id` and `site_status_type_values`.
- `PUT /projects/notifications/{id}` — accepts `site_status_type_id` and `site_status_type_values`.
- `GET /projects/notifications/{id}` — returns `site_status_type_id`, `site_status_type`, and `site_status_values`.
- `GET /projects/notifications/{id}/site-status-updates` — returns `site_status_type` and `notification_values`.
- `GET /projects/notifications/{id}/site-status-updates/copied` — returns `site_status_type` and `notification_values`.

---

## 6. Important notes for frontend

1. **Values are stored on the notification, not per update.** The `request-site-status-update` endpoint does not accept or change these values.
2. **Copy-to-clipboard only.** The user wanted a copy button beside each value in the site status updates tab; values are not edited there.
3. **Always send the full values array when editing.** If `site_status_type_values` is provided, it replaces all existing values.
4. **Field types:** render text, number, date, and select inputs based on `field_type`.
5. **Options:** only present for `field_type = select`. Use them as dropdown options.
6. **Show in site status updates:** only keys with `show_in_site_status_updates = true` appear in `notification_values` of the site status updates response.
