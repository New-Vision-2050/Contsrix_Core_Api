# Project-Specific Roles & Permissions System - Implementation Guide

## Overview

This document describes the complete implementation of a project-specific roles and permissions system for the Constrix API. This system allows employees within a project to have specific roles, and these roles and permissions are public/shared across all companies that the project is shared with.

## Key Features

✅ **Project-Specific Roles**: Create custom roles for each project  
✅ **Project-Specific Permissions**: Granular permissions following `submodule.action` pattern  
✅ **Auto Admin Role**: Automatically creates "Project Admin" role when a project is created  
✅ **Auto Assignment**: Manager and creator automatically assigned as admins  
✅ **Translatable Permissions**: Full Arabic and English support  
✅ **Middleware Protection**: Route-level permission checking  
✅ **Shared Across Companies**: Roles and permissions work across shared projects  
✅ **Complete API**: Full CRUD operations for roles and permissions  

---

## Database Schema

### New Tables Created

#### 1. `project_roles`
- Stores roles specific to each project
- Fields: `id`, `project_id`, `name`, `slug`, `description`, `is_default`, `is_active`, `timestamps`

#### 2. `project_permissions`
- Stores all available project permissions with translations
- Fields: `id`, `name`, `submodule`, `action`, `title` (JSON), `description`, `is_active`, `timestamps`

#### 3. `project_role_permissions`
- Many-to-many pivot table linking roles to permissions
- Fields: `project_role_id`, `project_permission_id`, `timestamps`

### Updated Tables

#### 1. `projects`
- Added: `created_by_user_id` - Tracks who created the project

#### 2. `project_employees`
- Added: `project_role_id` - Links employee to their project role

---

## Permission Pattern

Permissions follow the pattern: `submodule.action`

### Examples:
- `employee.create` - Create employees in project
- `employee.update` - Update employee information
- `employee.delete` - Delete employees from project
- `archiveLibrary.create` - Create archive library items
- `task.view` - View tasks
- `role.assignPermission` - Assign permissions to roles

### Actions:
- `view` - View details
- `list` - List/index items
- `create` - Create new items
- `update` - Update existing items
- `delete` - Delete items

---

## Installation & Setup

### Step 1: Run Migrations

```bash
php artisan migrate
```

This will create:
- `2026_04_14_000001_add_created_by_to_projects_table`
- `2026_04_14_000002_create_project_roles_table`
- `2026_04_14_000003_create_project_permissions_table`
- `2026_04_14_000004_create_project_role_permissions_table`
- `2026_04_14_000005_add_role_to_project_employees_table`

### Step 2: Seed Permissions

```bash
php artisan db:seed --class=Modules\\Project\\ProjectManagement\\Database\\Seeders\\ProjectPermissionsSeeder
```

This creates default permissions for:
- Employee Management (view, list, create, update, delete)
- Archive Library (view, list, create, update, delete)
- Project Settings (view, update, delete)
- Role Management (view, list, create, update, delete, assignPermission)
- Task Management (view, list, create, update, delete)

### Step 3: Update Existing Projects (Optional)

For existing projects, you may want to manually create admin roles:

```php
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectRole;
use Modules\Project\ProjectManagement\Models\ProjectPermission;

$projects = ProjectManagement::all();

foreach ($projects as $project) {
    // Create admin role
    $adminRole = ProjectRole::create([
        'project_id' => $project->id,
        'name' => 'Project Admin',
        'slug' => 'project-admin',
        'description' => 'Full access to project resources',
        'is_default' => true,
        'is_active' => true,
    ]);

    // Assign all permissions
    $allPermissions = ProjectPermission::where('is_active', true)->pluck('id');
    $adminRole->permissions()->sync($allPermissions);
}
```

---

## Auto-Creation Flow

When a new project is created:

1. **Observer Triggers**: `ProjectManagementObserver` detects project creation
2. **Admin Role Created**: "Project Admin" role is automatically created for the project
3. **All Permissions Assigned**: All active permissions are assigned to the admin role
4. **Manager Added**: If `manager_id` exists, they are added to `project_employees` with admin role
5. **Creator Added**: If `created_by_user_id` exists and differs from manager, they are also added as admin
6. **Transaction Safe**: All operations wrapped in database transaction

---

## API Endpoints

### Project Permissions

#### 1. List All Permissions
```http
GET /api/v1/projects/permissions
```

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "employee.create",
      "submodule": "employee",
      "action": "create",
      "title": {
        "ar": "إنشاء موظف",
        "en": "Create Employee"
      },
      "title_ar": "إنشاء موظف",
      "title_en": "Create Employee",
      "description": "Add new employee to project",
      "is_active": true
    }
  ]
}
```

#### 2. Get Permissions by Submodule
```http
GET /api/v1/projects/permissions/submodule/{submodule}
```

Example: `/api/v1/projects/permissions/submodule/employee`

#### 3. Update Permission Translations
```http
PUT /api/v1/projects/permissions/{id}
Content-Type: application/json

