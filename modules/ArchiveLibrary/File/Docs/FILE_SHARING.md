# File Sharing Feature

## Overview
This feature allows users to share files with other users via email. The system syncs the shared users in the `file_shares` table and generates a shareable URL.

## Implementation Details

### API Endpoint
- **URL**: `POST /api/files/share`
- **Authentication**: Required (Bearer Token)
- **Tenancy**: Multi-tenant enabled

### Request Format
```json
{
    "file_id": "uuid-of-the-file",
    "user_ids": [
        "uuid-of-user-1",
        "uuid-of-user-2",
        "uuid-of-user-3"
    ]
}
```

### Validation Rules
- `file_id`: Required, must be a valid UUID, must exist in files table
- `user_ids`: Required, must be an array with at least one user
- `user_ids.*`: Each must be a valid UUID and exist in users table

### Response Format
```json
{
    "status": true,
    "message": "File shared successfully",
    "data": {
        "file": {
            "id": "uuid",
            "name": "File Name",
            "reference_number": "REF-001",
            "start_date": "2025-01-01",
            "end_date": "2025-12-31",
            "folder_id": "uuid",
            "access_type": "private",
            "media_urls": ["https://..."]
        },
        "share_url": "https://example.com/api/shared-files/{file_id}",
        "shared_with_count": 3
    }
}
```

## Database Structure

### file_shares Table
- `id`: UUID (Primary Key)
- `file_id`: UUID (Foreign Key to files table)
- `user_id`: UUID (Foreign Key to users table)
- `created_at`: Timestamp
- `updated_at`: Timestamp

The relationship uses Laravel's `sync()` method, which:
- Adds new user associations
- Removes users no longer in the list
- Preserves existing associations

## Components Created

### 1. ShareFileRequest
**Location**: `modules/ArchiveLibrary/File/Requests/ShareFileRequest.php`
- Validates input data
- Provides getter methods for file_id and user_ids
- Custom validation error messages

### 2. FileRepository::shareFile()
**Location**: `modules/ArchiveLibrary/File/Repositories/FileRepository.php`
- Handles database transaction
- Syncs users in file_shares table
- Returns refreshed file model

### 3. FileCRUDService::shareFile()
**Location**: `modules/ArchiveLibrary/File/Services/FileCRUDService.php`
- Calls repository method
- Generates share URL (dummy for now)
- Returns formatted response data

### 4. FileController::shareFile()
**Location**: `modules/ArchiveLibrary/File/Controllers/FileController.php`
- Handles HTTP request
- Calls service method
- Returns JSON response
- Includes TODO for email notification implementation

### 5. Route Registration
**Location**: `modules/ArchiveLibrary/File/Resources/routes/api.php`
- Registered as `POST /api/files/share`
- Protected by auth:api middleware
- Tenancy-enabled

## Email Notification (TODO)

The current implementation includes a placeholder for sending emails to users. To implement:

1. Create a notification class:
```php
php artisan make:notification FileSharedNotification
```

2. Update the controller to send notifications:
```php
use Illuminate\Support\Facades\Notification;
use App\Notifications\FileSharedNotification;

// In shareFile method:
$users = \Modules\User\Models\User::whereIn('id', $request->getUserIds())->get();
Notification::send($users, new FileSharedNotification($result['share_url'], $result['file']));
```

3. Implement the notification with email channel and mail message.

## Testing

A Postman collection has been created for testing:
**Location**: `modules/ArchiveLibrary/File/Postman/FileShare_API.postman_collection.json`

### Import Instructions:
1. Open Postman
2. Click "Import"
3. Select the JSON file
4. Configure environment variables:
   - `base_url`: Your API base URL
   - `token`: Your authentication token
   - `tenant_id`: Your tenant ID
   - `file_id`: ID of file to share
   - `user_id_1`, `user_id_2`, `user_id_3`: User IDs to share with

## Usage Example

```bash
curl -X POST "http://localhost:8000/api/files/share" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Tenant: YOUR_TENANT_ID" \
  -H "Content-Type: application/json" \
  -d '{
    "file_id": "9d2f3a1b-4c5d-6e7f-8a9b-0c1d2e3f4a5b",
    "user_ids": [
      "8c1e2d3b-4a5b-6c7d-8e9f-0a1b2c3d4e5f",
      "7b0c1d2e-3f4a-5b6c-7d8e-9f0a1b2c3d4e"
    ]
  }'
```

## Future Enhancements

1. **Email Notifications**: Implement actual email sending to users
2. **Share URL Functionality**: Create endpoint to access shared files via the generated URL
3. **Permission Levels**: Add different share permission levels (view, edit, download)
4. **Expiration**: Add expiration dates for shared links
5. **Activity Tracking**: Log share activities for audit purposes
6. **Notification Preferences**: Allow users to opt-in/out of share notifications
