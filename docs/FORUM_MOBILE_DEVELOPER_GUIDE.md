# Forum (المنتدى) — Mobile Developer Guide

A company has **many forums**. Each forum has its own announcements and chat groups (text, images, voice, files).

The employee app lists the forums this user can see, then opens one. Management buttons (create a forum, add members, create a group, publish an announcement, and the rest) are shown only when the matching flag in `capabilities` is true. The same actions exist on the dashboard — see `docs/FORUM_DASHBOARD_FRONTEND_SPEC.md`.

Backend: Laravel REST + **Laravel Reverb** (WebSocket) + **FCM**.

Postman: `Forum_API.postman_collection.json` (folder **Forums**).

The paths in this guide are live. The older unscoped routes still work and read the company's first forum. New builds should call `/forums` and `/forums/{forumId}/...`.

---

## What changed

Groups and announcements used to belong to the company. They now belong to a forum.

| Before | Now |
|--------|-----|
| `GET /overview` | `GET /forums` then `GET /forums/{forumId}/overview` |
| `GET /groups`, `GET /announcements` | Same resources, under `/forums/{forumId}/...` |
| Push data had `group_id` / `announcement_id` | Also `forum_id`, so the tap opens the right forum |
| Announcements channel was per company | `forum.announcements.{forumId}` |
| One admin managed the only forum | A forum admin is assigned per forum. Management UI is the dashboard |

Message shape, send rules, and the group chat channel are unchanged apart from `forum_id`.

---

## 1. Common

**Base URL:** `{{base_url}}/api/v1/forum`

**Required headers (all endpoints):**

| Header | Value |
|--------|-------|
| `Authorization` | `Bearer {jwt}` |
| `X-Tenant` | company (tenant) id |
| `Accept` | `application/json` |
| `Accept-Language` | `ar` / `en` |

**Response envelope:**

```json
{ "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT", "message": null, "payload": { } }
```

Lists use `SUCCESS_WITH_LIST_PAYLOAD_OBJECTS` and `payload` is an array, plus `pagination`: `{ "page", "next_page", "last_page", "result_count" }`. Errors: `403` (not allowed), `404`, `422` (`errors` per field).

**Who sees a forum on mobile**

`GET /forums` (the default, `scope=visible`) returns forums where all of these are true:

- `status` is `active` (drafts and archived forums are hidden)
- `visibility` is `company` (كل موظفي الشركة), **or** `scoped` and the user sits in one of the forum's branches/projects, **or** the user is a forum member or forum admin

**Roles inside a forum**

| | Mobile |
|--|--------|
| Employee | Reads announcements. Reads groups they belong to. Sends when the group allows it. Can report a message. |
| User with a forum permission | Same chat access, plus the button whose flag in `capabilities` is true. |

---

## 2. Forums list — `GET /forums`

First screen. One row per forum the user can open.

```json
{
  "id": "uuid",
  "name": "منتدى إدارة المشاريع",
  "description": "متابعة أعمال المشاريع والتنسيق بين الفرق",
  "logo_url": "https://...|null",
  "visibility": "company",
  "unread_count": 4,
  "groups_count": 6,
  "last_message": {
    "group_id": "uuid",
    "group_name": "مهندسو المواقع",
    "type": "text",
    "preview": "تم رفع تقرير الزيارة",
    "sender": { "id": "uuid", "name": "أحمد" },
    "created_at": "2026-11-22T09:18:00.000000Z"
  }
}
```

`unread_count` is announcements the user has not read **plus** unread messages in their groups, inside this forum. `last_message` is the newest preview across their groups, or `null`.

Sort: most recent `last_message.created_at` first. Forums with no messages follow, by `name`.

The response also has `capabilities` next to `pagination`. Show a button only when its flag is true. Do not read `GET /users/me`.

```json
{
  "can_create": false,
  "can_update": false,
  "can_archive": false,
  "can_restore": false,
  "can_manage_members": false,
  "can_manage_admins": false,
  "can_create_groups": false,
  "can_update_groups": false,
  "can_delete_groups": false,
  "can_archive_groups": false,
  "can_restore_groups": false,
  "can_publish_announcements": false,
  "can_delete_announcements": false,
  "can_review_reports": false
}
```

The same object is on `GET /forums/{forumId}/overview` and `GET /forums/{forumId}/access`. A call without the permission returns `403`.

