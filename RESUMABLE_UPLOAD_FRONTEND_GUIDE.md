# Resumable (Chunked) File Upload — Frontend Guide

> Fixes large-file `500 Internal Server Error` (e.g. 86MB files) on:
> - `POST /api/v1/projects/attachment-requests` (create attachment request)
> - `POST /api/v1/projects/attachment-requests/items/replace-media`
>
> Large uploads sent as a single multipart request are prone to timeouts,
> memory pressure, and cannot resume after a network drop. For any file you
> consider "large" (recommendation: **> 8–10MB**), upload it in small chunks
> first, then reference the resulting `upload_id` token instead of sending
> the raw file. Small files can still be sent directly as before — nothing
> changes for those.

All endpoints below live in the same route group as the rest of
`/api/v1/projects/*` → middleware `auth:api` + `InitializeTenancyByRequestData`.
Your existing API client already sends the Bearer token and `X-Tenant` header,
so no new auth/tenant wiring is needed.

---

## 1. Overview of the flow

```
1. POST /uploads/init            -> { upload_id }
2. POST /uploads/{upload_id}/chunk   (repeat for every chunk, any order, retryable)
3. GET  /uploads/{upload_id}/status  (optional — resume after reload/drop)
4. POST /uploads/{upload_id}/complete -> merges chunks server-side
5. Use the returned upload_id as a token in:
     - replace-media:      { item_id, upload_id }
     - create request:     { ..., attachment_upload_ids: [upload_id, ...] }
```

The `upload_id` from step 4 is a **single-use token** valid for ~2 hours. Once
consumed by step 5 it cannot be reused. Unfinished sessions (steps 1–3) expire
after ~24 hours if abandoned.

All endpoints are prefixed with:
```
/api/v1/projects/attachment-requests/uploads
```

---

## 2. Endpoints

### 2.1 Initiate a session
```
POST /api/v1/projects/attachment-requests/uploads/init
Content-Type: application/json

{
  "file_name": "large-drawing.pdf",
  "file_size": 90177536,
  "total_chunks": 18,
  "mime_type": "application/pdf"
}
```
Pick a chunk size on the client (recommended **5MB**) and compute
`total_chunks = Math.ceil(file.size / chunkSize)`.

Response:
```json
{
  "payload": {
    "upload_id": "b3f1e6b0-....-....-....-............",
    "file_name": "large-drawing.pdf",
    "mime_type": "application/pdf",
    "file_size": 90177536,
    "total_chunks": 18,
    "received_chunks": [],
    "status": "pending"
  }
}
```

### 2.2 Upload a chunk
```
POST /api/v1/projects/attachment-requests/uploads/{upload_id}/chunk
Content-Type: multipart/form-data

chunk_index: 0            // 0-based
chunk: <binary blob>       // Blob/File slice for this chunk
```
- Safe to retry the same `chunk_index` (idempotent, overwrites).
- Chunks can be sent in parallel or sequentially, in any order.

Response echoes updated `received_chunks`:
```json
{ "payload": { "upload_id": "...", "received_chunks": [0], "total_chunks": 18, ... } }
```

### 2.3 Check status (resume support)
```
GET /api/v1/projects/attachment-requests/uploads/{upload_id}/status
```
Returns the same shape as above. After a page reload or network drop, call
this first and only (re)send the chunks **not** already in `received_chunks`.

### 2.4 Complete the upload
```
POST /api/v1/projects/attachment-requests/uploads/{upload_id}/complete
```
Call this only once all `total_chunks` have been received. The server merges
them into one file.

Response:
```json
{
  "payload": {
    "upload_id": "b3f1e6b0-....-....-....-............",
    "file_name": "large-drawing.pdf",
    "file_size": 90177536,
    "mime_type": "application/pdf"
  }
}
```
`upload_id` is now your **token** — use it in place of the raw file below.

### 2.5 Abort (optional cleanup)
```
DELETE /api/v1/projects/attachment-requests/uploads/{upload_id}
```
Call this if the user cancels the upload, to free temp storage early.

---

## 3. Using the token

