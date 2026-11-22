# Forum management (إدارة المنتديات) — Frontend Spec

For the **dashboard**. A company can have **many forums** (منتدى). This screen set is where they are created and managed. The employee app only opens a forum and chats inside it — see `docs/FORUM_MOBILE_DEVELOPER_GUIDE.md`.

Sidebar entry: **الموارد البشرية → المنتديات**. Permissions are grouped under `human-resources`, so they show in the Human Resources section of the roles screen.

These routes are live under `api/v1/forum`. The older unscoped routes (`/overview`, `/groups`, `/announcements`) still answer, and anything created there is stored on the company's first forum. New dashboard screens should call the `/forums/...` routes in this document.

A draft forum is published by `POST /forums/{id}` with `"status": "published"`. Archive and restore stay on their own routes.

## Base URL & headers

```
Base URL: {{base_url}}/api/v1/forum
```

| Header | Value |
|--------|-------|
| `Authorization` | `Bearer <auth_token>` |
| `X-Tenant` | `<tenant UUID>` |
| `Accept` | `application/json` |
| `Accept-Language` | `ar` or `en` |
| `Content-Type` | `application/json`, or `multipart/form-data` when a logo, icon, or attachment is uploaded |

Response envelope:

```json
{ "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT", "message": null, "payload": { } }
```

Lists use `SUCCESS_WITH_LIST_PAYLOAD_OBJECTS`, `payload` as an array, and a sibling `pagination` object: `{ "page", "next_page", "last_page", "result_count" }`. Some lists also return a sibling `stats` object for the KPI cards. Errors: `403`, `404`, `422` with `errors` per field.

Booleans are sent as `true` / `false`.

---

## 0. What changed

Previously the company had one implicit forum: groups and announcements belonged to the company. They now belong to a **forum**.

| Concept | Meaning |
|---------|---------|
| Forum | Named space (name, logo, description, who can see it). Example: «منتدى إدارة المشاريع». |
| Forum admin | Employee assigned on that forum. Manages its groups, members, announcements, and reports. |
| Forum member | Employee who can be placed into the forum's groups. |
| Group | Chat room inside one forum. Same send rules as before. |
| Announcement | Published inside one forum, not company-wide. |
| Report | A member flags a message. Reviewed on the dashboard. |

**Who can open this module**

| Caller | Can |
|--------|-----|
| Role that includes `FORUM_LIST` | Open **إدارة المنتديات**, including drafts and archived forums. |
| Role that includes `FORUM_CREATE` | **إنشاء منتدى**. |
| Role that includes the other `FORUM_*` keys | Only the matching screen in the table below. |
| No forum permission | No dashboard entry. They use the mobile app. |

Show or hide every management button from the permissions already returned by **`GET /api/v1/users/me`** (`permissions[].key`). The same list is `GET /api/v1/users/my-permissions`. Do not call a separate forum permissions endpoint.

| Button / screen | `permissions[].key` |
|-----------------|---------------------|
| إنشاء منتدى | `FORUM_CREATE` |
| إدارة المنتديات (list) | `FORUM_LIST` |
| Open a forum | `FORUM_VIEW` |
| حفظ الإعدادات | `FORUM_UPDATE` |
| أرشفة المنتدى | `FORUM_ARCHIVE` |
| استعادة المنتدى | `FORUM_RESTORE` |
| إنشاء مجموعة | `FORUM_GROUP_CREATE` |
| تعديل المجموعة / صلاحيات الإرسال | `FORUM_GROUP_UPDATE` |
| أرشفة مجموعة | `FORUM_GROUP_ARCHIVE` |
| استعادة مجموعة | `FORUM_GROUP_RESTORE` |
| حذف مجموعة | `FORUM_GROUP_DELETE` |
| أعضاء المنتدى أو المجموعة | `FORUM_MEMBER_LIST` to open, `FORUM_MEMBER_UPDATE` to save |
| أدمنز المنتدى | `FORUM_ADMIN_LIST` to open, `FORUM_ADMIN_UPDATE` to save |
| نشر إعلان | `FORUM_ANNOUNCEMENT_CREATE` |
| تعديل إعلان | `FORUM_ANNOUNCEMENT_UPDATE` |
| حذف إعلان | `FORUM_ANNOUNCEMENT_DELETE` |
| البلاغات | `FORUM_REPORT_LIST` |
| اعتماد الإجراء | `FORUM_REPORT_UPDATE` |
| سجل النشاط | `FORUM_ACTIVITY_LIST` |