Tapping a row opens that forum's home and subscribes to its announcements channel (section 5).

### Create a forum — `POST /forums`

Shown only when `can_create` is true. `multipart/form-data` when `logo` is attached, otherwise JSON. `403` if the role does not include `FORUM_CREATE`.

| Field | Required | Notes |
|-------|----------|-------|
| `name` | yes | |
| `description` | yes | |
| `visibility` | yes | `company` (كل موظفي الشركة) or `scoped` |
| `branch_ids` | when `scoped` | at least one branch **or** one project |
| `project_ids` | when `scoped` | |
| `admins` | when `status` is `published` | `[ { "user_id", "can_manage_groups": true, "can_publish_announcements": true } ]`. Include the current user if they should manage it |
| `member_ids` | no | employee ids |
| `notify_members` | no | default false if omitted. `true` sends push `forum_published` |
| `status` | no | `published` (default for this button) or `draft` |
| `logo` | no | PNG or JPG, max 5MB |

```json
{
  "name": "منتدى إدارة المشاريع",
  "description": "متابعة أعمال المشاريع والتنسيق بين الفرق",
  "visibility": "company",
  "admins": [
    { "user_id": "current-user-uuid", "can_manage_groups": true, "can_publish_announcements": true }
  ],
  "member_ids": [],
  "notify_members": true,
  "status": "published"
}
```

`422` when the name or description is empty, `scoped` has no branch and no project, or `published` has no admin. On success, prepend the forum and open it. A `draft` does not appear in `GET /forums` until it is published.

### Other management actions

Employee search for every picker: `GET /employees?search=` → `[ { "id", "name", "email", "job_title", "branch_name" } ]`.

| Button | Flag | Request |
|--------|------|---------|
| إضافة أعضاء للمنتدى | `can_manage_members` | `PUT /forums/{forumId}/members` |
| إضافة أعضاء لمجموعة | `can_manage_members` | `PUT /forums/{forumId}/groups/{groupId}/members` |
| تعيين أدمنز | `can_manage_admins` | `PUT /forums/{forumId}/admins` |
| إنشاء مجموعة | `can_create_groups` | `POST /forums/{forumId}/groups` |
| تعديل مجموعة / صلاحيات الإرسال | `can_update_groups` | `POST /forums/{forumId}/groups/{groupId}` and `PUT .../send-settings` |
| أرشفة / استعادة مجموعة | `can_archive_groups` / `can_restore_groups` | `POST .../groups/{groupId}/archive` or `.../restore` |
| حذف مجموعة | `can_delete_groups` | `DELETE /forums/{forumId}/groups/{groupId}` |
| تعديل المنتدى | `can_update` | `POST /forums/{forumId}` |
| أرشفة / استعادة المنتدى | `can_archive` / `can_restore` | `POST /forums/{forumId}/archive` or `.../restore` |
| نشر إعلان | `can_publish_announcements` | `POST /forums/{forumId}/announcements` |
| حذف إعلان | `can_delete_announcements` | `DELETE /forums/{forumId}/announcements/{id}` |
| مراجعة بلاغ | `can_review_reports` | `POST /forums/{forumId}/reports/{reportId}/resolve` |

**إضافة أعضاء للمنتدى.** Load the current ids with `GET /forums/{forumId}/members`, then send the full list (old ids + the new ones). The server replaces the list. An id you omit is removed from the forum and from every group in it.

```json
{ "member_ids": ["uuid-existing", "uuid-new"] }
```

**إضافة أعضاء لمجموعة.** Same idea, scoped to that group. Each person has a role.

```json
{
  "members": [
    { "user_id": "uuid-1", "role": "admin" },
    { "user_id": "uuid-2", "role": "member" }
  ]
}
```

`role` is `admin` or `member`. This replaces the members of that group only.

**إنشاء مجموعة** (`multipart` when `icon` is attached):

```json
{
  "name": "مهندسو المواقع",
  "description": "تقارير الزيارات",
  "show_name_in_chat": true,
  "send_permission": "all_members"
}
```

**نشر إعلان** (`multipart` when `attachments[]` is attached):

```json
{
  "title": "تحديث مواعيد الدوام",
  "body": "يُرجى مراجعة الجدول قبل بداية الأسبوع.",
  "status": "published",
  "notify": true
}
```

