# Forum (المنتدى) — Mobile Developer Guide

Real-time company forum: **Announcements** (published by admins only) and **Groups** (chat rooms with text / images / voice / files).
Backend: Laravel REST APIs + **Laravel Reverb** (WebSocket) + **FCM** push notifications.

Postman: `Forum_API.postman_collection.json`

---

## 1. Common

**Base URL:** `{{base_url}}/api/v1/forum`

**Required headers (all endpoints):**

| Header | Value |
|---|---|
| `Authorization` | `Bearer {jwt}` |
| `X-Tenant` | company (tenant) id |
| `Accept` | `application/json` |
| `Accept-Language` | `ar` / `en` |

**Response envelope:**

```json
{ "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT", "message": null, "payload": { } }
```

Lists use `SUCCESS_WITH_LIST_PAYLOAD_OBJECTS` and `payload` is an array. Errors: `403` (not allowed), `404`, `422` (`errors` per field).

**Who is a "forum admin"?** Users with role `super-admin` or `admin` (or company owner). Only they can:
- create / edit / delete groups, manage members and send settings
- publish / delete announcements
- delete anyone's message

Everyone else can only read announcements, read groups they joined, and send in a group when allowed.

---

## 2. Permission model (per group)

Every group item contains an `access` object. **Drive your UI from it**:

```json
"access": {
  "group_id": "uuid",
  "is_member": true,
  "my_role": "member",              // admin | member | null (not a member)
  "can_read": true,                 // false => show lock icon "الانضمام عن طريق الأدمن"
  "can_send": true,                 // false => show read-only banner "المجموعة للقراءة فقط · الإرسال للأدمنز"
  "allowed_message_types": ["text", "image", "voice", "file"],
  "can_manage": false               // forum admin
}
```

`send_permission` (group setting, from the "صلاحيات الإرسال" screen):

| Value | Meaning |
|---|---|
| `admins_only` | الأدمنز فقط (group admins + forum admins) |
| `all_members` | كل الأعضاء |
| `specific_members` | موظفون محددون: group admins + members with `can_send = true` |

`allow_images`, `allow_voice`, `allow_files` toggle the attachment types for **everyone allowed to send**. Text is always allowed.

`GET /access` returns the current user's access on **all** groups in one call (useful for caching).

---

## 3. Endpoints

### 3.1 Home / overview — `GET /overview`
Forum home screen: announcements card + "مجموعاتي" + "مجموعات أخرى".

```json
{
  "is_forum_admin": false,
  "announcements": { "unread_count": 2, "latest": { /* announcement */ } },
  "my_groups":    [ /* group */ ],
  "other_groups": [ /* group (is_locked = true) */ ]
}
```

**Group object**

```json
{
  "id": "uuid", "name": "مهندسو المواقع", "description": "...",
  "icon_url": "https://...|null",
  "members_count": 3, "admins_count": 1,
  "send_permission": "all_members",
  "allow_images": true, "allow_voice": true, "allow_files": true,
  "is_locked": false,
  "unread_count": 3,
  "last_message": { "id": "uuid", "type": "text", "preview": "تم رفع تقرير الزيارة", "sender": {"id":"uuid","name":"أحمد"}, "created_at": "ISO8601" },
  "access": { }
}
```

`last_message` / `unread_count` are empty for groups the user cannot read.

### 3.2 Announcements
| Method | Path | Notes |
|---|---|---|
| GET | `/announcements?page=1&per_page=15` | paginated, includes `unread_count` (extra key) and `is_read` per item |
| POST | `/announcements` | **admin only**. multipart: `title?`, `body`, `attachments[]?` |
| POST | `/announcements/read` | body `{ "ids": ["uuid"] }` — omit `ids` to mark all as read |
| DELETE | `/announcements/{id}` | **admin only** |

### 3.3 Groups
| Method | Path | Notes |
|---|---|---|
| GET | `/groups?scope=all\|mine\|others&search=` | list |
| GET | `/groups/{id}` | details (admins also get `allowed_user_ids`) |
| POST | `/groups` | **admin only**. multipart: `name`, `description?`, `icon?` (image), `send_permission?`, `allow_images?`, `allow_voice?`, `allow_files?`, `members[i][user_id]`, `members[i][role]` (`admin`/`member`), `members[i][can_send]` |
| POST / PUT | `/groups/{id}` | **admin only**. Same fields (all optional). **Use POST when uploading an icon** (multipart). If `members` is sent the member list is replaced |
| DELETE | `/groups/{id}` | **admin only** |
| GET | `/groups/{id}/access` | access object for current user |
| POST | `/groups/{id}/read` | mark group as read (reset unread badge) — call when opening the chat / on new realtime message while chat is open |

### 3.4 Members & send settings (admin only)
| Method | Path | Body |
|---|---|---|
| GET | `/employees?search=` | employees picker `[ {id,name,email} ]` |
| GET | `/groups/{id}/members` | visible to members/admins. `[ {user_id,name,role,can_send} ]` |
| PUT | `/groups/{id}/members` | `{ "members": [ {"user_id":"uuid","role":"admin\|member"} ] }` — **replaces** the list (the "الأعضاء والأدمنز" screen) |
| PUT | `/groups/{id}/send-settings` | `{ "send_permission":"specific_members", "allowed_user_ids":["uuid"], "allow_images":false, "allow_voice":true, "allow_files":true }` (the "صلاحيات الإرسال" screen). `allowed_user_ids` must be group members |

