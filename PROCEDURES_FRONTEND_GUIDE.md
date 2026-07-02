# Project Notification Procedures — Frontend Carousel Guide

> **Endpoint**: `GET /api/v1/projects/notifications/{id}/procedures`
> **Alt endpoint**: `GET /api/v1/employee-tasks/{taskId}/procedures`
> **Permission**: `PROJECT_NOTIFICATION_VIEW`

---

## Response Shape

```json
{
  "items": [ /* array of procedure objects — see below */ ],
  "summary": {
    "total": 3,
    "last_action": "التحديث الدوري لحالة الموقع",
    "start_date": "2026-07-01",
    "progress": 45
  }
}
```

---

## Procedure Item Structure

Each item in `items[]` represents **one taken (completed) procedure**. The same form can appear multiple times (e.g., multiple site-status updates).

```json
{
  "id": "uuid",
  "step_number": 1,
  "name": "إنشاء إشعار مشروع",
  "icon": "clipboard-list",
  "percentage": 10.0,
  "form": "createProjectNotificationTask",
  "taken_by": { "id": "uuid", "name": "أحمد محمد" },
  "taken_at": "2026-07-01 09:30:00",
  "status": "completed",
  "steps": [
    {
      "step_order": 1,
      "name": "موافقة المدير",
      "status": "approved",
      "action_by": { "id": "uuid", "name": "خالد علي" },
      "acted_at": "2026-07-01 10:00:00"
    }
  ],
  "approved_by": { "id": "uuid", "name": "خالد علي" },
  "attachments": [
    {
      "id": 101,
      "url": "https://s3.example.com/.../file.jpg",
      "name": "site_photo.jpg",
      "mime_type": "image/jpeg",
      "type": "image",
      "size": 245678
    }
  ],
  "form_data": {
    "notification_type": "إصلاح عاجل",
    "work_description": "استبدال الكابل التالف"
  }
}
```

### Field Reference

| Field | Type | Description |
|---|---|---|
| `id` | string | Unique procedure ID |
| `step_number` | int | Sequential order (1, 2, 3...) |
| `name` | string | Arabic display name of the procedure |
| `icon` | string\|null | Icon key for UI |
| `percentage` | float\|null | Progress weight (0–100) |
| `form` | string | Form key — identifies the procedure type |
| `taken_by` | {id, name}\|null | User who submitted the form |
| `taken_at` | string\|null | Timestamp `Y-m-d H:i:s` |
| `status` | string\|null | Always `completed` for taken procedures |
| `steps[]` | array | Approval chain — see below |
| `approved_by` | {id, name}\|null | Final approver (shortcut to last approved step) |
| `attachments[]` | array | Uploaded files — see below |
| `form_data` | object\|null | Submitted form payload — varies by `form` key |

### Step Object

| Field | Type | Description |
|---|---|---|
| `step_order` | int | Step position in the workflow |
| `name` | string\|null | Step name (Arabic) |
| `status` | string | `approved`, `rejected`, or `pending` |
| `action_by` | {id, name}\|null | User who acted on this step |
| `acted_at` | string\|null | Action timestamp `Y-m-d H:i:s` |

### Attachment Object

| Field | Type | Description |
|---|---|---|
| `id` | int | Media ID |
| `url` | string | Full file URL (S3/Minio) |
| `name` | string | File name |
| `mime_type` | string | MIME type (e.g., `image/jpeg`, `application/pdf`) |
| `type` | string | File type category (`image`, `document`, etc.) |
| `size` | int | File size in bytes |

---

## Carousel UI Design

### Layout

```
┌─────────────────────────────────────────────┐
│  الإجراءات (Procedures)          3 / 45%    │  ← summary header
├─────────────────────────────────────────────┤
│                                             │
│  ◀  ┌─────────────────────────────────┐  ▶  │  ← carousel nav
│     │  ① إنشاء إشعار مشروع             │     │
│     │  أحمد محمد · 2026-07-01 09:30   │     │
│     │  ✅ completed                    │     │
│     │                                 │     │
│     │  [📸 site_photo.jpg]            │     │  ← attachment thumbnail
│     │                                 │     │
│     │  ─── Approval Steps ───         │     │
│     │  1. موافقة المدير    ✅ خالد علي │     │
│     │     2026-07-01 10:00            │     │
│     │  2. موافقة المشرف    ✅ سعد المرسي│    │
│     │     2026-07-01 10:30            │     │
│     │                                 │     │
│     │  ─── Form Data ───              │     │
│     │  النوع: إصلاح عاجل              │     │
│     │  الوصف: استبدال الكابل التالف    │     │
│     │  ملاحظات: تم الكشف الميداني      │     │
│     └─────────────────────────────────┘     │
│                                             │
│  ● ○ ○                                      │  ← dots indicator
└─────────────────────────────────────────────┘
```

### Carousel Behavior

