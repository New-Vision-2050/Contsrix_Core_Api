# Project Notification Notes — Frontend Guide

> Copy-paste this guide to your frontend AI/tooling to add a “Notes” tab next to “Update Site Status” in the notification details UI. It shows all user notes from newest to oldest, and lets users add new notes.

## 1. What changed in the API

- **New table/model**: `project_notification_notes` + `ProjectNotificationNote` (per-notification, per-user notes with `note` text and timestamps)
- **New endpoints**:
  - `GET /api/v1/projects/notifications/{id}/notes` — returns all notes, newest first
  - `POST /api/v1/projects/notifications/{id}/notes` — add a new note (body: `{ "note": "text" }`)
- **Presenter changes**:
  - Every notification list/detail response now includes `last_note` (the newest note, with user/branch/timezone) so you can show a preview in the list.
  - The notes list endpoint returns each note with `id`, `note`, `created_at` (timezone‑aware), `user` (id/name/phone), and `branch` (id/name).

## 2. API examples

### List all notes for a notification

```http
GET /api/v1/projects/notifications/{id}/notes
```

Response:

```json
{
  "items": [
    {
      "id": "uuid",
      "note": "Power was restored after 2 hours. Crew is back on site.",
      "created_at": "2026-07-11 15:45:00",
      "timezone": "Asia/Riyadh",
      "user": {
        "id": "uuid",
        "name": "Ahmed Ali",
        "phone": "966500000001"
      },
      "branch": {
        "id": "uuid",
        "name": "Riyadh Main"
      }
    },
    {
      "id": "uuid",
      "note": "Initial outage reported at 13:30. Dispatched team.",
      "created_at": "2026-07-11 13:32:00",
      "timezone": "Asia/Riyadh",
      "user": {
        "id": "uuid",
        "name": "Sara Khalid",
        "phone": "966500000002"
      },
      "branch": {
        "id": "uuid",
        "name": "Riyadh Main"
      }
    }
  ],
  "timezone": "Asia/Riyadh"
}
```

### Add a new note

```http
POST /api/v1/projects/notifications/{id}/notes
Content-Type: application/json

{
  "note": "Customer reported flickering lights after power returned."
}
```

Response (single formatted note):

```json
{
  "id": "uuid",
  "note": "Customer reported flickering lights after power returned.",
  "created_at": "2026-07-11 16:02:00",
  "timezone": "Asia/Riyadh",
  "user": {
    "id": "uuid",
    "name": "Mohammed Saleh",
    "phone": "966500000003"
  },
  "branch": {
    "id": "uuid",
    "name": "Riyadh Main"
  }
}
```

### `last_note` in list/detail responses

```json
{
  "id": "uuid",
  "notification_number": "NTF-2026-00001",
  "last_note": {
    "id": "uuid",
    "note": "Power was restored after 2 hours. Crew is back on site.",
    "created_at": "2026-07-11 15:45:00",
    "user": { "id": "uuid", "name": "Ahmed Ali", "phone": "966500000001" },
    "branch": { "id": "uuid", "name": "Riyadh Main" }
  },
  // ... other fields
}
```

If there are no notes, `last_note` is `null`.

## 3. UI placement

- **Add a new tab** in the notification detail screen next to “Update Site Status”. Label: “Notes” / “ملاحظات”.
- **Inside the tab**:
  - Show a **list of note cards** from newest to oldest (the API already returns them sorted).
  - Each card should display:
    - Note text
    - Created at (already timezone‑aware)
    - User name (and optionally phone)
    - Branch name
  - Provide a **“Add note”** button/area at the bottom of the list. When submitted, call `POST /notes` and prepend the new note to the list (optimistic UI).

## 4. Component examples

### React + TailwindCSS