{
  "title_ar": "إنشاء موظف محدث",
  "title_en": "Create Employee Updated",
  "description": "Updated description"
}
```

### Project Roles

#### 1. List Project Roles
```http
GET /api/v1/projects/{project_id}/roles
```

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "project_id": "uuid",
      "name": "Project Admin",
      "slug": "project-admin",
      "description": "Full access to project resources",
      "is_default": true,
      "is_active": true,
      "permissions_count": 25,
      "permissions": [
        {
          "id": "uuid",
          "name": "employee.create",
          "title": {
            "ar": "إنشاء موظف",
            "en": "Create Employee"
          }
        }
      ],
      "created_at": "2026-04-14T10:00:00.000000Z"
    }
  ]
}
```

#### 2. Create Project Role
```http
POST /api/v1/projects/{project_id}/roles
Content-Type: application/json

{
  "name": "Team Member",
  "slug": "team-member",
  "description": "Regular team member with limited permissions",
  "is_active": true,
  "permission_ids": [
    "permission-uuid-1",
    "permission-uuid-2"
  ]
}
```

#### 3. Get Role Details
```http
GET /api/v1/projects/{project_id}/roles/{id}
```

#### 4. Update Project Role
```http
PUT /api/v1/projects/{project_id}/roles/{id}
Content-Type: application/json

{
  "name": "Senior Team Member",
  "description": "Senior team member with extended permissions",
  "is_active": true,
  "permission_ids": [
    "permission-uuid-1",
    "permission-uuid-2",
    "permission-uuid-3"
  ]
}
```

**Note**: This replaces ALL permissions. Use sync-permissions endpoint for the same behavior.

#### 5. Delete Project Role
```http
DELETE /api/v1/projects/{project_id}/roles/{id}
```

**Note**: Cannot delete default roles (`is_default = true`)

#### 6. Assign Permissions to Role (Add)
```http
POST /api/v1/projects/{project_id}/roles/{id}/assign-permissions
Content-Type: application/json

{
  "permission_ids": [
    "permission-uuid-1",
    "permission-uuid-2"
  ]
}
```

**Behavior**: Adds permissions to existing ones (keeps current permissions)

#### 7. Sync Permissions to Role (Replace)
```http
POST /api/v1/projects/{project_id}/roles/{id}/sync-permissions
Content-Type: application/json

{
  "permission_ids": [
    "permission-uuid-1",
    "permission-uuid-2",
    "permission-uuid-3"
  ]
}
```

**Behavior**: Replaces all permissions with provided list

### Assign Employees with Roles

#### Updated Assign Employees Endpoint
```http
POST /api/v1/projects/employees/assign
Content-Type: application/json

{
  "project_id": "project-uuid",
  "user_ids": [
    "user-uuid-1",
    "user-uuid-2"
  ],
  "project_role_id": "role-uuid"
}
```

**Note**: `project_role_id` is optional. If not provided, employees are added without a specific role.

---

## Middleware Usage

### Apply to Routes

```php
use Modules\Project\ProjectManagement\Middleware\CheckProjectPermission;

Route::middleware(['auth:api', 'project.permission:employee.create'])
    ->post('projects/{project_id}/employees', [Controller::class, 'create']);
```

### How It Works

1. Extracts `project_id` from route parameter or request body
2. Gets authenticated user's ID
3. Finds user's `ProjectEmployee` record for this project
4. Loads their `projectRole` with `permissions`
5. Checks if permission exists in their role
6. Returns 403 if:
   - User is not assigned to project
   - User has no role
   - Permission not in their role

---

## Seeder Structure

The seeder uses an easy-to-update array structure:

```php
private function getPermissions(): array
{
    return [
        [
            'name' => 'employee.create',
            'submodule' => 'employee',
            'action' => 'create',
            'title_ar' => 'إنشاء موظف',
            'title_en' => 'Create Employee',
            'description' => 'Add new employee to project',
        ],
        // Add more permissions here...
    ];
}
```

### To Add New Permissions:

1. Open `modules/Project/ProjectManagement/Database/Seeders/ProjectPermissionsSeeder.php`
2. Add new entries to the `getPermissions()` array
3. Run: `php artisan db:seed --class=Modules\\Project\\ProjectManagement\\Database\\Seeders\\ProjectPermissionsSeeder`

---

## Models & Relationships

### ProjectRole Model

```php
// Relationships
$role->project;          // BelongsTo ProjectManagement
$role->permissions;      // BelongsToMany ProjectPermission
$role->projectEmployees; // HasMany ProjectEmployee
```

### ProjectPermission Model

```php
// Relationships
$permission->roles;      // BelongsToMany ProjectRole

// Translations
$permission->title;      // Current locale
$permission->getTranslation('title', 'ar');  // Arabic
$permission->getTranslation('title', 'en');  // English
```

### ProjectEmployee Model

```php
// Relationships
$employee->project;      // BelongsTo ProjectManagement
$employee->user;         // BelongsTo User
$employee->company;      // BelongsTo Company
$employee->assignedBy;   // BelongsTo User
$employee->projectRole;  // BelongsTo ProjectRole (NEW)
```

