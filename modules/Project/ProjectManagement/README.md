# Project Management Module

## Overview
Complete CRUD module for managing Projects in the Constrix API system. This module handles project creation, updates, listing, deletion, and export functionality with comprehensive relationships to project types, employees, clients, branches, and more.

## Database Schema

### Table: `projects`

| Column | Type | Required | Description |
|--------|------|----------|-------------|
| `id` | UUID | Yes | Primary key |
| `project_type_id` | BigInteger | Yes | Foreign key to project_types |
| `sub_project_type_id` | BigInteger | Yes | Foreign key to project_types (sub level) |
| `sub_sub_project_type_id` | BigInteger | Yes | Foreign key to project_types (sub-sub level) |
| `name` | String | No | Project name |
| `responsible_employee_id` | UUID | No | Foreign key to users |
| `client_id` | UUID | No | Foreign key to clients |
| `project_classification_id` | UUID | No | Classification identifier |
| `cost_center_branch_id` | UUID | No | Foreign key to branches |
| `management_id` | UUID | No | Foreign key to management_hierarchies |
| `currency_id` | UUID | No | Foreign key to currencies |
| `project_value` | Decimal(15,2) | No | Project monetary value |
| `company_id` | UUID | Yes | Foreign key to companies |
| `status` | Integer | Yes | Status (-1: suspended, 0: inactive, 1: active) |
| `created_at` | Timestamp | Auto | Creation timestamp |
| `updated_at` | Timestamp | Auto | Last update timestamp |

### Relationships
- **projectType**: BelongsTo ProjectType (main type)
- **subProjectType**: BelongsTo ProjectType (sub type)
- **subSubProjectType**: BelongsTo ProjectType (sub-sub type)
- **responsibleEmployee**: BelongsTo User
- **client**: BelongsTo Client
- **costCenterBranch**: BelongsTo Branch
- **management**: BelongsTo ManagementHierarchy
- **currency**: BelongsTo Currency
- **company**: BelongsTo Company

## API Endpoints

### Base URL
```
/api/v1/projects
```

### 1. List Projects
**GET** `/api/v1/projects`

**Query Parameters:**
- `page` (integer): Page number (default: 1)
- `per_page` (integer): Items per page (default: 15)
- `name` (string): Filter by project name (partial match)
- `project_type_id` (integer): Filter by project type
- `sub_project_type_id` (integer): Filter by sub project type
- `sub_sub_project_type_id` (integer): Filter by sub-sub project type
- `responsible_employee_id` (uuid): Filter by responsible employee
- `client_id` (uuid): Filter by client
- `management_id` (uuid): Filter by management
- `status` (integer): Filter by status (-1, 0, 1)

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Project Name",
      "project_value": "100000.50",
      "status": 1,
      "project_type_name": "Type Name",
      "responsible_employee_name": "Employee Name",
      "client_name": "Client Name",
      "created_at": "2025-02-20 00:00:00",
      "updated_at": "2025-02-20 00:00:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 100
  }
}
```

### 2. Create Project
**POST** `/api/v1/projects`

**Request Body:**
```json
{
  "project_type_id": 1,
  "sub_project_type_id": 2,
  "sub_sub_project_type_id": 3,
  "name": "New Project Name",
  "responsible_employee_id": "uuid",
  "client_id": "uuid",
  "project_classification_id": "uuid",
  "cost_center_branch_id": "uuid",
  "management_id": "uuid",
  "currency_id": "uuid",
  "project_value": 100000.50,
  "status": 1
}
```

**Validation Rules:**
- `project_type_id`: required, integer, exists in project_types
- `sub_project_type_id`: required, integer, exists in project_types
- `sub_sub_project_type_id`: required, integer, exists in project_types
- `name`: nullable, string, max 255 characters
- `responsible_employee_id`: nullable, uuid, exists in users
- `client_id`: nullable, uuid, exists in clients
- `project_classification_id`: nullable, uuid
- `cost_center_branch_id`: nullable, uuid, exists in branches
- `management_id`: nullable, uuid, exists in management_hierarchies
- `currency_id`: nullable, uuid, exists in currencies
- `project_value`: nullable, numeric, min 0
- `status`: nullable, integer, in (-1, 0, 1)

**Response:**
```json
{
  "data": {
    "id": "uuid",
    "name": "New Project Name",
    "project_value": "100000.50",
    "status": 1,
    "project_type": {
      "id": 1,
      "name": "Type Name"
    },
    "sub_project_type": {
      "id": 2,
      "name": "Sub Type Name"
    },
    "sub_sub_project_type": {
      "id": 3,
      "name": "Sub-Sub Type Name"
    },
    "responsible_employee": {
      "id": "uuid",
      "name": "Employee Name",
      "email": "email@example.com"
    },
    "client": {
      "id": "uuid",
      "name": "Client Name"
    },
    "cost_center_branch": {
      "id": "uuid",
      "name": "Branch Name"
    },
    "management": {
      "id": "uuid",
      "name": "Management Name"
    },
    "currency": {
      "id": "uuid",
      "name": "Currency Name",
      "code": "USD"
    },
    "created_at": "2025-02-20 00:00:00",
    "updated_at": "2025-02-20 00:00:00"
  }
}
```

### 3. Get Project
**GET** `/api/v1/projects/{id}`

**Response:** Same as Create response with all relationships loaded.

### 4. Update Project
**PUT** `/api/v1/projects/{id}`

**Request Body:** Same as Create endpoint

**Response:** Same as Create response

### 5. Delete Project
**DELETE** `/api/v1/projects/{id}`

**Response:**
```json
{
  "message": "Deleted successfully"
}
```

### 6. Export Projects
**POST** `/api/v1/projects/export`

**Request Body:**
```json
{
  "format": "xlsx",
  "ids": []
}
```

**Parameters:**
- `format`: xlsx or csv (default: xlsx)
- `ids`: array of project IDs to export (empty for all)

**Response:** File download (Excel or CSV)

## Module Structure

```
modules/Project/ProjectManagement/
├── Commands/
│   └── UpdateProjectManagementCommand.php
├── Controllers/
│   └── ProjectManagementController.php
├── Database/
│   ├── Migrations/
│   │   └── 2025_02_20_000001_create_projects_table.php
│   └── factories/
│       └── ProjectManagementFactory.php
├── DTO/
│   └── CreateProjectManagementDTO.php
├── Exports/
│   └── ProjectManagementExport.php
├── Filters/
│   └── ProjectManagementFilter.php
├── Handlers/
│   ├── DeleteProjectManagementHandler.php
│   └── UpdateProjectManagementHandler.php
├── Models/
│   └── ProjectManagement.php
├── Presenters/
│   └── ProjectManagementPresenter.php
├── Providers/
│   └── ProjectManagementServiceProvider.php
├── Repositories/
│   └── ProjectManagementRepository.php
├── Requests/
│   ├── CreateProjectManagementRequest.php
│   ├── UpdateProjectManagementRequest.php
│   ├── GetProjectManagementRequest.php
│   ├── GetProjectManagementListRequest.php
│   ├── DeleteProjectManagementRequest.php
│   └── ExportProjectManagementRequest.php
├── Resources/
│   └── routes/
│       └── api.php
├── Services/
│   └── ProjectManagementCRUDService.php
└── module.json
```

## Postman Collection

A complete Postman collection is available at:
```
ProjectManagement_API.postman_collection.json
```

The collection includes:
- All CRUD endpoints
- Sample requests with variables
- Response examples
- Filter parameters
- Export functionality

### Postman Variables
- `url`: http://localhost/api/v1
- `token`: Your bearer token
- `project_id`: Project UUID
- `project_type_id`: Project type ID (integer)
- `sub_project_type_id`: Sub project type ID (integer)
- `sub_sub_project_type_id`: Sub-sub project type ID (integer)
- `employee_id`: Employee UUID
- `client_id`: Client UUID
- `branch_id`: Branch UUID
- `management_id`: Management UUID
- `currency_id`: Currency UUID

## Usage Examples

### Create a Project
```php
use Modules\Project\ProjectManagement\Services\ProjectManagementCRUDService;
use Modules\Project\ProjectManagement\DTO\CreateProjectManagementDTO;

