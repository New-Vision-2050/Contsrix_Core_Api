# ProjectType Module - Implementation Summary

## Overview
Complete ProjectType module with hierarchical tree structure using AsTree trait, schema support, and multi-tenancy.

## Database Structure

### 1. **schemas** table
- `id` (primary key)
- `name` (string)
- Simple lookup table for schema types

### 2. **project_types** table
- `id` (integer primary key)
- `name` (string)
- `icon` (string, nullable)
- `parent_id` (integer, nullable) - For tree hierarchy
- `reference_project_type_id` (integer, nullable) - Reference to second level type for schema inheritance
- `company_id` (UUID, nullable) - Multi-tenancy support
- `path` (text, nullable) - AsTree path for efficient queries
- `is_created` (boolean, default: true) - false for seeded data, true for user-created
- `is_have_schema` (boolean, default: false) - true if type has schemas
- `is_active` (boolean, default: true)

### 3. **project_type_schemas** table (Pivot)
- `id` (primary key)
- `project_type_id` (foreign key to project_types)
- `schema_id` (foreign key to schemas)
- Unique constraint on (project_type_id, schema_id)

## Seeded Data

### Schemas (8 types):
1. بيانات المشروع (Project Details)
2. المعلومات المالية (Financial Information)
3. أطراف العمل (Work Parties)
4. الكتاب (Books/Documents)
5. المقاولون (Contractors)
6. المستشارون (Consultants)
7. الموردون (Suppliers)
8. المراقبون (Supervisors)

### Project Types (3 root + 8 second level):
**Root Level (is_created=false):**
- Construction Projects
- Infrastructure Projects
- Technology Projects

**Second Level (is_created=false, is_have_schema=true):**
- Residential Buildings
- Commercial Buildings
- Industrial Facilities
- Roads & Highways
- Bridges & Tunnels
- Utilities & Networks
- Software Development
- IT Infrastructure

## API Endpoints

### Standard CRUD:
- `GET /api/v1/project-types` - List all (paginated)
- `POST /api/v1/project-types` - Create new project type
- `GET /api/v1/project-types/{id}` - Get single project type
- `PUT /api/v1/project-types/{id}` - Update project type
- `DELETE /api/v1/project-types/{id}` - Delete project type
- `POST /api/v1/project-types/export` - Export to Excel/CSV

### Tree Navigation:
- `GET /api/v1/project-types/roots` - Get root level project types
- `GET /api/v1/project-types/{id}/children` - Get direct children

### Second Level & Schemas:
- `POST /api/v1/project-types/second-level` - Create second level with schemas
- `GET /api/v1/project-types/filter` - Filter project types (second_level, parent_id, etc.)
- `GET /api/v1/project-types/{id}/schemas` - Get schemas for any project type

## Schema Inheritance Logic

When calling `GET /api/v1/project-types/{id}/schemas`:

1. **If project type is second level with schemas**: Returns its own schemas
2. **If has reference_project_type_id**: Returns reference type's schemas
3. **If child of second level**: Returns parent's schemas
4. **Otherwise**: Returns empty collection

## Create Second Level Project Type

**Endpoint:** `POST /api/v1/project-types/second-level`

**Request Body:**
```json
{
    "name": "Residential Villas",
    "icon": "villa-icon",
    "parent_id": 1,
    "reference_project_type_id": null,
    "schema_ids": [1, 2, 3],
    "is_active": true
}
```

**Features:**
- Automatically sets `company_id` from tenant context
- Automatically sets `is_have_schema = true`
- Automatically sets `is_created = true`
- Attaches selected schemas to the project type

## Filter API

**Endpoint:** `GET /api/v1/project-types/filter`

**Query Parameters:**
- `second_level` (boolean) - Filter for second level types only
- `parent_id` (integer) - Filter by parent ID
- `is_have_schema` (boolean) - Filter by schema availability
- `is_created` (boolean) - Filter by user-created vs seeded

**Example:**
```
GET /api/v1/project-types/filter?second_level=true&parent_id=1&is_have_schema=true
```

## Key Features

### 1. **Hierarchical Structure**
- Uses `nevadskiy/laravel-tree` AsTree trait
- Efficient path-based queries
- Supports unlimited nesting levels

### 2. **Multi-Tenancy**
- Uses `CustomBelongsToTenant` trait
- Company-scoped queries
- Tenant middleware on all routes

### 3. **Schema System**
- Flexible schema assignment
- Schema inheritance from parent/reference types
- Many-to-many relationship

### 4. **Seeded vs User-Created**
- `is_created = false` for system-seeded data
- `is_created = true` for user-created types
- Allows filtering and different business logic

## Running Migrations & Seeders

```bash
# Run migrations
php artisan migrate --path=modules/Project/ProjectType/Database/Migrations

# Run seeders
php artisan db:seed --class=Modules\\Project\\ProjectType\\Database\\Seeders\\SchemaSeeder
php artisan db:seed --class=Modules\\Project\\ProjectType\\Database\\Seeders\\ProjectTypeSeeder
```

## Postman Collection

File: `ProjectType_API.postman_collection.json`

Includes all endpoints with:
- Sample requests
- Bearer token authentication
- Query parameters
- Response examples
- Variable usage ({{url}}, {{token}}, {{project_type_id}})

## Models & Relationships

### ProjectType Model
- `parent()` - BelongsTo self
- `children()` - HasMany self (from AsTree)
- `company()` - BelongsTo Company
- `referenceProjectType()` - BelongsTo ProjectType
- `schemas()` - BelongsToMany Schema

### Schema Model
- `projectTypes()` - BelongsToMany ProjectType

### ProjectTypeSchema Model (Pivot)
- `projectType()` - BelongsTo ProjectType
- `schema()` - BelongsTo Schema

## Architecture Pattern

Follows established Constrix patterns:
- **Request** → **DTO** → **Service** → **Repository** → **Model**
- **Presenter** for response formatting
- **Handler** for update/delete operations
- **Command** pattern for updates
- **Filter** support for list queries
- **Export** functionality included

## Notes

- All second level project types should have `is_have_schema = true`
- Schema inheritance works automatically based on hierarchy
- Reference project type allows copying schemas from another type
- Tenant middleware ensures company isolation
- AsTree provides efficient tree operations
