# Project Share Types Implementation

## Overview
This implementation adds **three independent dropdown lists** for project sharing:
- **النوع (Type)**: Main category (e.g., جهة حكومية, جهة مالكة, استشاري)
- **العلاقة (Relation)**: Relationship type (e.g., مرخصة لجميع المراحل, شريك استراتيجي)
- **الدور (Role)**: Specific role (e.g., جهة رقابية, مستثمر رئيسي)

**Important**: Types, Relations, and Roles are **independent** - they don't depend on each other. Users can select any combination.

## Database Structure

### Tables Created:
1. **project_share_types** - Stores all types, relations, and roles with translations
2. **resource_shares** - Updated with `type_id`, `relation_id`, `role_id` columns

### Schema:
```sql
project_share_types:
- id (bigint)
- name (json) - {"ar": "...", "en": "..."}
- level (string) - 'type', 'relation', 'role'
- is_active (boolean)
- timestamps

resource_shares:
+ type_id (bigint, nullable, FK to project_share_types)
+ relation_id (bigint, nullable, FK to project_share_types)
+ role_id (bigint, nullable, FK to project_share_types)
```

## Models

### ProjectShareType
**Location**: `modules/Shared/ResourceShare/Models/ProjectShareType.php`

**Scopes**:
- `types()` - Get only type level (النوع)
- `relations()` - Get only relation level (العلاقة)
- `roles()` - Get only role level (الدور)
- `active()` - Get only active types

### ResourceShare
**Updated relationships**:
- `type()` - BelongsTo ProjectShareType
- `relation()` - BelongsTo ProjectShareType
- `role()` - BelongsTo ProjectShareType

## API Endpoints

### Base URL
All endpoints are under: `/api/v1/resource-shares/`

### 1. Get All Types (النوع)
```http
GET /project-share-types
```

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "name": {"ar": "جهة حكومية", "en": "Government Entity"},
      "level": "type"
    },
    {
      "id": 2,
      "name": {"ar": "جهة مالكة", "en": "Owner Entity"},
      "level": "type"
    }
  ]
}
```

### 2. Get All Relations (العلاقة)
```http
GET /project-share-types/relations
```

**Response**:
```json
{
  "data": [
    {
      "id": 10,
      "name": {"ar": "مرخصة لجميع المراحل", "en": "Licensed for All Stages"},
      "level": "relation"
    },
    {
      "id": 11,
      "name": {"ar": "شريك استراتيجي", "en": "Strategic Partner"},
      "level": "relation"
    }
  ]
}
```

### 3. Get All Roles (الدور)
```http
GET /project-share-types/roles
```

**Response**:
```json
{
  "data": [
    {
      "id": 20,
      "name": {"ar": "جهة رقابية", "en": "Regulatory Authority"},
      "level": "role"
    },
    {
      "id": 21,
      "name": {"ar": "مستثمر رئيسي", "en": "Main Investor"},
      "level": "role"
    }
  ]
}
```

### 4. Get All Types, Relations, and Roles (Combined)
```http
GET /project-share-types/all
```

**Response**:
```json
{
  "data": {
    "types": [
      {
        "id": 1,
        "name": {"ar": "جهة حكومية", "en": "Government Entity"},
        "level": "type"
      }
    ],
    "relations": [
      {
        "id": 10,
        "name": {"ar": "مرخصة لجميع المراحل", "en": "Licensed for All Stages"},
        "level": "relation"
      }
    ],
    "roles": [
      {
        "id": 20,
        "name": {"ar": "جهة رقابية", "en": "Regulatory Authority"},
        "level": "role"
      }
    ]
  }
}
```

## Seeded Data

### Types (النوع) - 9 items:
1. جهة حكومية (Government Entity)
2. جهة مالكة (Owner Entity)
3. استشاري (Consultant)
4. مقاول رئيسي (Main Contractor)
5. مقاول باطن (Subcontractor)
6. مورد (Supplier)
7. شريك (Partner)
8. إدارة داخلية (Internal Management)
9. جهة رقابية (Supervisory Entity)

### Relations (العلاقة) - 9 items:
1. مرخصة لجميع المراحل (Licensed for All Stages)
2. شريك استراتيجي (Strategic Partner)
3. مشرف هندسي (Engineering Supervisor)
4. مقاول عام (General Contractor)
5. مقاول فرعي (Sub Contractor)
6. مورد مواد (Material Supplier)
7. ممول مشروع (Project Financer)
8. إدارة فنية (Technical Management)
9. مدقق خارجي (External Auditor)

### Roles (الدور) - 9 items:
1. جهة رقابية (Regulatory Authority)
2. مستثمر رئيسي (Main Investor)
3. مشرف هندسي (Engineering Supervisor)
4. مقاول عام (General Contractor)
5. مقاول فرعي (Sub Contractor)
6. مورد مواد (Material Supplier)
7. ممول مشروع (Project Financer)
8. إدارة فنية (Technical Management)
9. مدقق خارجي (External Auditor)

## Usage in Project Sharing

When creating or viewing a resource share, you can now include:

```json
{
  "shareable_id": "project-uuid",
  "shareable_type": "Modules\\Project\\ProjectManagement\\Models\\ProjectManagement",
  "shared_with_company_id": "company-uuid",
  "type_id": 1,
  "relation_id": 10,
  "role_id": 20,
  "schema_ids": ["schema-1", "schema-2"]
}
```

The response will include full type information:
```json
{
  "type": {
    "id": 1,
    "name": {"ar": "جهة حكومية", "en": "Government Entity"}
  },
  "relation": {
    "id": 10,
    "name": {"ar": "مرخصة لجميع المراحل", "en": "Licensed for All Stages"}
  },
  "role": {
    "id": 20,
    "name": {"ar": "جهة رقابية", "en": "Regulatory Authority"}
  }
}
```

## Running Migrations and Seeder

```bash
# Run migrations
php artisan migrate

# Run seeder
php artisan db:seed --class=ProjectShareTypeSeeder
```

## Files Created/Modified

### New Files:
1. `database/migrations/2026_04_19_190000_create_project_share_types_table.php`
2. `database/migrations/2026_04_19_190100_add_type_columns_to_resource_shares_table.php`
3. `modules/Shared/ResourceShare/Models/ProjectShareType.php`
4. `database/seeders/ProjectShareTypeSeeder.php`
5. `modules/Shared/ResourceShare/Controllers/ProjectShareTypeController.php`

### Modified Files:
1. `modules/Shared/ResourceShare/Models/ResourceShare.php` - Added type relationships
2. `modules/Shared/ResourceShare/Presenters/ResourceSharePresenter.php` - Added type data to response
3. `modules/Shared/ResourceShare/Repositories/ResourceShareRepository.php` - Added eager loading
4. `modules/Shared/ResourceShare/Resources/routes/api.php` - Added new routes

## Frontend Integration

**Three Independent Dropdowns**:
- Load all three lists on page load using `/project-share-types/all` endpoint
- Or load each list separately:
  - Types: `/project-share-types`
  - Relations: `/project-share-types/relations`
  - Roles: `/project-share-types/roles`
- Users can select **any combination** - selections are independent

All endpoints support Arabic and English translations automatically based on the app locale.