---

## 3. Permission model (per group)

Every group item contains an `access` object. **Drive the chat UI from it:**

```json
"access": {
  "group_id": "uuid",
  "is_member": true,
  "my_role": "member",
  "can_read": true,
  "can_send": true,
  "allowed_message_types": ["text", "image", "voice", "file"],
  "can_manage": false
}
```

`my_role` is `admin`, `member`, or `null` (not a member). `can_read: false` → lock icon, «الانضمام عن طريق الأدمن». `can_send: false` → read-only banner, «المجموعة للقراءة فقط · الإرسال للأدمنز».

These flags already on the payload are the role permissions. Compare them as booleans. Do not read `GET /users/me` on mobile to decide them.

| Attribute | Where | True when the role has |
|-----------|--------|------------------------|
| `capabilities.can_create` | `GET /forums`, overview, access | `FORUM_CREATE` |
| `capabilities.can_manage_members` | same | `FORUM_MEMBER_UPDATE` — إضافة أعضاء للمنتدى أو لمجموعة |
| `capabilities.can_create_groups` | same | `FORUM_GROUP_CREATE` |
| `capabilities.can_update_groups` | same | `FORUM_GROUP_UPDATE` |
| `capabilities.can_manage_admins` | same | `FORUM_ADMIN_UPDATE` |
| `capabilities.can_publish_announcements` | same | `FORUM_ANNOUNCEMENT_CREATE` |
| `capabilities.can_review_reports` | same | `FORUM_REPORT_UPDATE` |
| `is_forum_admin` | overview and access | any forum management permission |
| `access.can_manage` | each group | `FORUM_GROUP_UPDATE` |

`can_update`, `can_archive`, `can_restore`, `can_delete_groups`, `can_archive_groups`, `can_restore_groups`, and `can_delete_announcements` are in the same `capabilities` object. The full map is in section 2.

`send_permission` on the group:

| Value | Meaning |
|-------|---------|
| `admins_only` | Group admins and forum admins only |
| `all_members` | Every member |
| `specific_members` | Group admins, plus members with `can_send: true` |

`allow_images`, `allow_voice`, `allow_files` toggle attachment types for **everyone allowed to send**. Text is always allowed.

`GET /forums/{forumId}/access` returns the current user's access on **all groups of that forum** in one call.

---

## 4. Endpoints

### 4.1 Forum home — `GET /forums/{forumId}/overview`

Announcements card + «مجموعاتي» + «مجموعات أخرى» for **this forum only**.

```json
{
  "forum": {
    "id": "uuid",
    "name": "منتدى إدارة المشاريع",
    "description": "...",
    "logo_url": null
  },
  "is_forum_admin": false,
  "capabilities": { "can_create": false, "can_manage_members": false, "can_create_groups": false },
  "announcements": { "unread_count": 2, "latest": { } },
  "my_groups": [],
  "other_groups": []
}
```

`other_groups` are groups in the forum the user cannot read (`is_locked: true`). Do not open them.

**Group object**

```json
{
  "id": "uuid",
  "forum_id": "uuid",
  "name": "مهندسو المواقع",
  "description": "...",
  "icon_url": "https://...|null",
  "category": null,
  "show_name_in_chat": true,
  "status": "active",
  "members_count": 3,
  "admins_count": 1,
  "send_permission": "all_members",
  "allow_images": true,
  "allow_voice": true,
  "allow_files": true,
  "is_locked": false,
  "unread_count": 3,
  "last_message": {
    "id": "uuid",
    "type": "text",
    "preview": "تم رفع تقرير الزيارة",
    "sender": { "id": "uuid", "name": "أحمد" },
    "created_at": "2026-11-22T09:18:00.000000Z"
  },
  "access": { }
}
```

Archived groups are not in this payload. `last_message` and `unread_count` are empty when `can_read` is false. When `show_name_in_chat` is false, still use `name` on the forum home list; hide it only in the chat header if the design does that.

### 4.2 Announcements

| Method | Path | Notes |
|--------|------|-------|
| GET | `/forums/{forumId}/announcements?page=1&per_page=15` | Published only. Extra key `unread_count`. Each item has `is_read` |
| POST | `/forums/{forumId}/announcements/read` | `{ "ids": ["uuid"] }`. Omit `ids` to mark all as read in this forum |