Assign these keys to a role, then run `php artisan permissions:update-names --create-missing --force` once so they exist in the database. A user sees a key in `me` only after that role is assigned.

`can_create` on `GET /forums?scope=manage` repeats `FORUM_CREATE` for convenience. The me response is the source of truth.

---

## 1. Shared objects

### Forum

```json
{
  "id": "uuid",
  "name": "منتدى إدارة المشاريع",
  "description": "متابعة أعمال المشاريع والتنسيق بين الفرق",
  "logo_url": "https://...|null",
  "visibility": "company",
  "branch_ids": [],
  "project_ids": [],
  "status": "active",
  "groups_count": 6,
  "members_count": 48,
  "admins_count": 2,
  "open_reports_count": 1,
  "last_activity_at": "2026-11-22T09:18:00.000000Z",
  "my_role": "admin",
  "created_at": "2026-11-01T08:00:00.000000Z"
}
```

| Field | Values |
|-------|--------|
| `visibility` | `company` = كل موظفي الشركة. `scoped` = وحدات أو مشاريع محددة (`branch_ids` and/or `project_ids` required). |
| `status` | `draft` (wizard not published), `active`, `archived`. |
| `my_role` | `owner` (company admin), `admin` (forum admin), `null`. |

`branch_ids` are company-structure units (الإدارات / الفروع). `project_ids` are projects. Reuse the pickers the dashboard already uses for those two lists.

### Forum admin

```json
{
  "user_id": "uuid",
  "name": "أحمد سامي",
  "email": "ahmad@example.com",
  "job_title": "مدير المشروع",
  "branch_name": "إدارة المشاريع",
  "can_manage_groups": true,
  "can_publish_announcements": true
}
```

A forum admin with `can_manage_groups` can create groups, edit members, and change send settings. `can_publish_announcements` can publish and delete announcements. Both can review reports. A company admin can do all of it on every forum.

### Group

Same object as the mobile guide, plus:

| Field | Meaning |
|-------|---------|
| `forum_id` | Parent forum |
| `status` | `active` or `archived`. Archived groups stay in the dashboard table and disappear from mobile. |
| `category` | Optional label from «فئة المجموعة» |
| `show_name_in_chat` | When `true`, the group name is shown in the chat header |

`send_permission`: `admins_only` (الأدمنز فقط) · `all_members` (كل الأعضاء) · `specific_members` (موظفون محددون). `allow_images`, `allow_voice`, `allow_files` apply to everyone who is allowed to send. Text is always allowed.

### Announcement

```json
{
  "id": "uuid",
  "forum_id": "uuid",
  "title": "تحديث مواعيد الدوام",
  "body": "يُرجى مراجعة جدول الدوام المحدث قبل بداية الأسبوع القادم.",
  "status": "published",
  "notify": true,
  "author": { "id": "uuid", "name": "الإدارة العامة" },
  "attachments": [
    { "id": 1, "url": "https://...", "name": "جدول-الدوام.pdf", "mime_type": "application/pdf", "type": "file", "size": 1468006 }
  ],
  "created_at": "2026-11-22T09:18:00.000000Z"
}
```

`status`: `draft` or `published`. Attachment `type` is `image`, `file`, or `voice`.

---

## 2. Screen map

Inside a forum the page shell is one tab bar. Highlight the active tab in pink.

**نظرة عامة · المجموعات · الأعضاء · الأدمنز · الإعلانات · البلاغات · الإعدادات · سجل النشاط**