1. **One card per procedure** — each `items[]` entry is a slide.
2. **Swipe / arrow navigation** — left/right to move between procedures.
3. **Dots indicator** — bottom dots showing current position (`step_number` / `total`).
4. **Multiple submissions** — if the same `form` appears multiple times, show each as a separate slide. Add a small badge like `(2nd submission)` on repeated forms.
5. **Progress bar** — use `summary.progress` for an overall progress bar in the header.

### Card Content (per slide)

Each card has 4 sections, top to bottom:

#### Section 1: Header
```
① {name}                          {status badge}
{taken_by.name} · {taken_at}
```
- `step_number` as a circled number prefix
- `name` as the card title
- `status` as a badge: `completed` → green ✅
- `taken_by.name` + `taken_at` as subtitle

#### Section 2: Attachments
- If `attachments[]` is empty, skip this section.
- For images (`type === "image"`): show thumbnail grid (tap to open full `url`).
- For documents (`type === "document"` or PDF): show file icon + name (tap to download `url`).
- Show file size: format `size` bytes as KB/MB.

#### Section 3: Approval Steps
- Render `steps[]` as a vertical timeline.
- Each step: `{step_order}. {name} — {status icon} {action_by.name} · {acted_at}`
- Status icons: `approved` → ✅, `rejected` → ❌, `pending` → ⏳
- If `action_by` is null, show `—` (not yet acted).

#### Section 4: Form Data
- Render `form_data` as key-value pairs.
- The keys vary by `form` — see the mapping table below.
- Skip null/empty values.
- Format dates and numbers appropriately.

---

## `form_data` Keys Per Form Type

| `form` value | Display Name | `form_data` fields |
|---|---|---|
| `createProjectNotificationTask` | إنشاء إشعار مشروع | `notification_type`, `feeder_number`, `work_description`, `contractor_name`, `contractor_technical_name`, `contractor_mobile`, `task_latitude`, `task_longitude`, `notes` |
| `updateProjectNotificationTask` | تحديث بيانات الإشعار | Same as create |
| `updateProjectNotificationSiteStatus` | التحديث الدوري لحالة الموقع | `update_date`, `update_time`, `site_status_id`, `current_site_status_id`, `work_stages_completed`, `current_status_description`, `completion_percentage`, `updates_obstacles`, `additional_notes` |
| `projectNotificationFine` | بنود الغرامة | `reason`, `items[]` (each: `name_ar`, `quantity`, `unit_amount`, `total_amount`), `total_amount` |
| `confirmProjectNotificationLocation` | تأكيد التواجد في الموقع | `latitude`, `longitude`, `distance_meters`, `is_inside_location` |
| `projectNotificationWorkStoppageReport` | محضر إيقاف أعمال | `other_notes`, `reasons[]` (each: `reason_name_ar`, `notes`) |
| `projectNotificationWorkResumption` | استئناف الأعمال | `reasons_resolved`, `safety_notes_reviewed`, `site_ready`, `contractor_notified`, `notes` |
| `projectNotificationTaskPostponement` | تأجيل المهمة | `new_task_date`, `new_task_time`, `reason` |
| `endProjectNotificationTask` | إنهاء المهمة | `latitude`, `longitude`, `notes` |

### Arabic labels for common `form_data` keys

| Key | Arabic Label |
|---|---|
| `notification_type` | نوع الإشعار |
| `feeder_number` | رقم المغذي |
| `work_description` | وصف العمل |
| `contractor_name` | اسم المقاول |
| `contractor_technical_name` | الاسم الفني للمقاول |
| `contractor_mobile` | جوال المقاول |
| `notes` | ملاحظات |
| `task_latitude` | خط العرض |
| `task_longitude` | خط الطول |
| `update_date` | تاريخ التحديث |
| `update_time` | وقت التحديث |
| `completion_percentage` | نسبة الإنجاز |
| `current_status_description` | وصف الحالة الحالية |
| `work_stages_completed` | مراحل العمل المنجزة |
| `updates_obstacles` | المعوقات |
| `additional_notes` | ملاحظات إضافية |
| `reason` | السبب |
| `total_amount` | المبلغ الإجمالي |
| `latitude` | خط العرض |
| `longitude` | خط الطول |
| `distance_meters` | المسافة (متر) |
| `is_inside_location` | داخل النطاق |
| `other_notes` | ملاحظات أخرى |
| `reasons_resolved` | تم حل الأسباب |
| `safety_notes_reviewed` | تم مراجعة ملاحظات السلامة |
| `site_ready` | الموقع جاهز |
| `contractor_notified` | تم إبلاغ المقاول |
| `new_task_date` | تاريخ المهمة الجديد |
| `new_task_time` | وقت المهمة الجديد |

---

## Multiple Submissions

Some forms can be submitted **multiple times**. The `items[]` array will contain separate entries for each submission, each with its own `form_data`, `attachments`, and `steps`.

**Example**: 3 site-status updates → 3 slides in the carousel, all with `form: "updateProjectNotificationSiteStatus"` but different `taken_at`, `form_data`, and `attachments`.

**UI approach**:
- Show each as a separate carousel slide.
- Add a counter badge on repeated forms: `تحديث 1`, `تحديث 2`, `تحديث 3`.
- Or group by `form` key with a collapsible sub-carousel.