Announcement object: `id`, `forum_id`, `title`, `body`, `author` `{id, name}`, `attachments[]` `{id, url, name, mime_type, type, size}`, `is_read`, `created_at`. `type` on an attachment is `image`, `file`, or `voice`.

The app does not publish or delete announcements.

### 4.3 Groups and messages

| Method | Path | Notes |
|--------|------|-------|
| GET | `/forums/{forumId}/groups?scope=all\|mine\|others&search=` | List inside the forum |
| GET | `/forums/{forumId}/groups/{id}` | Details |
| GET | `/forums/{forumId}/groups/{id}/access` | Access object for the current user |
| POST | `/forums/{forumId}/groups/{id}/read` | Reset the unread badge. Call when opening the chat, and when a realtime message arrives while that chat is open |
| GET | `/forums/{forumId}/groups/{groupId}/messages?limit=30&before_id=` | Newest page first. **Items inside a page are chronological.** `has_more` says older messages exist. Next page: `before_id` = id of the **oldest** message you have |
| POST | `/forums/{forumId}/groups/{groupId}/messages` | Multipart, below |
| DELETE | `/forums/{forumId}/groups/{groupId}/messages/{messageId}` | Sender only, on their own message |
| POST | `/forums/{forumId}/groups/{groupId}/messages/{messageId}/reports` | `{ "reason": "..." }`. Any member who can read the message. `422` if this user already reported it |

**Send message (multipart/form-data):**

| `type` | Required fields |
|--------|-----------------|
| `text` (default) | `body` |
| `image` | `attachment` (image ≤ 10MB), `body?` caption |
| `voice` | `attachment` (audio ≤ 20MB: m4a/aac/mp3/ogg/wav/webm), `duration_seconds` (waveform label, e.g. `0:24`) |
| `file` | `attachment` (≤ 20MB), `body?` |

`403` if the user cannot send or the type is disabled for the group.

**Message object**

```json
{
  "id": "uuid",
  "forum_id": "uuid",
  "group_id": "uuid",
  "type": "image",
  "body": "صورة من زيارة الموقع",
  "duration_seconds": null,
  "sender": { "id": "uuid", "name": "أحمد سامي" },
  "attachments": [
    { "id": 1, "url": "https://...", "name": "x.jpg", "mime_type": "image/jpeg", "type": "image", "size": 12345 }
  ],
  "created_at": "2026-11-22T09:18:00.000000Z"
}
```

Render «my message» by comparing `sender.id` with the logged-in user id.

Reporting a message does not remove it. A dashboard admin decides later. On `delete_message` the app receives `forum.message.deleted` and drops the bubble.

---

## 5. Real-time (Laravel Reverb)

Reverb speaks the **Pusher protocol** — use any Pusher client (`pusher_channels_flutter`, `laravel-echo` + `pusher-js`).

**Connection settings** come from the backend env: `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`, `REVERB_APP_KEY`.

**All forum channels are private.** Authorize every subscription:

```
POST {{base_url}}/api/v1/forum/broadcasting/auth
Headers: Authorization: Bearer {jwt}, X-Tenant: {tenant}
Body (form): socket_id=...&channel_name=private-forum.group.{id}
```

Use this URL as the Pusher/Echo `authEndpoint` and pass those headers. Do not use `/broadcasting/auth`.

| Channel (subscribe as `private-...`) | When | Events |
|--------------------------------------|------|--------|
| `forum.user.{userId}` | Once after login, keep open | `forum.list.changed`, `forum.group.activity`, `forum.membership.changed` |
| `forum.announcements.{forumId}` | While that forum is in the user's list (subscribe after `GET /forums`, drop when a forum disappears) | `forum.announcement.published` |
| `forum.group.{groupId}` | While that chat screen is open | `forum.message.created`, `forum.message.deleted` |

Event names are used **as is**, with a leading dot: `.listen('.forum.message.created', cb)`.

**Payloads**

`forum.message.created` → `{ "forum_id", "group_id", "message": { } }`. Also delivered to the sender. De-duplicate by `message.id` against the POST response.

`forum.message.deleted` → `{ "forum_id", "group_id", "message_id" }`. Remove the bubble. If it was the preview on the home list, refetch overview.