| Screen | Route suggestion | API |
|--------|------------------|-----|
| إدارة المنتديات | `/forums` | `GET /forums?scope=manage` |
| إنشاء منتدى (4 steps) | modal on `/forums` | `POST /forums` |
| نظرة عامة | `/forums/:id` | `GET /forums/{id}` |
| المجموعات | `/forums/:id/groups` | `GET /forums/{id}/groups` |
| بيانات المجموعة | modal | `POST /forums/{id}/groups`, `POST /forums/{id}/groups/{groupId}` |
| أعضاء المجموعة | `/forums/:id/groups/:groupId/members` | `GET/PUT .../members` |
| صلاحيات المحادثة | `/forums/:id/groups/:groupId/permissions` | `PUT .../send-settings` |
| الأدمنز | `/forums/:id/admins` | `GET/PUT /forums/{id}/admins` |
| الأعضاء | `/forums/:id/members` | `GET/PUT /forums/{id}/members`, `PUT .../members/groups` |
| الإعلانات | `/forums/:id/announcements` | `GET /forums/{id}/announcements` |
| إنشاء إعلان | `/forums/:id/announcements/new` | `POST /forums/{id}/announcements` |
| البلاغات | `/forums/:id/reports` | `GET /forums/{id}/reports` |
| مراجعة بلاغ | modal | `POST /forums/{id}/reports/{reportId}/resolve` |
| الإعدادات | `/forums/:id/settings` | `POST /forums/{id}`, archive / restore |
| سجل النشاط | `/forums/:id/activity` | `GET /forums/{id}/activity` |

Employee search on every picker: `GET /employees?search=`. Each item is `{ "id", "name", "email", "job_title", "branch_name", "avatar_url" }`.

---

## 3. إدارة المنتديات (list)

Header **إدارة المنتديات**. Primary button **إنشاء منتدى** when `can_create` is true.

Four KPI cards, from `stats`:

| Card | Key |
|------|-----|
| إجمالي المنتديات | `stats.total` |
| منتديات نشطة | `stats.active` |
| بلاغات مفتوحة | `stats.open_reports` |
| منتديات مؤرشفة | `stats.archived` |

Filters (query string): `search`, `status` (`draft|active|archived`), `visibility` (`company|scoped`), `branch_id`.

Table, RTL. Columns:

| Column | Source |
|--------|--------|
| اسم المنتدى | `name` + `logo_url` |
| الوحدة / النطاق | «كل الشركة», or the branch/project names |
| المجموعات | `groups_count` |
| الأعضاء | `members_count` |
| الحالة | `status` → مسودة / نشط / مؤرشف |
| آخر نشاط | `last_activity_at` |
| الإجراءات | **إدارة** when `FORUM_VIEW`. أرشفة when `FORUM_ARCHIVE`. استعادة when `FORUM_RESTORE` |

Pagination under the table.

### `GET /forums?scope=manage&page=1&per_page=15&search=&status=&visibility=&branch_id=`

`payload` is an array of forum objects. Sibling keys:

```json
{
  "can_create": true,
  "stats": { "total": 9, "active": 6, "open_reports": 3, "archived": 1 },
  "pagination": { "page": 1, "next_page": null, "last_page": 1, "result_count": 9 }
}
```

`scope=manage` is the dashboard. `scope=visible` is the mobile list (active forums the caller may open) and is documented in the mobile guide.

---

## 4. إنشاء منتدى — four steps, one request

The wizard keeps its state in the client. Nothing is stored until **حفظ كمسودة** or **نشر المنتدى**.

Steps, in order: **البيانات الأساسية → الأدمنز → الأعضاء → المراجعة**. Completed steps show a check. **إلغاء** closes the modal. **السابق** / **التالي** move between steps.

### Step 1 — البيانات الأساسية

| Field | Required | Notes |
|-------|----------|-------|
| اسم المنتدى | yes | |
| الوصف | yes | |
| وصف إضافي | no | Longer text, stored in `description` after the short description, or send as `details` |
| شعار المنتدى | no | Image, PNG or JPG. Field name `logo` |
| نطاق الظهور | yes | Two cards. **كل موظفي الشركة** → `visibility=company`. **وحدات أو مشاريع محددة** → `visibility=scoped`, then show the branch and project multi-selects |

`details` is optional extra text. If you only have one description box filled, send `description` and omit `details`.

### Step 2 — تعيين الأدمنز

Search, then a checklist. Selected rows are marked **أدمن**. At least one admin is required to publish. Pass them as `admins: [{ "user_id", "can_manage_groups": true, "can_publish_announcements": true }]`. Default both flags to `true` on this step; the أدمنز tab edits them later.

Helper text: forum admins manage groups, members, and announcements.

### Step 3 — إضافة الأعضاء

Search plus filters **الإدارة** (`branch_id`) and **المشروع** (`project_id`) — these only filter the picker, they are not saved. Show «تم تحديد N موظفاً». Selected rows are marked **عضو**. Members are optional. Pass `member_ids: ["uuid"]`.

A purple note can explain that selected employees are the forum members and can then be distributed to groups.

### Step 4 — مراجعة ونشر

