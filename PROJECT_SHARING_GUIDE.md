# Project Sharing System - Implementation Guide

## Overview
This system enables companies to share projects with other companies using a polymorphic relationship. Projects can be shared with specific schemas, and the recipient company must accept the invitation before accessing the shared project.

## Architecture

### Key Components

1. **`resource_shares` table** - Polymorphic table storing all sharing relationships
2. **`Shareable` trait** - Replaces `BelongsToTenant` for models that support sharing
3. **`ResourceShare` model** - Manages sharing relationships
4. **`ResourceShareService`** - Business logic for sharing operations
5. **`ProjectShareController`** - API endpoints for project sharing

### Status Flow
- **pending** → Project share invitation sent, awaiting response
- **accepted** → Recipient company accepted, project now visible in their project list
- **rejected** → Recipient company rejected the invitation

## Database Schema

```sql
resource_shares:
  - id (uuid)
  - shareable_type (string) - e.g., "Modules\Project\ProjectManagement\Models\ProjectManagement"
  - shareable_id (uuid) - Project ID
  - owner_company_id (uuid) - Company that owns the project
  - shared_with_company_id (uuid) - Company receiving the share
  - status (enum: pending, accepted, rejected)
  - schema_ids (json) - Array of schema IDs that are shared
  - shared_by_user_id (uuid) - User who initiated the share
  - responded_by_user_id (uuid) - User who accepted/rejected
  - responded_at (timestamp)
  - notes (text)
```

## API Endpoints

### 1. Get Company by Serial Number
**GET** `/api/companies/by-serial-number?serial_number={serial_number}`

**Purpose**: Lookup a company by its serial number before sharing

**Response**:
```json
{
  "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT",
  "payload": {
    "id": "uuid",
    "name": "Company Name",
    "serial_no": "COMPANY-12345678",
    ...
  }
}
```

---

### 2. List All Schemas
**GET** `/api/schemas`

**Purpose**: Get all available project schemas to select when sharing

**Query Parameters**:
- `search` (optional) - Filter schemas by name
- `per_page` (optional, default: 50) - Results per page
- `page` (optional, default: 1) - Page number

**Response**:
```json
{
  "code": "SUCCESS_WITH_MULTIPLE_PAYLOAD_OBJECTS",
  "payload": [
    {
      "id": 1,
      "name": "Schema Name"
    }
  ],
  "pagination": {
    "total": 100,
    "per_page": 50,
    "current_page": 1,
    "last_page": 2
  }
}
```

---

### 3. Share Project with Company
**POST** `/api/projects/share`

**Purpose**: Share a project with another company

**Request Body**:
```json
{
  "project_id": "uuid",
  "company_serial_number": "COMPANY-12345678",
  "schema_ids": [1, 2, 3],  // Optional array of schema IDs
  "notes": "Optional note about the share"
}
```

**Response**:
```json
{
  "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT",
  "payload": {
    "id": "share-uuid",
    "owner_company": {
      "id": "uuid",
      "name": "Owner Company",
      "serial_number": "OWNER-12345"
    },
    "shared_with_company": {
      "id": "uuid",
      "name": "Recipient Company",
      "serial_number": "COMPANY-12345678"
    },
    "status": "pending",
    "schema_ids": [1, 2, 3],
    "created_at": "2026-04-08T10:00:00.000Z"
  }
}
```

---

### 4. Get Project Shares
**GET** `/api/projects/{project_id}/shares`

**Purpose**: Get all companies a project is shared with (owner only)

**Response**:
```json
{
  "code": "SUCCESS_WITH_MULTIPLE_PAYLOAD_OBJECTS",
  "payload": [
    {
      "id": "share-uuid",
      "shared_with_company": {
        "id": "uuid",
        "name": "Company Name",
        "serial_number": "COMPANY-12345"
      },
      "status": "accepted",
      "schema_ids": [1, 2],
      "responded_at": "2026-04-08T11:00:00.000Z"
    }
  ]
}
```

---

### 5. Get Pending Invitations
**GET** `/api/projects/shares/pending`

**Purpose**: Get all pending project share invitations for current company

**Response**:
```json
{
  "code": "SUCCESS_WITH_MULTIPLE_PAYLOAD_OBJECTS",
  "payload": [
    {
      "id": "share-uuid",
      "owner_company": {
        "id": "uuid",
        "name": "Owner Company",
        "serial_number": "OWNER-12345"
      },
      "status": "pending",
      "schema_ids": [1, 2, 3],
      "project": {
        "id": "project-uuid",
        "name": "Project Name",
        "serial_number": "PRJ-12345"
      },
      "notes": "Please review this project",
      "created_at": "2026-04-08T10:00:00.000Z"
    }
  ]
}
```

---

### 6. Accept/Reject Share Invitation
**POST** `/api/projects/shares/respond`

**Purpose**: Accept or reject a share invitation

**Request Body**:
```json
{
  "share_id": "uuid",
  "action": "accept"  // or "reject"
}
```

**Response**:
```json
{
  "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT",
  "payload": {
    "message": "Share accepted successfully",
    "action": "accept"
  }
}
```

---

### 7. Get Shared Projects (Shared With Me)
**GET** `/api/projects/shares/shared-with-me`

**Purpose**: Get all projects shared with current company (accepted)

