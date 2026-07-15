# Project Notification Copied Site Status Updates — Frontend Guide

> Use this guide to show copied site status updates separately from the regular site status history, and to highlight **not-copied** (original) updates with a border in the main list.

## 1. What changed in the API

- **New column**: `is_copied` on `project_notification_site_status_updates`.
- **Existing endpoint updated**: `GET /api/v1/projects/notifications/{id}/site-status-updates` now returns `is_copied` on every item.
- **New endpoint**: `GET /api/v1/projects/notifications/{id}/site-status-updates/copied` returns only approved updates where `is_copied = true`.

## 2. API endpoints

### Copy a site status update

```http
POST /api/v1/projects/notifications/{notification_id}/site-status-updates/{site_status_update_id}/copy
```

Marks a single approved site status update as copied (`is_copied: true`). On success the update will appear in the copied-only list.

```json
{
  "data": {
    "id": "uuid",
    "is_copied": true
  },
  "message": "Site status update marked as copied successfully"
}
```

### Main list (all updates)

```http
GET /api/v1/projects/notifications/{id}/site-status-updates
```

Each item now includes `is_copied: true|false`. Use this to style not-copied items with a border.

```json
{
  "data": {
    "items": [
      {
        "id": "uuid",
        "status": "approved",
        "is_copied": false,
        "description": "Foundations completed, rebar inspected.",
        "requested_by": { "id": "uuid", "name": "Ahmed Ali" },
        "reviewed_by": { "id": "uuid", "name": "Sara Khalid" },
        "reviewed_at": "2026-07-12 10:30:00",
        "created_at": "2026-07-12 09:00:00",
        "attachments": [],
        "process": { /* workflow steps */ }
      }
    ],
    "summary": { "total": 1, "approved": 1, "pending": 0 },
    "timezone": "Asia/Riyadh"
  }
}
```

### Copied updates only

```http
GET /api/v1/projects/notifications/{id}/site-status-updates/copied
```

Returns the same shape but only `is_copied: true` items. Use this for a separate “Copied” tab or section.

```json
{
  "data": {
    "items": [
      {
        "id": "uuid",
        "status": "approved",
        "is_copied": true,
        "description": "Copied from previous visit.",
        "requested_by": { "id": "uuid", "name": "Ahmed Ali" },
        "reviewed_by": { "id": "uuid", "name": "Sara Khalid" },
        "reviewed_at": "2026-07-13 14:00:00",
        "created_at": "2026-07-13 14:00:00",
        "attachments": [],
        "process": { /* workflow steps */ }
      }
    ],
    "summary": { "total": 1, "approved": 1, "pending": 0 },
    "timezone": "Asia/Riyadh"
  }
}
```

## 3. UI placement

- Keep the existing **“Site Status Updates”** tab and call `GET /site-status-updates`.
- In that list, put a visible border around **not-copied** items (`is_copied === false`) so users can quickly identify originals.
- Optional: add a new tab or section **“Copied Updates”** and call `GET /site-status-updates/copied` to show only copied records.

## 4. Component examples

### React + TailwindCSS — main list with border on not-copied

```jsx
function SiteStatusUpdateList({ notificationId }) {
  const [updates, setUpdates] = useState([]);

  useEffect(() => {
    api.get(`/projects/notifications/${notificationId}/site-status-updates`)
      .then((res) => setUpdates(res.data.data.items));
  }, [notificationId]);

  return (
    <div className="space-y-4">
      {updates.map((update) => (
        <div
          key={update.id}
          className={[
            'rounded p-4 bg-white shadow-sm',
            update.is_copied ? 'border border-gray-200' : 'border-2 border-orange-400',
          ].join(' ')}
        >
          <p className="text-gray-900 mb-2">{update.description}</p>
          <div className="text-xs text-gray-500 space-x-4">
            <span>{update.requested_by?.name}</span>
            <span>{update.created_at}</span>
            <span className="font-medium text-orange-600">
              {update.is_copied ? 'Copied' : 'Original'}
            </span>
          </div>
        </div>
      ))}
    </div>
  );
}
```

### React + TailwindCSS — copied updates tab

```jsx
function CopiedSiteStatusUpdates({ notificationId }) {
  const [copiedUpdates, setCopiedUpdates] = useState([]);

  useEffect(() => {
    api.get(`/projects/notifications/${notificationId}/site-status-updates/copied`)
      .then((res) => setCopiedUpdates(res.data.data.items));
  }, [notificationId]);

  return (
    <div className="space-y-4">
      {copiedUpdates.map((update) => (
        <div key={update.id} className="border rounded p-4 bg-gray-50">
          <p className="text-gray-900 mb-2">{update.description}</p>
          <div className="text-xs text-gray-500 space-x-4">
            <span>{update.requested_by?.name}</span>
            <span>{update.created_at}</span>
          </div>
        </div>
      ))}
    </div>
  );
}
```

### Vue + TailwindCSS — main list with border on not-copied

```vue
<template>
  <div class="space-y-4">
    <div
      v-for="update in updates"
      :key="update.id"
      :class="[
        'rounded p-4 bg-white shadow-sm',
        update.is_copied ? 'border border-gray-200' : 'border-2 border-orange-400',
      ]"
    >
      <p class="text-gray-900 mb-2">{{ update.description }}</p>
      <div class="text-xs text-gray-500 space-x-4">
        <span>{{ update.requested_by?.name }}</span>
        <span>{{ update.created_at }}</span>
        <span class="font-medium text-orange-600">
          {{ update.is_copied ? 'Copied' : 'Original' }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({ notificationId: String });
const updates = ref([]);

onMounted(async () => {
  const res = await api.get(`/projects/notifications/${props.notificationId}/site-status-updates`);
  updates.value = res.data.data.items;
});
</script>
```

## 5. Arabic UI labels (optional)

| English | Arabic |
|---|---|
| Site Status Updates | تحديثات حالة الموقع |
| Copied Updates | التحديثات المنسوخة |
| Original | أصلي |
| Copied | منسوخ |

## 6. UX tips

- Use the border only on the **main list** to highlight original (not-copied) updates.
- The copied tab can use a standard border since every item is already labeled as copied.
- The API returns `is_copied` for every item, so the same card component can be reused for both endpoints.

That’s it! You now have a separate endpoint for copied updates and a clear visual border for original (not-copied) site status updates.