Read-only summary:

- **البيانات الأساسية** — name, description, visibility label
- **الأدمنز والأعضاء** — admin count, member count, first names
- **إشعار الموظفين** — switch, default on. `notify_members`
- Note: the forum appears to employees in scope after publish

Buttons:

| Button | Request |
|--------|---------|
| نشر المنتدى | `POST /forums` with `status: "published"` |
| حفظ كمسودة | `POST /forums` with `status: "draft"` |
| السابق | back to step 3, no request |

A draft is listed with status مسودة and is hidden on mobile. Opening **إدارة** on a draft returns to this wizard with the saved values (`GET /forums/{id}`).

### `POST /forums`

`multipart/form-data` when `logo` is present, otherwise JSON.

```json
{
  "name": "منتدى إدارة المشاريع",
  "description": "متابعة أعمال المشاريع والتنسيق بين الفرق",
  "details": null,
  "visibility": "scoped",
  "branch_ids": ["uuid"],
  "project_ids": ["uuid"],
  "admins": [
    { "user_id": "uuid", "can_manage_groups": true, "can_publish_announcements": true }
  ],
  "member_ids": ["uuid", "uuid"],
  "notify_members": true,
  "status": "published"
}
```

`422` when `name` or `description` is empty, `visibility=scoped` has no branch and no project, or `status=published` has no admin.

Publishing with `notify_members: true` sends the mobile push `forum_published` to employees who can see the forum.

---

## 5. نظرة عامة

Breadcrumb: لوحة العمل / {اسم المنتدى}. Title is the forum name, subtitle is `description`.

Tabs as in section 2. **نظرة عامة** is active.

KPI row: `groups_count`, `members_count`, `admins_count`, `open_reports_count`.

Three rows under **إدارة المنتدى**, each with **إدارة**:

| Row | Goes to |
|-----|---------|
| المجموعات | tab المجموعات |
| الأعضاء | tab الأعضاء |
| البلاغات | tab البلاغات |

Secondary actions: **معاينة المنتدى على الموبايل** (no API — a static preview is enough) and **سجل النشاط** (tab سجل النشاط).

### `GET /forums/{id}`

`payload` is the forum object plus:

```json
{
  "details": null,
  "admins": [ { "user_id": "uuid", "name": "أحمد سامي", "can_manage_groups": true, "can_publish_announcements": true } ],
  "member_ids": ["uuid"]
}
```

`403` if the caller is neither a company admin nor an admin of this forum.

---

## 6. المجموعات

Title **مجموعات المنتدى**. Button **إنشاء مجموعة**.

KPI cards (sibling `stats` on the list): إجمالي المجموعات، مجموعات نشطة، مجموعات مؤرشفة. A fourth card can show how many groups are `admins_only`.

Filters: `search`, `status` (`active|archived`), `send_permission`.

| Column | Source |
|--------|--------|
| اسم المجموعة | `name` |
| الأعضاء | `members_count` |
| الأدمنز | `admins_count` |
| صلاحية الإرسال | `send_permission` |
| الحالة | `status` |
| الإجراءات | **إدارة** opens the group (members page). Menu: تعديل البيانات، صلاحيات الإرسال، أرشفة |

### `GET /forums/{forumId}/groups?search=&status=&send_permission=&page=&per_page=`

### Create / edit — modal بيانات المجموعة

| Field | API |
|-------|-----|
| اسم المجموعة | `name` required |
| وصف المجموعة | `description` |
| صورة المجموعة | `icon` file, PNG/JPG. Use POST (multipart) when a file is attached |
| إظهار اسم المجموعة في الدردشة | `show_name_in_chat` |
| فئة المجموعة | `category` optional |

Primary button saves and continues to the members page. **إلغاء** closes the modal.

### `POST /forums/{forumId}/groups`

Multipart. Also accepts `send_permission`, `allow_images`, `allow_voice`, `allow_files`. Defaults: `all_members` and all attachment types `true`, `status=active`, `show_name_in_chat=true`.

### `POST /forums/{forumId}/groups/{groupId}`

Same fields, all optional. Use POST so an icon can be uploaded. `PUT` is accepted when there is no file.

### `POST /forums/{forumId}/groups/{groupId}/archive` and `POST .../restore`

Sets `status`. No body.

---

## 7. أعضاء المجموعة وتعيين أدمنز

