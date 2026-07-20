# Site Status Type ↔ Notification Types — Frontend Integration Guide

## Overview

Site status types can now be linked to one or more **notification types** (from the `project_notification_types` lookup table). This allows the frontend to filter the site status type dropdown based on the notification type the user selects when creating a project notification.

---

## What Changed

### 1. Site Status Type Create/Update APIs — New `notification_types` Field

**POST** `/api/v1/projects/notifications/site-status-types`
**PUT** `/api/v1/projects/notifications/site-status-types/{id}`

Both APIs now accept an optional `notification_types` array in the request body. Each element is a UUID referencing a row in the `project_notification_types` table.

#### Request Body (new field highlighted)

```json
{
  "project_type_id": 1,
  "name_ar": "صيانة دورية",
  "name_en": "Periodic Maintenance",
  "sort_order": 1,
  "is_active": true,
  "notification_types": [
    "uuid-of-notification-type-1",
    "uuid-of-notification-type-2"
  ],
  "keys": [ ... ]
}
```

- **`notification_types`**: `array` of UUID strings (optional, nullable)
- **`notification_types.*`**: must be a valid UUID that exists in `project_notification_types` table

### 2. GET Site Status Types — New `notification_type_id` Query Filter

**GET** `/api/v1/projects/notifications/site-status-types`
**GET** `/api/v1/projects/notifications/site-status-types/with-keys`

Both APIs now accept an optional `notification_type_id` query parameter. When provided, only site status types linked to that notification type are returned.

#### Example

```text
GET /api/v1/projects/notifications/site-status-types?project_type_id=1&notification_type_id=0dc9ddbe-cbad-4ff2-8c69-132c65b26ec2
```

### 3. Site Status Type Response — New `notification_types` Field

All site status type responses (list, show, create, update) now include a `notification_types` array:

```json
{
  "id": "site-status-type-uuid",
  "project_type_id": 1,
  "name_ar": "صيانة دورية",
  "name_en": "Periodic Maintenance",
  "sort_order": 1,
  "is_active": true,
  "notification_types": [
    {
      "id": "notification-type-uuid-1",
      "name_ar": "جهد متوسط كابلات هوائي",
      "name_en": "Medium voltage aerial cables"
    },
    {
      "id": "notification-type-uuid-2",
      "name_ar": "جهد منخفض كابلات ارضي",
      "name_en": "Low voltage ground cables"
    }
  ],
  "created_at": "...",
  "updated_at": "..."
}
```

---

## Frontend Integration Steps

### Step 1: Admin — Link Site Status Types to Notification Types

In the admin page where you create/edit **site status types**, add a multi-select dropdown to choose which notification types this site status type applies to.

- Fetch available notification types from:
  `GET /api/v1/projects/notifications/notification-types`
- Send the selected UUIDs as `notification_types` array in the create/update request.

### Step 2: Create Project Notification — Filter Site Status Types by Notification Type

When the user selects a **notification type** in the create project notification form:

1. Fetch site status types filtered by the selected notification type:
   ```text
   GET /api/v1/projects/notifications/site-status-types?project_type_id={project_type_id}&notification_type_id={selected_notification_type_id}
   ```
2. Populate the site status type dropdown with only the returned options.
3. If the user changes the notification type, re-fetch the site status types with the new `notification_type_id`.

### Step 3: Update Project Notification — Same Filter

When editing a project notification, if the user changes the notification type, re-fetch site status types with the new filter to ensure the selected site status type is still valid.

---

## Available Notification Types

Fetch the list of available notification types (seeded lookup table):

```text
GET /api/v1/projects/notifications/notification-types
```

Response:
```json
{
  "items": [
    {
      "id": "uuid-1",
      "value": "جهد متوسط كابلات هوائي",
      "name_ar": "جهد متوسط كابلات هوائي",
      "name_en": "Medium voltage aerial cables",
      "sort_order": 1,
      "is_active": true
    }
  ]
}
```

---

## Summary of API Changes

| API | Change |
|-----|--------|
| `POST /site-status-types` | New optional `notification_types` array field in request body |
| `PUT /site-status-types/{id}` | New optional `notification_types` array field in request body |
| `GET /site-status-types` | New optional `notification_type_id` query param for filtering |
| `GET /site-status-types/with-keys` | New optional `notification_type_id` query param for filtering |
| `GET /site-status-types/{id}` | Response now includes `notification_types` array |
| All site status type responses | Now include `notification_types` array in the payload |