```jsx
function NotificationNotes({ notificationId }) {
  const [notes, setNotes] = useState([]);
  const [newNote, setNewNote] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    fetchNotes();
  }, [notificationId]);

  const fetchNotes = async () => {
    const res = await api.get(`/projects/notifications/${notificationId}/notes`);
    setNotes(res.data.items);
  };

  const handleAdd = async (e) => {
    e.preventDefault();
    if (!newNote.trim()) return;
    setLoading(true);
    const added = await api.post(`/projects/notifications/${notificationId}/notes`, {
      note: newNote,
    });
    setNotes(prev => [added.data, ...prev]);
    setNewNote('');
    setLoading(false);
  };

  return (
    <div className="space-y-4">
      {/* List */}
      {notes.map((note) => (
        <div key={note.id} className="border rounded p-4 bg-white shadow-sm">
          <p className="text-gray-900 mb-2">{note.note}</p>
          <div className="text-xs text-gray-500 space-x-4">
            <span>{note.user?.name}</span>
            <span>{note.branch?.name}</span>
            <span>{note.created_at}</span>
          </div>
        </div>
      ))}

      {/* Add form */}
      <form onSubmit={handleAdd} className="border rounded p-4 bg-gray-50">
        <textarea
          value={newNote}
          onChange={(e) => setNewNote(e.target.value)}
          placeholder="Add a note..."
          className="w-full p-2 border rounded"
          rows={3}
        />
        <button
          type="submit"
          disabled={loading || !newNote.trim()}
          className="mt-2 px-4 py-2 bg-blue-600 text-white rounded disabled:opacity-50"
        >
          Add Note
        </button>
      </form>
    </div>
  );
}
```

### Vue + TailwindCSS

```vue
<template>
  <div class="space-y-4">
    <!-- List -->
    <div v-for="note in notes" :key="note.id" class="border rounded p-4 bg-white shadow-sm">
      <p class="text-gray-900 mb-2">{{ note.note }}</p>
      <div class="text-xs text-gray-500 space-x-4">
        <span>{{ note.user?.name }}</span>
        <span>{{ note.branch?.name }}</span>
        <span>{{ note.created_at }}</span>
      </div>
    </div>

    <!-- Add form -->
    <form @submit.prevent="handleAdd" class="border rounded p-4 bg-gray-50">
      <textarea
        v-model="newNote"
        placeholder="Add a note..."
        class="w-full p-2 border rounded"
        rows="3"
      />
      <button
        type="submit"
        :disabled="loading || !newNote.trim()"
        class="mt-2 px-4 py-2 bg-blue-600 text-white rounded disabled:opacity-50"
      >
        Add Note
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({ notificationId: String });
const notes = ref([]);
const newNote = ref('');
const loading = ref(false);

const fetchNotes = async () => {
  const res = await api.get(`/projects/notifications/${props.notificationId}/notes`);
  notes.value = res.data.items;
};

const handleAdd = async () => {
  if (!newNote.value.trim()) return;
  loading.value = true;
  const added = await api.post(`/projects/notifications/${props.notificationId}/notes`, {
    note: newNote.value,
  });
  notes.value.unshift(added.data);
  newNote.value = '';
  loading.value = false;
};

onMounted(fetchNotes);
</script>
```

## 5. Arabic UI labels (optional)

| English | Arabic |
|---|---|
| Notes | ملاحظات |
| Add note | إضافة ملاحظة |
| Write a note… | اكتب ملاحظة… |
| No notes yet | لا توجد ملاحظات بعد |

## 6. UX tips

- **Optimistic UI**: prepend the newly added note immediately, then refetch if the API fails.
- **Auto‑scroll** to the new note after adding.
- **Character limit**: the backend accepts any length, but you may enforce a reasonable limit (e.g., 1000) in the UI for better UX.
- **Read‑only**: notes cannot be edited or deleted via the API (they are audit logs). If you need edit/delete, request those endpoints.

That’s it! You now have a full notes tab that mirrors the “Update Site Status” cards UI, with timezone‑aware timestamps and user/branch context.