Page title: أعضاء {اسم المجموعة}. Reached after create, or from **إدارة** on the groups table.

Filters above the table are local: search, الإدارة, الدور (`admin|member`).

| Column | Source |
|--------|--------|
| اسم الموظف | `name` |
| رقم الموظف | `employee_number` when the user has one |
| الإدارة | `branch_name` |
| الدور | `role` → أدمن / عضو |
| الحالة | active membership |
| الإجراءات | تعيين كأدمن، إزالة |

**إضافة أعضاء** opens the employee picker (`GET /employees`). **حفظ التغييرات** sends the full list. The server **replaces** the member list.

### `GET /forums/{forumId}/groups/{groupId}/members`

```json
{ "user_id": "uuid", "name": "أحمد سامي", "employee_number": "EMP-1024", "branch_name": "إدارة المشاريع", "role": "admin", "can_send": true }
```

### `PUT /forums/{forumId}/groups/{groupId}/members`

```json
{ "members": [ { "user_id": "uuid", "role": "admin" }, { "user_id": "uuid", "role": "member" } ] }
```

`role` is `admin` or `member`. Group admins can always send. `can_send` for ordinary members is owned by the next screen, not this one.

---

## 8. صلاحيات المحادثة والمرفقات

Title: صلاحيات محادثة {اسم المجموعة}.

**من يمكنه الإرسال** — one of three radio cards:

| Card | `send_permission` |
|------|-------------------|
| الأدمنز فقط | `admins_only` |
| كل الأعضاء | `all_members` |
| موظفون محددون | `specific_members` |

When `specific_members` is selected, show the group members as a checklist. Checked ids go in `allowed_user_ids`. Group admins are always included; the API rejects ids that are not members.

**الوسائط المسموحة** — three switches: الصور `allow_images`, الرسائل الصوتية `allow_voice`, الملفات والمرفقات `allow_files`.

A summary line under the cards repeats the current choice (for example «الإرسال للأعضاء · الصور والملفات»). Save is explicit.

### `PUT /forums/{forumId}/groups/{groupId}/send-settings`

```json
{
  "send_permission": "specific_members",
  "allowed_user_ids": ["uuid"],
  "allow_images": false,
  "allow_voice": true,
  "allow_files": true
}
```

---

## 9. أدمنز المنتدى

Table of forum admins: اسم الموظف، رقم الموظف، الإدارة، المجموعات التي يديرها، الحالة.

**تعيين أدمن جديد** card:

- Employee search (`GET /employees`)
- Switch **إدارة المجموعات والأعضاء** → `can_manage_groups`
- Switch **نشر الإعلانات** → `can_publish_announcements`
- **تعيين الأدمن** adds the row locally; **حفظ** persists

### `GET /forums/{forumId}/admins`

### `PUT /forums/{forumId}/admins`

Replaces the admin list. The creator/company side is not removed by omitting them; company admins always keep access.

```json
{
  "admins": [
    { "user_id": "uuid", "can_manage_groups": true, "can_publish_announcements": true }
  ]
}
```

---

## 10. أعضاء المنتدى وتوزيعهم على المجموعات

This is the forum-level member list, not a single group.

Filters: search, الإدارة, المجموعة.

| Column | Source |
|--------|--------|
| اسم الموظف | `name` |
| رقم الموظف | `employee_number` |
| الإدارة | `branch_name` |
| الدور | `forum_role` (`admin` or `member`) |
| المجموعات | `groups: [{ "id", "name", "role" }]` |
| الحالة | `status` |
| الإجراءات | إزالة من المنتدى |

Two cards at the bottom:

| Card | Action |
|------|--------|
| إضافة أعضاء | Employee picker, then `PUT /forums/{id}/members` with the full `member_ids` list (replace) |
| توزيع الموظف على المجموعات | Pick a member, check groups, choose a role per group, then the request below |

### `GET /forums/{forumId}/members?search=&branch_id=&group_id=&page=&per_page=`

### `PUT /forums/{forumId}/members`

```json
{ "member_ids": ["uuid", "uuid"] }
```

Removing a member removes them from every group in the forum.

### `PUT /forums/{forumId}/members/{userId}/groups`

```json
{
  "groups": [
    { "group_id": "uuid", "role": "member" },
    { "group_id": "uuid", "role": "admin" }
  ]
}
```

Replaces that employee's group memberships inside this forum. Groups omitted here drop the employee from that group.