$service = app(ProjectManagementCRUDService::class);

$dto = new CreateProjectManagementDTO(
    projectTypeId: 1,
    subProjectTypeId: 2,
    subSubProjectTypeId: 3,
    name: 'New Project',
    projectValue: 100000.50,
    status: 1
);

$project = $service->create($dto);
```

### List Projects with Filters
```php
$projects = $service->list(
    page: 1,
    perPage: 15
);
```

### Update a Project
```php
use Ramsey\Uuid\Uuid;
use Modules\Project\ProjectManagement\Commands\UpdateProjectManagementCommand;

$command = new UpdateProjectManagementCommand(
    id: Uuid::fromString('project-uuid'),
    projectTypeId: 1,
    subProjectTypeId: 2,
    subSubProjectTypeId: 3,
    name: 'Updated Project Name',
    status: 1
);

$handler->handle($command);
```

## Features

✅ Complete CRUD operations
✅ Multi-level project type hierarchy (type, sub-type, sub-sub-type)
✅ Comprehensive relationships (employees, clients, branches, management, currency)
✅ Advanced filtering and search
✅ Pagination support
✅ Export to Excel/CSV
✅ Multi-tenancy support
✅ Status management (active, inactive, suspended)
✅ Decimal precision for project values
✅ Eager loading to prevent N+1 queries
✅ Postman collection for API testing

## Notes

1. **Project Types**: The module uses integer IDs for project types (not UUIDs) as they reference the existing `project_types` table.

2. **Foreign Keys**: Only essential foreign keys are enforced in the migration. Optional foreign keys are commented out and can be enabled when the referenced tables are confirmed to exist.

3. **Multi-tenancy**: The module uses the `BelongsToTenant` trait and includes tenancy middleware for proper company isolation.

4. **Status Values**:
   - `-1`: Suspended
   - `0`: Inactive
   - `1`: Active (default)

5. **Relationships**: All relationships are eager-loaded in list and detail endpoints to optimize query performance.

## Migration

To run the migration:
```bash
php artisan migrate --path=modules/Project/ProjectManagement/Database/Migrations
```

To rollback:
```bash
php artisan migrate:rollback --path=modules/Project/ProjectManagement/Database/Migrations
```

## Testing

Import the Postman collection and set the required variables to test all endpoints. Make sure to:
1. Set your authentication token
2. Create project types first (or use existing ones)
3. Update variable values with actual IDs from your database
4. Test in order: Create → List → Get → Update → Delete

## Support

For issues or questions, refer to the main Constrix API documentation or contact the development team.