**Response**:
```json
{
  "code": "SUCCESS_WITH_MULTIPLE_PAYLOAD_OBJECTS",
  "payload": [
    {
      "id": "share-uuid",
      "owner_company": {
        "id": "uuid",
        "name": "Owner Company"
      },
      "status": "accepted",
      "schema_ids": [1, 2],
      "project": {
        "id": "project-uuid",
        "name": "Shared Project",
        "serial_number": "PRJ-12345",
        "status": 1
      }
    }
  ]
}
```

---

### 8. Remove Share
**DELETE** `/api/projects/shares/{share_id}`

**Purpose**: Remove a share (owner only)

**Response**:
```json
{
  "code": "DELETED_SUCCESSFULLY"
}
```

---

### 9. List Projects (Includes Shared)
**GET** `/api/projects`

**Purpose**: Get all projects (owned + accepted shared projects)

**Note**: The existing project index API now automatically includes:
- Projects owned by current company
- Projects shared with current company (accepted status only)

This is handled automatically by the `Shareable` trait's global scope.

---

## How It Works

### Shareable Trait Magic

The `Shareable` trait adds a global scope that modifies ALL queries on the `ProjectManagement` model:

```php
// This query now automatically includes owned + accepted shared projects
$projects = ProjectManagement::all();

// You can still query only owned projects:
$ownedProjects = ProjectManagement::ownedOnly()->get();

// Or only shared projects:
$sharedProjects = ProjectManagement::sharedOnly()->get();
```

### Model Methods

Projects now have these convenient methods:

```php
$project = ProjectManagement::find($id);

// Check ownership
$project->isOwnedByCurrentCompany(); // true/false

// Check if shared with specific company
$project->isSharedWith($companyId); // true/false

// Share the project
$share = $project->shareWith(
    companyId: $targetCompanyId,
    schemaIds: [1, 2, 3],
    userId: Auth::id(),
    notes: 'Please review'
);

// Get sharing status with a company
$status = $project->getSharingStatus($companyId); // 'pending', 'accepted', 'rejected', or null
```

### Relationships

Projects have these relationships with shares:

```php
$project->shares; // All shares (pending, accepted, rejected)
$project->acceptedShares; // Only accepted shares
$project->pendingShares; // Only pending shares
$project->rejectedShares; // Only rejected shares
```

## Usage Workflow

### For Project Owner (Company A):

1. **Lookup Target Company**:
   ```
   GET /api/companies/by-serial-number?serial_number=COMPANYB-12345
   ```

2. **Get Available Schemas** (optional):
   ```
   GET /api/schemas
   ```

3. **Share Project**:
   ```
   POST /api/projects/share
   {
     "project_id": "project-uuid",
     "company_serial_number": "COMPANYB-12345",
     "schema_ids": [1, 2, 3]
   }
   ```

4. **Check Share Status**:
   ```
   GET /api/projects/{project_id}/shares
   ```

5. **Remove Share** (if needed):
   ```
   DELETE /api/projects/shares/{share_id}
   ```

### For Recipient Company (Company B):

1. **View Pending Invitations**:
   ```
   GET /api/projects/shares/pending
   ```

2. **Accept Invitation**:
   ```
   POST /api/projects/shares/respond
   {
     "share_id": "share-uuid",
     "action": "accept"
   }
   ```

3. **View Projects** (now includes shared project):
   ```
   GET /api/projects
   ```

4. **View All Shared Projects**:
   ```
   GET /api/projects/shares/shared-with-me
   ```

## Route Registration

Add these routes to your routes file:

```php
// Company routes
Route::get('/companies/by-serial-number', [CompanyController::class, 'getBySerialNumber']);

// Schema routes
Route::get('/schemas', [SchemaController::class, 'index']);
Route::get('/schemas/{id}', [SchemaController::class, 'show']);

// Project sharing routes
Route::post('/projects/share', [ProjectShareController::class, 'shareProject']);
Route::get('/projects/{project_id}/shares', [ProjectShareController::class, 'getProjectShares']);
Route::get('/projects/shares/pending', [ProjectShareController::class, 'getPendingInvitations']);
Route::get('/projects/shares/shared-with-me', [ProjectShareController::class, 'getSharedWithMe']);
Route::post('/projects/shares/respond', [ProjectShareController::class, 'respondToShare']);
Route::delete('/projects/shares/{share_id}', [ProjectShareController::class, 'removeShare']);
```

## Migration

Run the migration to create the `resource_shares` table:

```bash
php artisan migrate
```

## Adding Sharing to Other Models

To add sharing functionality to other models:

1. Replace `BelongsToTenant` trait with `Shareable` trait:
   ```php
   use App\Traits\Shareable;
   
   class YourModel extends Model {
       use Shareable; // Instead of BelongsToTenant
   }
   ```

2. The model automatically gets sharing functionality!

## Important Notes

- **Relationships**: When a shared project is accessed by the recipient company, all its relationships (manager, branch, etc.) are loaded from the owner company's data. Make sure your relationship queries don't filter by `tenant('id')` if you want them to work across companies.

- **Permissions**: Add proper permission checks in your controllers to ensure only authorized users can share projects.

- **Schema Filtering**: The `schema_ids` field is stored but not automatically enforced. You'll need to implement schema filtering logic in your application if needed.

- **Cascading Deletes**: If the owner company or shared company is deleted, the shares are automatically deleted via foreign key constraints.

## Future Enhancements

Consider implementing:
- Email notifications when projects are shared
- Webhook notifications for share status changes
- Audit logging for sharing activities
- Bulk sharing operations
- Share expiration dates
- Read-only vs full-access sharing permissions