---

## 11. الإعلانات العامة

Title **الإعلانات العامة**. Button **إعلان جديد**.

KPI cards from `stats`: إجمالي الإعلانات، منشورة، مسودات، بانتظار (drafts). Use `stats.total`, `stats.published`, `stats.drafts`.

Filters: `search`, `status` (`draft|published`), `author_id`.

| Column | Source |
|--------|--------|
| العنوان | `title` |
| الناشر | `author.name` |
| التاريخ | `created_at` |
| الحالة | `status` → مسودة / منشور |
| الإجراءات | تعديل (drafts), حذف |

### `GET /forums/{forumId}/announcements?page=&per_page=&search=&status=`

Dashboard rows do not need `is_read`. Mobile uses the same path and does receive `is_read` and `unread_count` (see the mobile guide).

### `DELETE /forums/{forumId}/announcements/{id}`

Allowed for a published announcement or a draft.

---

## 12. إنشاء ومعاينة إعلان عام

Two columns.

**محتوى الإعلان** (the form):

| Field | Required |
|-------|----------|
| عنوان الإعلان `title` | yes |
| نص الإعلان `body` | yes |
| إضافة صورة / إرفاق ملف / تسجيل صوتي | no, one or more `attachments[]` |
| إشعار الموظفين `notify` | switch, default on. Copy: إرسال إشعار عند نشر الإعلان |

**كيف سيظهر للموظف** is a live preview built in the client from the form. Show the company name, the title, the body, each attachment (PDF row, voice row, image), and the caption «عرض مثالي في تطبيق الموظفين». Do not call the API for the preview.

| Button | Body |
|--------|------|
| نشر الإعلان | `status: "published"` |
| حفظ كمسودة | `status: "draft"` |
| إلغاء الإعلان | leave the page, no request |

### `POST /forums/{forumId}/announcements`

Multipart.

| Field | Notes |
|-------|-------|
| `title` | required |
| `body` | required |
| `status` | `published` or `draft` |
| `notify` | `true` / `false`. Ignored for drafts |
| `attachments[]` | image ≤ 10MB, file ≤ 20MB, audio ≤ 20MB (`m4a`, `aac`, `mp3`, `ogg`, `wav`, `webm`) |

`notify: true` on publish sends push `forum_announcement` to members who can see the forum, except the author.

Editing a draft is `POST /forums/{forumId}/announcements/{id}` with the same fields. Publishing a draft is the same request with `status: "published"`.

---

## 13. بلاغات المنتديات

Employees file a report from the mobile chat. This screen only reviews them.

KPI cards: بلاغات جديدة `new`, قيد المراجعة `in_review`, تمت المعالجة `resolved`, مغلقة `closed`.

Filters: `search`, `status`, `group_id`, المنتدى is already the page forum.

| Column | Source |
|--------|--------|
| البلاغ | `reason` |
| المُبلِّغ | `reporter.name` |
| المجموعة | `group_name` |
| الحالة | `status` |
| التاريخ | `created_at` |
| الإجراءات | **مراجعة** opens the modal |

### `GET /forums/{forumId}/reports?page=&per_page=&search=&status=&group_id=`

```json
{
  "id": "uuid",
  "group_id": "uuid",
  "group_name": "مهندسو المواقع",
  "message_id": "uuid",
  "message": {
    "id": "uuid",
    "type": "text",
    "body": "نص الرسالة المُبلَّغ عنها",
    "sender": { "id": "uuid", "name": "سارة إبراهيم" },
    "attachments": [],
    "created_at": "2026-11-22T09:18:00.000000Z"
  },
  "reason": "محتوى غير مناسب",
  "reporter": { "id": "uuid", "name": "أحمد سامي" },
  "status": "new",
  "created_at": "2026-11-22T10:00:00.000000Z"
}
```

`status`: `new`, `in_review`, `resolved` (content kept), `closed` (message removed). Opening the modal sends no request. The first resolve moves it out of `new`.

### مراجعة بلاغ — modal

Show **بيانات البلاغ** (reporter, group, sender, date, status) and **الرسالة المُبلَّغ عنها** (body + attachment, with a link to the file).

**قرار المراجعة** — one option:

| Option | `action` |
|--------|----------|
| إخفاء الرسالة والمرفق | `delete_message` |
| إغلاق البلاغ مع إبقاء المحتوى | `keep_message` |

