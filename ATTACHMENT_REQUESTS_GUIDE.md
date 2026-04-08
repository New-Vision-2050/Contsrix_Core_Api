# Attachment Request System - Complete Guide

## Overview

The Attachment Request System allows companies to request and approve attachments for shared projects. When attachments are approved, they are automatically saved to the ArchiveLibrary folder structure.

## Key Concepts

### 1. **ArchiveLibrary Folder Integration**

Each project has a root folder in ArchiveLibrary where `folder.id = project.id`. The attachment type hierarchy maps to the folder structure:

- **`attachment_type_id`** → Folder ID (Level 1 - Root subfolder)
- **`attachment_sub_type_id`** → Folder ID (Level 2 - Subfolder)
- **`attachment_sub_sub_type_id`** → Folder ID (Level 3 - Sub-subfolder)

**Example Folder Structure:**
```
Project Root Folder (id = project_id)
├── Technical Drawings (attachment_type_id)
│   ├── Architectural (attachment_sub_type_id)
│   │   └── Floor Plans (attachment_sub_sub_type_id)
│   └── Structural (attachment_sub_type_id)
├── Contracts (attachment_type_id)
└── Reports (attachment_type_id)
```

### 2. **Request Status Workflow**

**Request-Level Statuses:**
- **`pending`** - No items have been responded to yet
- **`semi-approved`** - Some items approved, some declined/pending/update_requested
- **`approved`** - ALL items approved
- **`declined`** - ALL items declined

**Item-Level Statuses:**
- **`pending`** - Waiting for response
- **`approved`** - Approved and saved to ArchiveLibrary
- **`declined`** - Rejected
- **`update_requested`** - Needs modification from sender

### 3. **Auto-Save to ArchiveLibrary**

When an attachment item is **approved**, the system automatically:

1. Creates a `File` record in ArchiveLibrary
2. Links it to the appropriate folder based on `attachment_type_id` hierarchy
3. Duplicates the media file (no physical duplication, only database row)
4. Updates `model_type` and `model_id` to reference the new File record
5. Keeps original file in attachment-requests storage as backup

**Folder Selection Priority:**
```php
if (attachment_sub_sub_type_id exists) {
    save to → attachment_sub_sub_type_id folder
} else if (attachment_sub_type_id exists) {
    save to → attachment_sub_type_id folder
} else if (attachment_type_id exists) {
    save to → attachment_type_id folder
} else {
    save to → project root folder
}
```

## API Endpoints

### **1. Get Shared Companies (For Dropdown Selection)**

```http
GET /api/v1/projects/sharing/projects/{project_id}/shared-companies
```

**Use Case:** Get list of companies that have accepted the project share. Use this to populate the `receiver_company_id` dropdown when creating an attachment request.

**Response Example:**
```json
{
  "data": [
    {
      "id": "company-uuid-1",
      "name": "ABC Construction Ltd",
      "serial_number": "COMPANY-12345678",
      "shared_at": "2026-04-01T10:00:00.000Z",
      "accepted_at": "2026-04-01T15:30:00.000Z"
    },
    {
      "id": "company-uuid-2",
      "name": "XYZ Engineering Co",
      "serial_number": "COMPANY-87654321",
      "shared_at": "2026-04-05T08:00:00.000Z",
      "accepted_at": "2026-04-05T09:15:00.000Z"
    }
  ]
}
```

### **2. Get Folder Children (For Dropdown Selection)**

```http
GET /api/v1/projects/attachment-requests/folders/children?project_id={project_id}
GET /api/v1/projects/attachment-requests/folders/children?parent_id={folder_id}
```

**Use Case:** Populate dropdowns for selecting attachment types (folder paths)

**Response Example:**
```json
{
  "data": [
    {
      "id": "folder-uuid-1",
      "name": "Technical Drawings",
      "parent_id": null,
      "project_id": "project-uuid"
    },
    {
      "id": "folder-uuid-2",
      "name": "Contracts",
      "parent_id": null,
      "project_id": "project-uuid"
    }
  ]
}
```

### **3. Create Attachment Request**

```http
POST /api/v1/projects/attachment-requests
Content-Type: multipart/form-data

Fields:
- name (required)
- date (required, YYYY-MM-DD)
- project_id (required)
- receiver_company_id (required - use Get Shared Companies endpoint)
- attachment_type_id (optional - folder ID)
- attachment_sub_type_id (optional - subfolder ID)
- attachment_sub_sub_type_id (optional - sub-subfolder ID)
- attachments[] (required - array of files, max 10MB each)
- notes (optional)
```

**Serial Number:** Auto-generated as `ATR-YYYYMMDD-####` (e.g., `ATR-20260409-0001`)

### **4. View Outgoing Requests (Sender)**

```http
GET /api/v1/projects/attachment-requests/outgoing
GET /api/v1/projects/attachment-requests/outgoing?project_id={project_id}
```

Lists all requests your company has sent to other companies. Optionally filter by `project_id` to see requests for a specific project.

### **5. View Incoming Requests (Receiver)**

```http
GET /api/v1/projects/attachment-requests/incoming
GET /api/v1/projects/attachment-requests/incoming?project_id={project_id}
GET /api/v1/projects/attachment-requests/incoming/pending
GET /api/v1/projects/attachment-requests/incoming/pending?project_id={project_id}
```

Lists all requests received by your company. Optionally filter by `project_id` to see requests for a specific project.

### **6. Get Request Details**

```http
GET /api/v1/projects/attachment-requests/{id}
```

Returns full details including all attachment items with their individual statuses.

### **7. Respond to Individual Attachment**

