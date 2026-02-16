# ContractualRelationship Module

## Overview
This module manages user contractual relationship information, similar to the ContactInfo module. It provides a createOrUpdate API for managing contractual relationship data for users.

## Database Structure

### Tables Created:
1. **contractual_relationship_types** - Stores types of contractual relationships
   - id (UUID)
   - name (string)
   - is_active (boolean)
   - timestamps

2. **contractual_relationships** - Stores user contractual relationship data
   - id (UUID)
   - company_id (UUID)
   - global_id (UUID)
   - contractual_relationship_type_id (UUID, foreign key)
   - employment_name (string, nullable)
   - registration_number (string, nullable)
   - timestamps

## Installation Steps

### 1. Run Migrations
```bash
php artisan migrate --path=modules/UserInfo/ContractualRelationship/Database/Migrations
```

### 2. Run Seeder
```bash
php artisan db:seed --class=Modules\\UserInfo\\ContractualRelationship\\Database\\Seeders\\ContractualRelationshipTypeSeeder
```

This will seed the following contractual relationship types:
- Full-time Employee
- Part-time Employee
- Contractor
- Freelancer
- Consultant
- Temporary Worker
- Intern

## API Endpoints

### Base URL
`/api/v1/contractual-relationship`

### Endpoints

#### 1. Get Contractual Relationship Types
**GET** `/types`

Retrieves all active contractual relationship types for dropdown/selection purposes.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": "uuid",
            "name": "Full-time Employee",
            "is_active": true
        },
        {
            "id": "uuid",
            "name": "Part-time Employee",
            "is_active": true
        },
        {
            "id": "uuid",
            "name": "Contractor",
            "is_active": true
        }
    ]
}
```

#### 2. Get Contractual Relationship
**GET** `/{user_id}`

Retrieves the contractual relationship information for a specific user.

**Response:**
```json
{
    "id": "uuid",
    "contractual_relationship_type_id": "uuid",
    "contractual_relationship_type": {
        "id": "uuid",
        "name": "Full-time Employee"
    },
    "employment_name": "Senior Developer",
    "registration_number": "EMP-12345"
}
```

#### 3. Create or Update Contractual Relationship
**PUT** `/{user_id}`

Creates or updates the contractual relationship information for a specific user.

**Request Body:**
```json
{
    "contractual_relationship_type_id": "uuid",
    "employment_name": "Senior Developer",
    "registration_number": "EMP-12345"
}
```

**Validation Rules:**
- `contractual_relationship_type_id`: required, must be a valid UUID and exist in contractual_relationship_types table
- `employment_name`: optional, string, max 255 characters
- `registration_number`: optional, string, max 255 characters

**Response:**
```json
{
    "id": "uuid",
    "contractual_relationship_type_id": "uuid",
    "contractual_relationship_type": {
        "id": "uuid",
        "name": "Full-time Employee"
    },
    "employment_name": "Senior Developer",
    "registration_number": "EMP-12345"
}
```

## Module Structure

```
ContractualRelationship/
├── Commands/
│   └── UpdateContractualRelationshipCommand.php
├── Controllers/
│   └── ContractualRelationshipController.php
├── Database/
│   ├── Migrations/
│   │   ├── 2025_02_10_000001_create_contractual_relationship_types_table.php
│   │   └── 2025_02_10_000002_create_contractual_relationships_table.php
│   └── Seeders/
│       └── ContractualRelationshipTypeSeeder.php
├── Models/
│   ├── ContractualRelationship.php
│   └── ContractualRelationshipType.php
├── Presenters/
│   └── ContractualRelationshipPresenter.php
├── Providers/
│   └── ContractualRelationshipServiceProvider.php
├── Repositories/
│   └── ContractualRelationshipRepository.php
├── Requests/
│   ├── GetContractualRelationshipRequest.php
│   └── UpdateContractualRelationshipRequest.php
├── Resources/
│   └── routes/
│       └── api.php
├── Services/
│   └── ContractualRelationshipCRUDService.php
└── module.json
```

## Features

- **CreateOrUpdate Pattern**: Similar to ContactInfo module, uses a single endpoint to create or update records
- **Type Management**: Predefined contractual relationship types with seeder
- **User Association**: Links to users via company_id and global_id
- **Validation**: Comprehensive request validation
- **Relationship Loading**: Automatically loads contractual relationship type information

## Usage Example

```php
// In your application
use Modules\UserInfo\ContractualRelationship\Services\ContractualRelationshipCRUDService;

$service = app(ContractualRelationshipCRUDService::class);

// Get contractual relationship
$relationship = $service->get($companyId, $globalId);

// Create or update
$command = new UpdateContractualRelationshipCommand(
    company_id: $companyId,
    global_id: $globalId,
    contractual_relationship_type_id: $typeId,
    employment_name: 'Senior Developer',
    registration_number: 'EMP-12345'
);

$relationship = $service->create($command);
```

## Notes

- The module follows the same pattern as ContactInfo module
- Uses UUID for all IDs
- Supports multi-tenancy through company_id
- Foreign key constraint uses shortened name 'cr_type_id_foreign' to avoid MySQL 64-character limit