**سبب الإجراء** → `note`, required.

**اعتماد الإجراء** submits. **رجوع للقائمة** closes the modal.

### `POST /forums/{forumId}/reports/{reportId}/resolve`

```json
{ "action": "delete_message", "note": "مخالفة لسياسة المنتدى" }
```

`delete_message` deletes the chat message for everyone (mobile receives `forum.message.deleted`) and sets the report to `closed`. `keep_message` sets it to `resolved` and leaves the message. Both write an activity row.

---

## 14. إعدادات المنتدى والظهور والأرشفة

Tab **الإعدادات**. Button **حفظ الإعدادات**.

**بيانات المنتدى:** `name`, `description` (and `details` if you collected it). Logo can be changed with the same `logo` file field.

**نطاق الظهور:** the same two cards as the wizard. Switching to `scoped` reveals the branch and project multi-selects. Switching back to `company` clears them on save.

**أرشفة المنتدى:**

| Button | Request | Effect |
|--------|---------|--------|
| أرشفة المنتدى | `POST /forums/{id}/archive` | `status=archived`. Hidden on mobile. Groups and messages are kept. |
| استعادة المنتدى | `POST /forums/{id}/restore` | `status=active`. Visible again to people in scope. |

Confirm archive in a dialog before the request. Show the current status and `archived_at` when it is archived.

### `POST /forums/{id}`

Multipart when `logo` is sent. Every field is optional.

```json
{
  "name": "منتدى إدارة المشاريع",
  "description": "...",
  "details": null,
  "visibility": "company",
  "branch_ids": [],
  "project_ids": []
}
```

Do not send `status` here. Archive and restore are the two requests above.

---

## 15. سجل النشاط والتغييرات

Read-only. Filters: `search`, `actor_id`, `action`, `from`, `to` (dates).

| Column | Source |
|--------|--------|
| الإجراء | `action_label` |
| الموظف المسؤول | `actor.name` |
| القسم المتأثر | `target_label` |
| التاريخ والوقت | `created_at` |
| التفاصيل | **عرض** expands the row |

The expanded row shows **قبل التعديل** (`before`) and **بعد التعديل** (`after`). Render the keys that are present; hide the block when both are `null` (for example a publish event).

### `GET /forums/{forumId}/activity?page=&per_page=&search=&actor_id=&action=&from=&to=`

```json
{
  "id": "uuid",
  "action": "group.send_settings_updated",
  "action_label": "تغيير صلاحية الإرسال",
  "actor": { "id": "uuid", "name": "أحمد سامي" },
  "target_type": "group",
  "target_id": "uuid",
  "target_label": "الدعم الفني",
  "before": { "send_permission": "all_members" },
  "after": { "send_permission": "admins_only" },
  "created_at": "2026-11-22T09:18:00.000000Z"
}
```

`action` values the UI should label:

| `action` | Label |
|----------|-------|
| `forum.published` | نشر المنتدى |
| `forum.updated` | تعديل إعدادات المنتدى |
| `forum.archived` | أرشفة المنتدى |
| `forum.restored` | استعادة المنتدى |
| `forum.admins_updated` | تعديل أدمنز المنتدى |
| `forum.members_updated` | تعديل أعضاء المنتدى |
| `group.created` | إنشاء مجموعة |
| `group.updated` | تعديل مجموعة |
| `group.archived` | أرشفة مجموعة |
| `group.members_updated` | تعديل أعضاء مجموعة |
| `group.send_settings_updated` | تغيير صلاحية الإرسال |
| `announcement.published` | نشر إعلان |
| `announcement.deleted` | حذف إعلان |
| `report.resolved` | معالجة بلاغ |

`action_label` is already localized. Prefer it for the cell, and use `action` for the filter value.

---

## 16. Status, empty, and error states

| Situation | UI |
|-----------|----|
| List `result_count = 0` | Empty state inside the table, keep the KPI cards and the create button |
| `403` | The forum is not theirs. Send them back to `/forums` |
| `422` | Show `errors` under the matching field. On the wizard, jump to the step that owns the first error |
| `404` on a group or announcement | It was deleted. Return to its list |
| Archive | Confirm, then refresh the forum header so the status chip flips to مؤرشف |
| Save draft | Toast and stay on the list; the new row is مسودة |

Chat itself is not a dashboard screen. Members read and send from the mobile app.