```http
POST /api/v1/projects/attachment-requests/items/respond
Content-Type: application/json

{
  "item_id": "item-uuid",
  "action": "approve|decline|request_update",
  "notes": "Optional response notes"
}
```

**Actions:**
- **`approve`** - Saves file to ArchiveLibrary automatically
- **`decline`** - Rejects the file
- **`request_update`** - Asks sender to modify and resubmit

### **8. Approve/Decline Entire Request**

```http
POST /api/v1/projects/attachment-requests/{id}/approve
POST /api/v1/projects/attachment-requests/{id}/decline
```

Bulk approve or decline ALL attachments at once.

## Workflow Example

### **Scenario: Company A sends drawings to Company B**

**Step 1: Company A selects folder structure**
```http
GET /folders/children?project_id=project-123
→ Returns: ["Technical Drawings", "Contracts", "Reports"]

GET /folders/children?parent_id=technical-drawings-id
→ Returns: ["Architectural", "Structural", "MEP"]

GET /folders/children?parent_id=architectural-id
→ Returns: ["Floor Plans", "Elevations", "Sections"]
```

**Step 2: Company A creates request**
```http
POST /attachment-requests
{
  "name": "Architectural Drawings Approval",
  "date": "2026-04-10",
  "project_id": "project-123",
  "receiver_company_id": "company-b-id",
  "attachment_type_id": "technical-drawings-id",
  "attachment_sub_type_id": "architectural-id",
  "attachment_sub_sub_type_id": "floor-plans-id",
  "attachments": [drawing1.pdf, drawing2.pdf, drawing3.pdf]
}
```

**Step 3: Company B views incoming requests**
```http
GET /attachment-requests/incoming/pending
→ Shows: All pending requests across all projects

GET /attachment-requests/incoming/pending?project_id=project-123
→ Shows: Only pending requests for "Project 123"
→ Result: "Architectural Drawings Approval" with 3 pending items
```

**Step 4: Company B reviews each drawing**
```http
POST /items/respond
{
  "item_id": "item-1",
  "action": "approve",
  "notes": "Approved for construction"
}
→ File automatically saved to: Project/Technical Drawings/Architectural/Floor Plans/

POST /items/respond
{
  "item_id": "item-2",
  "action": "approve"
}
→ File saved to same folder

POST /items/respond
{
  "item_id": "item-3",
  "action": "request_update",
  "notes": "Please fix dimensions on page 3"
}
→ Not saved yet
```

**Step 5: Request status updates automatically**
```
Total: 3 items
Approved: 2
Update Requested: 1
→ Request Status = "semi-approved"
```

**Step 6: Company B approves entire request**
```http
POST /attachment-requests/{id}/approve
→ All items (including item-3) become "approved"
→ All files saved to ArchiveLibrary
→ Request Status = "approved"
```

## Database Schema

### **attachment_requests**
```sql
- id (UUID, PK)
- serial_number (ATR-YYYYMMDD-####)
- name
- date
- project_id (FK → projects)
- sender_company_id (FK → companies)
- receiver_company_id (FK → companies)
- attachment_type_id (folder ID - optional)
- attachment_sub_type_id (folder ID - optional)
- attachment_sub_sub_type_id (folder ID - optional)
- status (pending|semi-approved|approved|declined)
- created_by_user_id, responded_by_user_id
- responded_at, notes
```

### **attachment_request_items**
```sql
- id (UUID, PK)
- attachment_request_id (FK)
- file_name, file_path, file_type, file_size
- status (pending|approved|declined|update_requested)
- responded_by_user_id, responded_at, response_notes
```

## Media Duplication Strategy

**No Physical File Duplication:**
The system duplicates only the database row in the `media` table, pointing to the same physical file on disk.

```php
// Original media record
media: {
  id: 1,
  model_type: 'AttachmentRequestItem',
  model_id: 'item-uuid',
  file_path: 'attachment-requests/2026/04/drawing.pdf'
}

// After approval, new media record created
media: {
  id: 2,
  model_type: 'File',
  model_id: 'file-uuid',
  file_path: 'attachment-requests/2026/04/drawing.pdf' // Same file
}
```

This saves storage space while maintaining referential integrity.

## Security & Permissions

✅ **Sender Company:**
- Can create requests
- Can view their outgoing requests
- Cannot respond to requests

✅ **Receiver Company:**
- Can view incoming requests
- Can respond to items (approve/decline/request update)
- Can approve/decline entire request
- Files only saved to their ArchiveLibrary

✅ **Access Control:**
- Only companies involved in project sharing can send/receive requests
- Folders must belong to the receiver's company
- Media files use Spatie Media Library with tenant isolation

## Statistics & Reporting

Each request includes real-time statistics:

```json
"statistics": {
  "total_items": 5,
  "approved_items": 3,
  "declined_items": 1,
  "pending_items": 1,
  "update_requested_items": 0
}
```

Use these to show progress indicators in the UI.

## Best Practices

1. **Always select folder path when creating requests** - Makes file organization automatic
2. **Use meaningful request names** - Helps track requests later
3. **Add notes when declining or requesting updates** - Sender knows what to fix
4. **Review items individually before bulk approval** - Ensures quality control
5. **Use the statistics** - Show progress bars/badges in UI

## Postman Collection

Import `ATTACHMENT_REQUESTS_API.postman_collection.json` for complete API testing with examples.

**Variables to set:**
- `base_url`: Your API base URL
- `token`: Bearer authentication token
- `project_id`: Project UUID
- `receiver_company_id`: Company UUID to send request to
- `request_id`: Attachment request UUID
- `item_id`: Attachment item UUID

---

**Created:** April 9, 2026  
**Version:** 1.0  
**Module:** Project Management - Attachment Requests
