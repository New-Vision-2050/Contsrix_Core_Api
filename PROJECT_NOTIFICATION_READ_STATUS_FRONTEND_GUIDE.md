# Project Notification Read/Status — Frontend Guide

> Copy-paste this guide to your frontend AI/tooling to style unread project-notification rows with a different background.

## 1. What changed in the API

Every project-notification response now includes a per-user `is_read` boolean:

- `GET /api/v1/projects/notifications` (list/index)
- `GET /api/v1/projects/notifications/{id}` (detail)
- `GET /api/v1/projects/notifications/map-tasks`
- `GET /api/v1/projects/notifications/my-tasks`
- `GET /api/v1/projects/notifications/my-inbox`
- All mutating endpoints (`approve`, `reject`, `request-update`, `update-site-status`, etc.)

Example list row:

```json
{
  "id": "uuid",
  "notification_number": "NTF-2026-00001",
  "status": "pending",
  "status_label": "بانتظار الرد",
  "is_read": false,
  ...
}
```

- `is_read: true` → the current user has marked it as read.
- `is_read: false` or missing → unread.

## 2. How to mark read/unread

Call this endpoint when the user opens/clicks a notification row, or when toggling a read/unread switch:

> **Important:** `POST /projects/notifications/{id}/update-site-status` and `POST /projects/notifications/{id}/end-task-status` automatically reset `is_read` to `false` for everyone, because the notification now contains new status information. After those endpoints succeed, the row should be styled as unread again.

```http
POST /api/v1/projects/notifications/{id}/read-status
Content-Type: application/json

{
  "is_read": true
}
```

To mark unread again:

```json
{
  "is_read": false
}
```

## 3. Row background styling

Use the `is_read` field in your table/list row component.

### React + TailwindCSS example

```jsx
function NotificationRow({ notification }) {
  const isUnread = !notification.is_read;

  return (
    <tr
      onClick={() => markAsRead(notification.id)}
      className={[
        "cursor-pointer border-b transition-colors",
        isUnread ? "bg-sky-50 hover:bg-sky-100" : "bg-white hover:bg-gray-50",
      ].join(" ")}
    >
      <td>{notification.notification_number}</td>
      <td>{notification.status_label}</td>
      <td>{notification.contractor_name}</td>
    </tr>
  );
}

async function markAsRead(notificationId) {
  await api.post(`/projects/notifications/${notificationId}/read-status`, {
    is_read: true,
  });
}
```

### Vue + TailwindCSS example

```vue
<template>
  <tr
    @click="markAsRead(notification.id)"
    :class="[
      'cursor-pointer border-b',
      !notification.is_read ? 'bg-sky-50 hover:bg-sky-100' : 'bg-white hover:bg-gray-50',
    ]"
  >
    <td>{{ notification.notification_number }}</td>
    <td>{{ notification.status_label }}</td>
    <td>{{ notification.contractor_name }}</td>
  </tr>
</template>

<script setup>
async function markAsRead(notificationId) {
  await api.post(`/projects/notifications/${notificationId}/read-status`, {
    is_read: true,
  });
}
</script>
```

### Plain CSS example

```css
.notification-row.unread {
  background-color: #f0f9ff; /* light sky blue */
}
.notification-row.read {
  background-color: #ffffff;
}
```

```html
<tr class="notification-row {{ is_read ? 'read' : 'unread' }}">...</tr>
```

## 4. UX recommendations

- **Auto-mark as read** when the user opens the notification detail or clicks the row.
- **Bold unread text** alongside the background change for stronger visual emphasis.
- **Read/unread toggle** in the row actions menu (e.g., a small eye icon) that calls the same endpoint with `is_read: false`.
- **Badge/filter**: show an “Unread” badge on rows where `is_read === false`.

## 5. Arabic UI labels (optional)

| English | Arabic |
|---|---|
| Mark as read | تحديد كمقروء |
| Mark as unread | تحديد كغير مقروء |
| Unread | غير مقروء |
| Read | مقروء |
