# Forum (المنتدى) — Mobile Developer Guide

A company has **many forums**. Each forum has its own announcements and chat groups (text, images, voice, files).

The employee app lists the forums this user can see, then opens one. Creating forums, groups, members, permissions, announcements, reports review, settings, and the activity log are **dashboard** screens — see `docs/FORUM_DASHBOARD_FRONTEND_SPEC.md`.

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
| User whose role includes a forum permission | Same chat access, plus the flags below. The management screens stay on the dashboard. The app only uses these flags. |

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

Tapping a row opens that forum's home and subscribes to its announcements channel (section 5).

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
| `is_forum_admin` | `GET /forums/{id}/overview` and `GET /forums/{id}/access` | any forum management permission |
| `can_create_groups` | `GET /forums/{id}/access` | `FORUM_GROUP_CREATE` |
| `can_publish_announcements` | `GET /forums/{id}/access` | `FORUM_ANNOUNCEMENT_CREATE` |
| `access.can_manage` | each group | `FORUM_GROUP_UPDATE` |

Creating a forum is not a mobile screen. A user who only has `FORUM_CREATE` still gets `is_forum_admin: true`, and the dashboard (which reads `GET /users/me`) shows **إنشاء منتدى**.

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
| المنتديات (list) | `GET /forums` + `forum.list.changed` on `forum.user.{userId}` |
| منتدى (home) | `GET /forums/{forumId}/overview` + `forum.group.activity` |
| الإعلانات | `GET /forums/{forumId}/announcements`, `POST .../announcements/read`, channel `forum.announcements.{forumId}` |
| شات المجموعة | `GET/POST .../groups/{id}/messages`, `POST .../groups/{id}/read`, channel `forum.group.{id}` |
| إبلاغ عن رسالة | `POST .../messages/{messageId}/reports` |

There is no «إدارة المنتدى» screen in the app. Admins use the dashboard.

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
