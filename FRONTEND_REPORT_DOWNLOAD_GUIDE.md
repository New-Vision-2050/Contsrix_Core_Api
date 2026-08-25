# Report Download — Frontend Integration Guide

> **Who this is for:** web frontend consuming `GET /api/v1/reports/{id}/download`.  
> **Why:** Large PDF reports are stored on DigitalOcean Spaces. The API now returns a **signed URL** in JSON. Opening that URL with axios/XHR causes a **CORS error**. Opening it with a normal browser navigation works.

---

## TL;DR

1. Call the download API with auth + `x-domain` (same as every other API call).
2. Read `payload.download_url` from the JSON response.
3. Open that URL with `window.location.href` or `window.open` — **do not** use axios/`responseType: 'blob'` against Spaces.
4. Do **not** follow redirects to Spaces with XMLHttpRequest.

---

## Endpoint

```
GET /api/v1/reports/{id}/download
```

**Required headers**

| Header | Example |
|---|---|
| `Authorization` | `Bearer <access_token>` |
| `x-domain` | `vd.constrix-nv.com` |
| `Accept` | `application/json` |

---

## Response shape (default)

HTTP **200** — JSON (not a file stream):

```json
{
  "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT",
  "message": null,
  "payload": {
    "download_url": "https://constrix.fra1.digitaloceanspaces.com/contrix/default_path/....pdf?X-Amz-Algorithm=...&response-content-disposition=attachment%3B...",
    "file_name": "تقرير الحضور والغياب - 2026-08-01 – 2026-08-25.pdf",
    "mime": "application/pdf",
    "file_size": 150047390,
    "expires_in": 1800
  }
}
```

| Field | Type | Meaning |
|---|---|---|
| `download_url` | string | Short-lived Spaces URL. Open in the browser to download. |
| `file_name` | string | Suggested filename (also forced by Spaces `Content-Disposition`). |
| `mime` | string | `application/pdf`, Excel mime, or `text/csv; charset=UTF-8`. |
| `file_size` | number \| null | Bytes on disk (can be large for PDFs). |
| `expires_in` | number | URL lifetime in seconds (currently **1800** = 30 minutes). |

### Error cases

| HTTP | When |
|---|---|
| `409` | Report not ready yet (`pending` / `processing`), or no direct URL (legacy file — use `?stream=1`). |
| `404` | Media/file missing. |
| `401` / `403` | Auth / permission. |

---

## Correct frontend pattern

```ts
async function downloadReport(reportId: string): Promise<void> {
  const { data } = await api.get(`/api/v1/reports/${reportId}/download`, {
    headers: {
      Accept: 'application/json',
      // Authorization + x-domain come from your api client interceptors
    },
  });

  const url = data?.payload?.download_url as string | undefined;
  if (!url) {
    throw new Error('download_url missing from report download response');
  }

  // Top-level navigation — NO CORS. Same as pasting the URL in the address bar.
  window.location.href = url;

  // Or keep the app tab open:
  // window.open(url, '_blank', 'noopener,noreferrer');
}
```

### Optional: invisible `<a>` click

```ts
function triggerBrowserDownload(url: string, fileName?: string): void {
  const a = document.createElement('a');
  a.href = url;
  a.rel = 'noopener';
  if (fileName) a.download = fileName; // hint only; cross-origin may ignore it
  a.target = '_blank';
  document.body.appendChild(a);
  a.click();
  a.remove();
}
```

Spaces already sends `Content-Disposition: attachment`, so the browser should download even without `a.download`.

---

## What NOT to do

```ts
// ❌ CORS: axios follows redirect / requests Spaces from the SPA origin
await api.get(`/api/v1/reports/${id}/download`, { responseType: 'blob' });

// ❌ CORS: fetching the signed Spaces URL with XHR
await fetch(payload.download_url);

// ❌ Do not strip query params from download_url — the signature is in the query string
```

Browser console error you will see if you do this:

```text
Access to XMLHttpRequest at 'https://constrix.fra1.digitaloceanspaces.com/...'
(redirected from 'https://core-be-production.constrix-nv.com/api/v1/reports/.../download')
from origin 'https://vd.constrix-nv.com' has been blocked by CORS policy
```

Pasting the same Spaces URL in the address bar works because that is **not** an XHR.

---

## UX notes

- **Large PDFs** (tens/hundreds of MB) can take a long time. Prefer `window.location` / `window.open` so the browser handles progress; do not buffer the file in JS memory.
- `expires_in` is 30 minutes. If the user waits longer before opening the URL, call the API again to get a fresh `download_url`.
- Show a toast like “Starting download…” after a successful API response; the file save dialog comes from the browser/Spaces, not from your blob handler.
- Excel / CSV use the same flow — only `mime` / `file_name` change.

---

## Optional: stream through the API

Only if you truly need the raw bytes from the API host (not recommended for large PDFs):

```
GET /api/v1/reports/{id}/download?stream=1
```

Returns the file body with `Content-Disposition` from the API. This can still hit timeouts/memory issues on huge PDFs. Prefer the signed URL.

---

## Checklist

- [ ] Remove `responseType: 'blob'` from report download.
- [ ] Read `payload.download_url` from JSON.
- [ ] Open with `window.location.href` or `window.open`.
- [ ] Keep sending `Authorization` + `x-domain` on the **API** call only (not to Spaces).
- [ ] Handle `409` (not ready) and missing `download_url`.
- [ ] Re-fetch URL if older than ~30 minutes.
