# Official Documents Folder Seeder

## Overview
This seeder creates a predefined "المستندات الرسمية" (Official Documents) folder with a fixed UUID that can be referenced throughout the application.

## Running the Seeder

### Run the seeder:
```bash
php artisan db:seed --class="Modules\ArchiveLibrary\Folder\Database\Seeders\OfficialDocumentsFolderSeeder"
```

### For tenant databases:
```bash
php artisan tenants:seed --class="Modules\ArchiveLibrary\Folder\Database\Seeders\OfficialDocumentsFolderSeeder"
```

## Configuration

The folder UUID and name are defined in `modules/ArchiveLibrary/Folder/Resources/config/config.php`:

```php
'official_documents_uuid' => '00000000-0000-0000-0000-000000000001',
'official_documents_name' => 'المستندات الرسمية',
```

## Usage in Code

### Get the official documents folder UUID:
```php
$officialDocsFolderId = config('folder.official_documents_uuid');
```

### Get the official documents folder name:
```php
$officialDocsName = config('folder.official_documents_name');
```

### Retrieve the folder:
```php
use Modules\ArchiveLibrary\Folder\Models\Folder;

$officialDocsFolder = Folder::find(config('folder.official_documents_uuid'));
```

### Create a file in the official documents folder:
```php
use Modules\ArchiveLibrary\File\Models\File;

$file = File::create([
    'name' => 'Important Document',
    'folder_id' => config('folder.official_documents_uuid'),
    'access_type' => 'private',
    // ... other fields
]);
```

## Folder Properties

- **UUID**: `00000000-0000-0000-0000-000000000001` (fixed, defined in config)
- **Name**: `المستندات الرسمية` (Official Documents in Arabic)
- **Parent**: `null` (root level folder)
- **Access Type**: `private` (default)
- **Company ID**: Set to current tenant's company_id

## Notes

- The seeder is **idempotent** - running it multiple times will not create duplicates
- The folder is created at the root level (no parent folder)
- The UUID is intentionally simple (`00000000-0000-0000-0000-000000000001`) for easy reference
- The folder is tenant-aware and will be created per company/tenant
- All operations are wrapped in a database transaction for data integrity