### ProjectManagement Model

```php
// Relationships
$project->creator;       // BelongsTo User (NEW)
$project->manager;       // BelongsTo User
$project->projectRoles;  // HasMany ProjectRole (NEW)
$project->projectEmployees; // HasMany ProjectEmployee
```

---

## Postman Collection

Import the collection: `ProjectRolesPermissions_API.postman_collection.json`

### Variables to Set:
- `url` - API base URL (e.g., `http://localhost/api/v1`)
- `token` - Your bearer token
- `project_id` - Project UUID
- `role_id` - Role UUID
- `permission_id` - Permission UUID

---

## Code Examples

### Check User Permission in Controller

```php
use Modules\Project\ProjectManagement\Models\ProjectEmployee;

public function someAction(Request $request)
{
    $projectId = $request->project_id;
    $userId = auth()->id();

    $employee = ProjectEmployee::where('project_id', $projectId)
        ->where('user_id', $userId)
        ->with('projectRole.permissions')
        ->first();

    if (!$employee || !$employee->projectRole) {
        return response()->json(['error' => 'Not authorized'], 403);
    }

    $hasPermission = $employee->projectRole->permissions
        ->pluck('name')
        ->contains('employee.create');

    if (!$hasPermission) {
        return response()->json(['error' => 'Missing permission'], 403);
    }

    // Proceed with action...
}
```

### Create Custom Role Programmatically

```php
use Modules\Project\ProjectManagement\Repositories\ProjectRoleRepository;

$roleRepo = app(ProjectRoleRepository::class);

$role = $roleRepo->createRole([
    'project_id' => $projectId,
    'name' => 'Viewer',
    'slug' => 'viewer',
    'description' => 'Can only view project data',
    'is_active' => true,
], [
    $employeeViewPermissionId,
    $taskViewPermissionId,
]);
```

### Assign Role to Employee

```php
use Modules\Project\ProjectManagement\Models\ProjectEmployee;

ProjectEmployee::updateOrCreate(
    [
        'project_id' => $projectId,
        'user_id' => $userId,
    ],
    [
        'company_id' => $companyId,
        'project_role_id' => $roleId,
        'assigned_by_user_id' => auth()->id(),
        'assigned_at' => now(),
    ]
);
```

---

## Translation Updates

### Via API
```http
PUT /api/v1/projects/permissions/{id}
Content-Type: application/json

{
  "title_ar": "النص المحدث بالعربية",
  "title_en": "Updated English Text"
}
```

### Via Code
```php
use Modules\Project\ProjectManagement\Models\ProjectPermission;

$permission = ProjectPermission::find($id);
$permission->update([
    'title' => [
        'ar' => 'النص المحدث بالعربية',
        'en' => 'Updated English Text',
    ],
]);
```

---

## Testing

### Test Permission Seeding
```bash
php artisan db:seed --class=Modules\\Project\\ProjectManagement\\Database\\Seeders\\ProjectPermissionsSeeder
```

### Test Auto Role Creation
```php
use Modules\Project\ProjectManagement\Models\ProjectManagement;

$project = ProjectManagement::create([
    'name' => 'Test Project',
    'manager_id' => $userId,
    'company_id' => tenant('id'),
]);

// Check if admin role was created
$adminRole = $project->projectRoles()
    ->where('slug', 'project-admin')
    ->first();

// Check if manager was assigned
$managerEmployee = $project->projectEmployees()
    ->where('user_id', $userId)
    ->first();

assert($adminRole !== null);
assert($managerEmployee->project_role_id === $adminRole->id);
```

---

## Troubleshooting

### Permission not working?
1. Check if user is assigned to project in `project_employees`
2. Check if user has a `project_role_id` set
3. Check if role has the required permission
4. Check middleware is applied to route

### Role can't be deleted?
- Default roles (`is_default = true`) cannot be deleted
- This prevents accidental deletion of auto-created admin roles

### Permissions not appearing?
- Run the seeder: `php artisan db:seed --class=Modules\\Project\\ProjectManagement\\Database\\Seeders\\ProjectPermissionsSeeder`
- Check `is_active` is `true`

---

## Best Practices

1. **Always assign roles** when adding employees to projects
2. **Use middleware** for route protection instead of manual checks
3. **Keep permissions granular** - use `submodule.action` pattern
4. **Update translations** regularly to keep them accurate
5. **Don't delete default roles** - they're auto-created for a reason
6. **Use sync for full replacement** of permissions, assign for additions
7. **Check permissions in frontend** to hide UI elements users can't access

---

## Future Enhancements

Possible additions to consider:
- Permission groups/categories
- Role templates
- Permission inheritance
- Activity logging for role/permission changes
- Bulk role assignment
- Role cloning/duplication
- Permission dependencies

---

## Support

For issues or questions:
1. Check this documentation
2. Review Postman collection examples
3. Check database for proper seeding
4. Verify middleware registration

---

**Last Updated**: April 15, 2026  
**Version**: 1.0.0