### 3.5 Messages
| Method | Path | Notes |
|---|---|---|
| GET | `/groups/{groupId}/messages?limit=30&before_id=` | newest page first; **items inside a page are chronological**. `has_more` (extra key) tells if older messages exist; for the next page pass `before_id` = id of the **first** (oldest) message you have |
| POST | `/groups/{groupId}/messages` | multipart (see below) |
| DELETE | `/groups/{groupId}/messages/{messageId}` | sender or forum admin |

**Send message (multipart/form-data):**

| `type` | Required fields |
|---|---|
| `text` (default) | `body` |
| `image` | `attachment` (image ≤ 10MB), `body?` caption |
| `voice` | `attachment` (audio ≤ 20MB: m4a/aac/mp3/ogg/wav/webm), `duration_seconds` (for the waveform label e.g. `0:24`) |
| `file` | `attachment` (≤ 20MB), `body?` |

Returns `403` if the user cannot send or the type is disabled for the group.

**Message object**

```json
{
  "id": "uuid", "group_id": "uuid",
  "type": "image", "body": "صورة من زيارة الموقع", "duration_seconds": null,
  "sender": { "id": "uuid", "name": "أحمد سامي" },
  "attachments": [ { "id": 1, "url": "https://...", "name": "x.jpg", "mime_type": "image/jpeg", "type": "image", "size": 12345 } ],
  "created_at": "2026-11-19T09:18:00.000000Z"
}
```

Render "my message" by comparing `sender.id` with the logged-in user id.

---

## 4. Real-time (Laravel Reverb)

Reverb speaks the **Pusher protocol** — use any Pusher client (`pusher_channels_flutter`, `laravel-echo` + `pusher-js`).

**Connection settings:** host/port/scheme/app key from the backend `.env` (`REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`, `REVERB_APP_KEY`).

**All forum channels are PRIVATE.** The client must authorize each subscription:

```
POST {{base_url}}/api/v1/forum/broadcasting/auth
Headers: Authorization: Bearer {jwt}, X-Tenant: {tenant}
Body (form): socket_id=...&channel_name=private-forum.group.{id}
```

Use this URL as the Pusher/Echo `authEndpoint` and pass the headers above in the auth headers. (Do not use `/broadcasting/auth`.)

| Channel (subscribe as `private-...`) | When to subscribe | Events |
|---|---|---|
| `forum.user.{userId}` | once after login (keep open) | `forum.group.activity`, `forum.membership.changed` |
| `forum.announcements.{companyId}` | once after login | `forum.announcement.published` |
| `forum.group.{groupId}` | while the chat screen is open | `forum.message.created`, `forum.message.deleted` |

Event names are used **as is** in Echo with a leading dot: `.listen('.forum.message.created', cb)`.

**Payloads**

`forum.message.created` → `{ "group_id": "uuid", "message": { /* message object */ } }` (also delivered to the sender — de-duplicate by `message.id` against the response of your POST).

`forum.message.deleted` → `{ "group_id": "uuid", "message_id": "uuid" }`

`forum.group.activity` → `{ "group_id": "uuid", "last_message": { "id","type","preview","sender","created_at" } }` — sent to every member except the sender. Update the home list preview and `unread_count += 1` unless that chat is currently open (then call `POST /groups/{id}/read`).

`forum.membership.changed` → `{ "group_id": "uuid", "change": "joined|removed|updated|group_deleted" }` — re-fetch `GET /overview` (or `GET /groups/{id}` if the chat is open). On `removed` / `group_deleted` close the chat screen and unsubscribe from the group channel.

`forum.announcement.published` → `{ "announcement": { /* announcement object */ } }`

Reconnect strategy: on reconnect, re-fetch `/overview` and the open chat (`/messages` latest page) to recover missed events.

---

## 5. Push notifications (FCM)

The device token must be saved on the user (existing flow — `fcm_token`). Push is sent to all recipients (group members except the sender, or all company users for announcements).

Notification `title` / `body` are localized; the **data payload** (all values are strings):

| `type` | Extra data | Action on tap |
|---|---|---|
| `forum_message` | `group_id`, `message_id`, `message_type` (`text/image/voice/file`), `user_id` | open the group chat |
| `forum_announcement` | `announcement_id`, `user_id` | open announcements screen |

Recommendation: while the app is in foreground and the same chat is open, suppress the local notification (realtime already delivered it).

---

## 6. Screens → API mapping

| Screen | APIs |
|---|---|
| المنتدى (home) | `GET /overview` + realtime `forum.group.activity` |
| الإعلانات العامة | `GET /announcements`, `POST /announcements/read` |
| شات المجموعة | `GET/POST /groups/{id}/messages`, `POST /groups/{id}/read`, channel `forum.group.{id}` |
| إدارة المنتدى › المجموعات | `GET /groups?search=` |
| إدارة المنتدى › الإعلانات | `POST /announcements`, `DELETE /announcements/{id}` |
| بيانات المجموعة | `POST /groups`, `POST /groups/{id}` (icon + name + description) |
| الأعضاء والأدمنز | `GET /employees`, `GET/PUT /groups/{id}/members` |
| صلاحيات الإرسال | `PUT /groups/{id}/send-settings` |

Show the "إدارة المنتدى" button only when `is_forum_admin = true`.

---

## 7. Backend setup notes (for DevOps)

- Run the module migration: `php artisan migrate` (creates `forum_groups`, `forum_group_members`, `forum_messages`, `forum_announcements`, `forum_announcement_reads`).
- Reverb server running and `BROADCAST_CONNECTION=reverb`; queue worker running (events and FCM push are queued).
- `FIREBASE_CREDENTIALS` configured for FCM.