---

## Summary Header

Use `summary` for the header area above the carousel:

| Field | Usage |
|---|---|
| `summary.total` | Total procedures count (badge: `3 إجراءات`) |
| `summary.last_action` | Name of the most recent procedure |
| `summary.start_date` | Date of the first procedure |
| `summary.progress` | Overall progress percentage → progress bar |

---

## TypeScript Interfaces

```typescript
interface ProceduresResponse {
  items: ProcedureItem[];
  summary: {
    total: number;
    last_action: string | null;
    start_date: string | null;
    progress: number;
  };
}

interface ProcedureItem {
  id: string;
  step_number: number;
  name: string | null;
  icon: string | null;
  percentage: number | null;
  form: string;
  taken_by: { id: string; name: string } | null;
  taken_at: string | null;
  status: string | null;
  steps: ProcedureStep[];
  approved_by: { id: string; name: string } | null;
  attachments: ProcedureAttachment[];
  form_data: Record<string, any> | null;
}

interface ProcedureStep {
  step_order: number;
  name: string | null;
  status: "approved" | "rejected" | "pending";
  action_by: { id: string; name: string } | null;
  acted_at: string | null;
}

interface ProcedureAttachment {
  id: number;
  url: string;
  name: string;
  mime_type: string;
  type: string;
  size: number;
}
```

---

## API Call Example

```typescript
// Fetch procedures for a project notification
const response = await fetch(
  `/api/v1/projects/notifications/${notificationId}/procedures`,
  { headers: { Authorization: `Bearer ${token}` } }
);
const data: ProceduresResponse = await response.json();

// Render carousel
<Carousel>
  {data.items.map(item => (
    <ProcedureCard key={item.id} item={item} />
  ))}
</Carousel>
```

---

## Direct Update API (Dashboard Admin)

> **Endpoint**: `PUT /api/v1/projects/notifications/{id}`
> **Permission**: `PROJECT_NOTIFICATION_UPDATE`
> **Content-Type**: `multipart/form-data`

Updates the notification **directly without any workflow procedure**. Dashboard admins use this to edit notification fields and manage attachments immediately.

### Request Fields

| Field | Type | Required | Description |
|---|---|---|---|
| `notification_number` | string | no | Unique per company |
| `notification_type` | string | no | e.g., `إصلاح عاجل` |
| `severity` | string | no | `منخفض`, `متوسط`, `عالي` |
| `work_type` | string | no | |
| `feeder_number` | string | no | |
| `work_description` | string | no | |
| `contractor_id` | UUID | no | Must exist in `contractors` |
| `contractor_name` | string | no | Auto-filled from contractor if omitted |
| `contractor_number` | string | no | Auto-filled from contractor if omitted |
| `contractor_technical_number` | string | no | |
| `contractor_technical_name` | string | no | |
| `contractor_category` | string | no | |
| `contractor_notes` | string | no | |
| `contractor_mobile` | string | no | |
| `task_latitude` | float | no | -90 to 90 |
| `task_longitude` | float | no | -180 to 180 |
| `location_radius` | int | no | meters |
| `location_link` | string | no | URL |
| `repair_point` | string | no | |
| `assigned_user_id` | UUID | no | Must exist in `users` |
| `selected_distance_meters` | int | no | |
| `task_date` | string | no | `Y-m-d` |
| `duration_hours` | float | no | 0.25–24 |
| `notes` | string | no | |
| `files[]` | file[] | no | New attachments (max 20MB each) |
| `deleted_media_ids[]` | int[] | no | Media IDs to remove from attachments |

### Response

Returns the full notification detail (same as `GET /notifications/{id}`):

```json
{
  "id": "uuid",
  "notification_number": "NTF-2026-00001",
  "attachments": [
    {
      "id": 101,
      "url": "https://s3.example.com/project-notifications/attachments/file.jpg",
      "name": "photo.jpg",
      "mime_type": "image/jpeg",
      "type": "image",
      "size": 245678
    }
  ],
  "procedure_attachments": [ ... ]
}
```

### Frontend Usage

```typescript
// Update notification with new files and delete an old attachment
const formData = new FormData();
formData.append('work_description', 'استبدال الكابل التالف');
formData.append('severity', 'عالي');
formData.append('deleted_media_ids[]', '101');
formData.append('files[]', fileInput.files[0]);

const response = await fetch(
  `/api/v1/projects/notifications/${notificationId}`,
  {
    method: 'PUT',
    headers: { Authorization: `Bearer ${token}` },
    body: formData,
  }
);
const updated = await response.json();
```

### Attachment Management

- **Add files**: Send `files[]` as multipart file uploads. New files are appended to the `attachments` collection.
- **Delete files**: Send `deleted_media_ids[]` with the media `id` values from the current `attachments` array. Only files in the `attachments` collection of this notification can be deleted.
- **Both in one request**: You can add and delete files in the same request — deletions are applied first, then new files are uploaded.