`forum.group.activity` → `{ "forum_id", "group_id", "last_message": { "id", "type", "preview", "sender", "created_at" } }`. Sent to every member except the sender. Update that group's preview. Add 1 to the group `unread_count` and to the forum `unread_count`, unless that chat is open — then call `POST /forums/{forumId}/groups/{id}/read`.

`forum.membership.changed` → `{ "forum_id", "group_id", "change": "joined|removed|updated|group_deleted" }`. Refetch `GET /forums/{forumId}/overview` (or the open group). On `removed` or `group_deleted`, close the chat and unsubscribe from `forum.group.{groupId}`.

`forum.announcement.published` → `{ "forum_id", "announcement": { } }`. Increment the forum and the announcements `unread_count`. Prepend the item if that screen is open.

`forum.list.changed` → `{ "forum_id", "change": "published|updated|archived|access_changed" }`. Refetch `GET /forums`. On `archived` or `access_changed`, if that forum (or a chat inside it) is on screen, leave it and unsubscribe from its announcements channel.

Reconnect: refetch `GET /forums`, the open forum's `/overview`, and the open chat's latest `/messages` page.

---

## 6. Push notifications (FCM)

The device token is saved on the user (existing `fcm_token` flow). Push goes to the recipients (group members except the sender, or everyone who can see the forum for announcements and a newly published forum).

`title` and `body` are localized. The **data payload** values are all strings:

| `type` | Extra data | On tap |
|--------|------------|--------|
| `forum_message` | `forum_id`, `group_id`, `message_id`, `message_type` (`text/image/voice/file`), `user_id` | Open that forum, then the group chat |
| `forum_announcement` | `forum_id`, `announcement_id`, `user_id` | Open that forum's announcements |
| `forum_published` | `forum_id`, `user_id` | Open that forum's home |

While the app is in the foreground and the same chat (or the same announcements screen) is open, suppress the local notification. Realtime already delivered it.

If a push arrives for a `forum_id` that is not in the cached list, refetch `GET /forums` before navigating.

---

## 7. Screens → API

| Screen | API |
|--------|-----|
| المنتديات (list) | `GET /forums` (`capabilities`) + `forum.list.changed` on `forum.user.{userId}` |
| إنشاء منتدى | `POST /forums` when `capabilities.can_create` |
| إضافة أعضاء للمنتدى | `GET /employees`, `GET/PUT /forums/{forumId}/members` when `can_manage_members` |
| إضافة أعضاء لمجموعة | `PUT /forums/{forumId}/groups/{groupId}/members` when `can_manage_members` |
| إنشاء مجموعة | `POST /forums/{forumId}/groups` when `can_create_groups` |
| نشر إعلان | `POST /forums/{forumId}/announcements` when `can_publish_announcements` |
| منتدى (home) | `GET /forums/{forumId}/overview` + `forum.group.activity` |
| الإعلانات | `GET /forums/{forumId}/announcements`, `POST .../announcements/read`, channel `forum.announcements.{forumId}` |
| شات المجموعة | `GET/POST .../groups/{id}/messages`, `POST .../groups/{id}/read`, channel `forum.group.{id}` |
| إبلاغ عن رسالة | `POST .../messages/{messageId}/reports` |

Show each management button only when its `capabilities` flag is true. The same screens also exist on the dashboard.

---

## 8. Cache and navigation

Keep the `GET /forums` payload in memory for the session. Each row's `id` is the `forumId` for every later call.

Suggested stack: Forums → Forum home → Announcements **or** Group chat.

Passing only a `group_id` is not enough anymore. Persist `forum_id` with the open chat so a push, a deep link, and a reconnect all hit `/forums/{forumId}/groups/{groupId}/...`.

---

## 9. Backend setup notes (for DevOps)

- `php artisan migrate` for the forum module.
- Tables: `forums` (the new parent), `forum_groups` (`forum_id`, `status`), `forum_group_members`, `forum_messages`, `forum_announcements` (`forum_id`, `status`), `forum_announcement_reads`, `forum_reports`, `forum_activity_logs`. Forum admins and forum members are stored on the forum, separate from group members.
- Reverb running and `BROADCAST_CONNECTION=reverb`. A queue worker must be running — events and FCM are queued.
- `FIREBASE_CREDENTIALS` configured for FCM.
