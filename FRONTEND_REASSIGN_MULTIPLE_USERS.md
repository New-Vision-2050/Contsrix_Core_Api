# Frontend Implementation Guide — Reassign Project Notification to Multiple Users

> This guide is a step-by-step recipe for frontend developers.
> It explains how to replace the single-user reassignment flow with the new multi-user flow (`assigned_user_ids`).
>
> For the conceptual/backend overview, see `FRONTEND_PROJECT_NOTIFICATION_REASSIGN.md`.

---

## 1. What changed

| Before (single user) | After (multiple users) |
|---|---|
| Send `{ "user_id": "<uuid>" }` | Send `{ "assigned_user_ids": ["<uuid>", "<uuid>"] }` |
| Backend appended the user to existing assignees | Backend **replaces** the notification's assigned-user list with the submitted array |
| Backend created one confirm-receive process | Backend creates one `CreateProjectNotificationTask` process **per user**, identical to notification creation |
| Use radio buttons / single select | Use checkboxes / multi-select chips |

**Important rule for frontend:** the API treats `assigned_user_ids` as the **final** list. Send the complete list of users you want assigned, not just the delta.

---

## 2. Component checklist

You need three pieces of UI, all reused from the **notification creation** flow:

1. **Multi-employee selector** — the same component used when creating a project notification.
2. **Map preview** — optional, shows the task location (`task_latitude`, `task_longitude`).
3. **Confirm button** — calls `POST /projects/notifications/{id}/reassign`.

---

## 3. Fetch the current state

Call the existing detail endpoint to get the notification and its current assignees:

```http
GET /api/v1/projects/notifications/{id}
```

Fields you need:

```jsonc
{
  "data": {
    "assigned_users": [
      { "id": "uuid-1", "name": "..." }
    ],
    "employee_task": {
      "task_latitude": 24.7136,
      "task_longitude": 46.6753
    }
  }
}
```

Use `assigned_users` to pre-select the current assignees in your multi-select component.

---

## 4. Build the request body

```json
{
  "assigned_user_ids": [
    "5636bbaf-230e-4a53-9f54-553e6bed6d17",
    "ae02596b-43f7-4155-8ea4-dd5fb19a36ed"
  ]
}
```

Rules:

- Minimum 1 user.
- No duplicates.
- All IDs must exist in `users.id`.
- Send the **full desired list**, not the delta.

---

## 5. Example implementation (framework-agnostic JS)

```js
// State
const [selectedUserIds, setSelectedUserIds] = useState([]);
const [loading, setLoading] = useState(false);
const [error, setError] = useState(null);

// On mount: load notification detail and pre-select current assignees
useEffect(() => {
  api.get(`/projects/notifications/${notificationId}`).then((res) => {
    const currentIds = res.data.data.assigned_users.map((u) => u.id);
    setSelectedUserIds(currentIds);
  });
}, [notificationId]);

// Multi-select change handler
const handleSelectionChange = (newSelectedIds) => {
  setSelectedUserIds(newSelectedIds);
};

// Submit
const handleReassign = async () => {
  if (selectedUserIds.length === 0) {
    setError('Please select at least one employee.');
    return;
  }

  setLoading(true);
  setError(null);

  try {
    const res = await api.post(`/projects/notifications/${notificationId}/reassign`, {
      assigned_user_ids: selectedUserIds,
    });

    showToast('Task reassigned successfully');
    refreshNotificationDetail(res.data.data);
  } catch (err) {
    const message = err.response?.data?.message || 'Reassignment failed';
    setError(message);
  } finally {
    setLoading(false);
  }
};
```

---

## 6. React-style component skeleton

```jsx
function ReassignNotificationModal({ notificationId, onSuccess }) {
  const [selectedUserIds, setSelectedUserIds] = useState([]);
  const [employees, setEmployees] = useState([]);
  const [loading, setLoading] = useState(false);

  // Load employees and current assignees
  useEffect(() => {
    async function load() {
      const [employeesRes, notificationRes] = await Promise.all([
        api.get('/projects/notifications/employees-with-locations'),
        api.get(`/projects/notifications/${notificationId}`),
      ]);

      setEmployees(employeesRes.data.data);
      setSelectedUserIds(
        notificationRes.data.data.assigned_users.map((u) => u.id)
      );
    }
    load();
  }, [notificationId]);

  const toggleUser = (userId) => {
    setSelectedUserIds((prev) =>
      prev.includes(userId)
        ? prev.filter((id) => id !== userId)
        : [...prev, userId]
    );
  };

  const submit = async () => {
    if (selectedUserIds.length === 0) return;
    setLoading(true);
    try {
      const res = await api.post(
        `/projects/notifications/${notificationId}/reassign`,
        { assigned_user_ids: selectedUserIds }
      );
      onSuccess(res.data.data);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <EmployeeMultiSelector
        employees={employees}
        selectedIds={selectedUserIds}
        onToggle={toggleUser}
      />
      <button onClick={submit} disabled={loading || selectedUserIds.length === 0}>
        {loading ? 'Reassigning...' : 'Confirm Reassign'}
      </button>
    </div>
  );
}
```

---

## 7. Validation & error handling

Show these errors to the user:

| Backend status | Meaning | Frontend message |
|---|---|---|
| `422` | `assigned_user_ids` missing, empty, or contains invalid/non-existent UUIDs | "Please select at least one valid employee." |
| `404` | Notification not found | "Notification not found." |
| `409`/generic | Linked task missing or one selected user not found | Show `response.data.message` |
| Network error | Connection issue | "Check your connection and try again." |

---

## 8. After-success behavior

1. Close the reassignment modal.
2. Refresh the notification detail/list from the API response.
3. Show toast: **"Task reassigned successfully. Selected employees can now confirm receipt to start their lifecycle."**
4. Each newly assigned employee will see the notification in their mobile inbox immediately.

---

## 9. Mobile / confirm-receive side

Nothing changes on mobile:

- Each assigned employee calls:
  ```http
  POST /api/v1/projects/notifications/{id}/confirm-receive
  ```
- They may first see and approve a `CreateProjectNotificationTask` process in their inbox.
- Once approved, the task moves to `in_progress` and their independent lifecycle begins.

---

## 10. Testing checklist

- [ ] Reassign to a single user works (backward-compatible UX path).
- [ ] Reassign to multiple users creates pending inbox entries for all selected users.
- [ ] Removing a previously assigned user via the multi-select removes them from the notification.
- [ ] Submitting an empty list shows a validation error before calling the API.
- [ ] Duplicate IDs in the UI are not sent to the backend.
- [ ] After reassignment, unselected previous users no longer see the task in their inbox.
- [ ] All selected users receive the same notifications as they would on task creation.