### 3.1 Replace media on an existing attachment item
```
POST /api/v1/projects/attachment-requests/items/replace-media
Content-Type: application/json   // no file attached anymore!

{
  "item_id": "<attachment_request_item_id>",
  "upload_id": "b3f1e6b0-....-....-....-............"
}
```
Small files: you may still send `multipart/form-data` with `new_file` directly
instead of `upload_id` — both are accepted (exactly one is required).

### 3.2 Create an attachment request
```
POST /api/v1/projects/attachment-requests
Content-Type: application/json (if only using tokens) or multipart/form-data (mixed)

{
  "name": "Shop Drawing Files",
  "date": "2026-07-21",
  "project_id": "...",
  "procedure_setting_id": "...",
  "attachments": [ /* optional: small files sent directly */ ],
  "attachment_upload_ids": ["b3f1e6b0-....-....-....-............"],
  "notes": "..."
}
```
- `attachments` and `attachment_upload_ids` can be combined (some small files
  sent directly, some large files via tokens) — at least one item total is
  required across both.

---

## 4. Next.js / React example

```ts
const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB
const BASE = "/api/v1/projects/attachment-requests/uploads";

async function uploadLargeFile(file: File, api: AxiosInstance): Promise<string> {
  const totalChunks = Math.ceil(file.size / CHUNK_SIZE);

  const { data: init } = await api.post(`${BASE}/init`, {
    file_name: file.name,
    file_size: file.size,
    total_chunks: totalChunks,
    mime_type: file.type,
  });
  const uploadId = init.payload.upload_id;

  for (let index = 0; index < totalChunks; index++) {
    const start = index * CHUNK_SIZE;
    const chunk = file.slice(start, start + CHUNK_SIZE);

    const form = new FormData();
    form.append("chunk_index", String(index));
    form.append("chunk", chunk, file.name);

    // Retry a couple of times per chunk on network failure before giving up.
    await api.post(`${BASE}/${uploadId}/chunk`, form, {
      headers: { "Content-Type": "multipart/form-data" },
    });

    // Optional: report progress -> (index + 1) / totalChunks
  }

  const { data: completed } = await api.post(`${BASE}/${uploadId}/complete`);
  return completed.payload.upload_id; // token to send to replace-media / create-request
}

// Usage — replace media:
const uploadId = await uploadLargeFile(file, api);
await api.post("/api/v1/projects/attachment-requests/items/replace-media", {
  item_id: itemId,
  upload_id: uploadId,
});

// Usage — create request with a mix of small + large files:
const bigTokens = await Promise.all(largeFiles.map((f) => uploadLargeFile(f, api)));
const form = new FormData();
form.append("name", "...");
form.append("date", "...");
form.append("project_id", projectId);
form.append("procedure_setting_id", procedureSettingId);
smallFiles.forEach((f) => form.append("attachments[]", f));
bigTokens.forEach((t) => form.append("attachment_upload_ids[]", t));
await api.post("/api/v1/projects/attachment-requests", form);
```

### Resume after reload
Persist `uploadId`, `fileName` (and ideally a hash of the file, e.g. via
`crypto.subtle.digest`) in `localStorage` while chunking. On reload, before
re-uploading, call `GET {BASE}/{uploadId}/status` — if it 404s, the session
expired and you must call `init` again; otherwise only re-send chunks missing
from `received_chunks`.

---

## 5. Error responses

Standard envelope on failure:
```json
{ "status": "error", "message": { "type": "error", "code": 404, "name": null, "description": "Upload session not found or expired." } }
```

| HTTP code | Meaning |
|---|---|
| 404 | `upload_id` unknown or expired (session TTL passed) |
| 403 | `upload_id` belongs to a different company/tenant |
| 422 | Missing/invalid chunk index, chunks incomplete on `complete`, or token already consumed/expired |

---

## 6. Notes for backend/infra

- Chunks and merged files are stored on the `local` disk (`storage/app/private`)
  and are **not** publicly accessible.
- A scheduled command `chunked-uploads:cleanup` (hourly, `routes/console.php`)
  removes stale temp upload directories older than 6 hours, so no manual
  cleanup is required.
- Recommended client chunk size: **5MB** (keeps well under the 500MB
  `client_max_body_size` / `post_max_size` limits per request).
