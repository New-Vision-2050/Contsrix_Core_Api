# Project Permissions Integration Guide

## Overview

This guide explains how to integrate project-specific permissions with routes, use the config file system, and present permissions in a hierarchical tree structure similar to the global permissions system.

---

## Architecture

### **Two Permission Systems**

1. **Global Permissions** (RoleAndPermission module)
   - Company-wide permissions
   - Applied via `->permission(Permission::NAME())`
   - Stored in `permissions` table
   - Uses Permission Enum

2. **Project Permissions** (ProjectManagement module)
   - Project-specific permissions
   - Applied via middleware `project.permission:permission.name`
   - Stored in `project_permissions` table
   - Uses config file

---

## Config File Structure

### Location
`modules/Project/ProjectManagement/Config/permissions.php`

### Format
```php
return [
    'permissions' => [
        'PERMISSION_NAME' => 'permission.value',
    ]
];
```

### Key Principles

✅ **NAME stays constant** - Never changes (e.g., `PROJECT_EMPLOYEE_CREATE`)  
✅ **VALUE can change** - This is what's stored in database (e.g., `project-management.project-management*employee.create`)  
✅ **Use VALUE for**:
- Database storage
- Tree presentation  
- Permission checking

### Example
```php
return [
    'permissions' => [
        // NAME                     => VALUE
        'PROJECT_EMPLOYEE_CREATE' => 'project-management.project-management*employee.create',
        'PROJECT_EMPLOYEE_UPDATE' => 'project-management.project-management*employee.update',
        'PROJECT_ARCHIVE_CREATE'  => 'project-management.project-management*archive-library.create',
    ]
];
```

---

## How to Apply Permissions on Routes

### Method 1: Using Middleware with Config Keys (Recommended)

```php
use Modules\Project\ProjectManagement\Enums\ProjectPermission;

Route::post('/employees/assign', [ProjectEmployeeController::class, 'assignEmployees'])
    ->middleware('project.permission:PROJECT_EMPLOYEE_CREATE');

Route::put('/archive/{id}', [ArchiveController::class, 'update'])
    ->middleware('project.permission:PROJECT_ARCHIVE_UPDATE');
```

**Benefits**:
- ✅ Config key stays constant even if permission value changes
- ✅ Easy to find and refactor
- ✅ Type-safe with IDE autocomplete
- ✅ Middleware automatically resolves to permission name

### Method 2: Using Permission Name Directly

```php
Route::post('/employees/assign', [ProjectEmployeeController::class, 'assignEmployees'])
    ->middleware('project.permission:project-management.project-management*employee.create');
```

**Note**: This works but is harder to maintain if permission names change.

### Method 2: Using Permission Enum (For Global Permissions)

```php
use Modules\RoleAndPermission\Enums\Permission;

Route::post('/', [ProjectManagementController::class, 'store'])
    ->permission(Permission::PROJECT_MANAGEMENT_CREATE());
```

---

## Permission Tree Structure

### API Endpoints

#### 1. Get All Permissions (Tree Format)
```http
GET /api/v1/projects/permissions/tree
```

**Response**:
```json
{
  "data": [
    {
      "name": "الموظفين",
      "key": "employee",
      "permissions": [
        {
          "id": "uuid",
          "key": "employee.view",
          "submodule": "employee",
          "action": "view",
          "type": "view",
          "name": "عرض الموظف",
          "title_ar": "عرض الموظف",
          "title_en": "View Employee"
        },
        {
          "id": "uuid",
          "key": "employee.create",
          "submodule": "employee",
          "action": "create",
          "type": "create",
          "name": "إنشاء موظف",
          "title_ar": "إنشاء موظف",
          "title_en": "Create Employee"
        }
      ],
      "count": 5
    },
    {
      "name": "المكتبة الأرشيفية",
      "key": "archiveLibrary",
      "permissions": [...],
      "count": 5
    }
  ]
}
```

#### 2. Get User's Project Permissions (Tree Format)
```http
GET /api/v1/projects/{project_id}/my-permissions
```

**Response**:
```json
{
  "data": {
    "project_id": "uuid",
    "user_id": "uuid",
    "role": {
      "id": "uuid",
      "name": "Project Admin",
      "slug": "project-admin"
    },
    "permissions": [
      {
        "name": "الموظفين",
        "key": "employee",
        "permissions": [...],
        "count": 5
      }
    ],
    "permissions_count": 25
  }
}
```

#### 3. Get User's Project Permissions (Flat Format)
```http
GET /api/v1/projects/{project_id}/my-permissions/flat
```

**Response**:
```json
{
  "data": {
    "project_id": "uuid",
    "user_id": "uuid",
    "role": {...},
    "permissions": [
      {
        "id": "uuid",
        "name": "employee.view",
        "title": "عرض الموظف",
        "title_ar": "عرض الموظف",
        "title_en": "View Employee",
        "submodule": "employee",
        "action": "view"
      }
    ],
    "permissions_count": 25
  }
}
```

---

## How Middleware Works

### Flow

1. **Extract permission name** from route middleware parameter
2. **Get project_id** from route or request
3. **Get user's ProjectEmployee** record
4. **Load user's role** with permissions
5. **Check if permission exists** in user's role
6. **Allow or deny** access

### CheckProjectPermission Middleware

```php
// Usage
Route::post('/action', [Controller::class, 'method'])
    ->middleware('project.permission:employee.create');

// Middleware checks:
// 1. Is user assigned to project?
// 2. Does user have a role?
// 3. Does role have 'employee.create' permission?
```

### Middleware Registration

Already registered in `ProjectManagementServiceProvider`:

```php
$this->app['router']->aliasMiddleware('project.permission', CheckProjectPermission::class);
```

---

## Updating Permissions

### Method 1: Update Config and Re-seed

1. **Edit config file**:
```php
// modules/Project/ProjectManagement/Config/permissions.php
'NEW_PERMISSION' => 'project-management.project-management*new-feature.create',
```

2. **Update seeder** (`ProjectPermissionsSeeder.php`):
```php
$configPermissions = config('project-management.permissions', []);

[
    'name' => $configPermissions['NEW_PERMISSION'] ?? 'new-feature.create',
    'submodule' => 'newFeature',
    'action' => 'create',
    'title_ar' => 'إنشاء ميزة جديدة',
    'title_en' => 'Create New Feature',
],
```

3. **Run seeder**:
```bash
php artisan db:seed --class=Modules\\Project\\ProjectManagement\\Database\\Seeders\\ProjectPermissionsSeeder
```

### Method 2: Update via API

```http
PUT /api/v1/projects/permissions/{id}
Content-Type: application/json

{
  "title_ar": "عنوان محدث",
  "title_en": "Updated Title",
  "description": "Updated description"
}
```

---

## Example Route Implementation

### Employee Management Routes

```php
Route::prefix('employees')->group(function () {
    Route::get('/', [ProjectEmployeeController::class, 'index'])
        ->middleware('project.permission:PROJECT_EMPLOYEE_LIST');
    
    Route::post('/assign', [ProjectEmployeeController::class, 'assignEmployees'])
        ->middleware('project.permission:PROJECT_EMPLOYEE_CREATE');
    
    Route::get('/{id}', [ProjectEmployeeController::class, 'show'])
        ->middleware('project.permission:PROJECT_EMPLOYEE_VIEW');
    
    Route::put('/{id}', [ProjectEmployeeController::class, 'update'])
        ->middleware('project.permission:PROJECT_EMPLOYEE_UPDATE');
    
    Route::delete('/{id}', [ProjectEmployeeController::class, 'delete'])
        ->middleware('project.permission:PROJECT_EMPLOYEE_DELETE');
});
```

### Archive Library Routes

```php
Route::prefix('archive')->group(function () {
    Route::get('/', [ArchiveController::class, 'index'])
        ->middleware('project.permission:PROJECT_ARCHIVE_LIST');
    
    Route::post('/', [ArchiveController::class, 'store'])
        ->middleware('project.permission:PROJECT_ARCHIVE_CREATE');
    
    Route::get('/{id}', [ArchiveController::class, 'show'])
        ->middleware('project.permission:PROJECT_ARCHIVE_VIEW');
    
    Route::put('/{id}', [ArchiveController::class, 'update'])
        ->middleware('project.permission:PROJECT_ARCHIVE_UPDATE');
    
    Route::delete('/{id}', [ArchiveController::class, 'delete'])
        ->middleware('project.permission:PROJECT_ARCHIVE_DELETE');
});
```

### Role Management Routes

```php
Route::prefix('{project_id}/roles')->group(function () {
    Route::get('/', [ProjectRoleController::class, 'index'])
        ->middleware('project.permission:PROJECT_ROLE_LIST');
    
    Route::post('/', [ProjectRoleController::class, 'store'])
        ->middleware('project.permission:PROJECT_ROLE_CREATE');
    
    Route::get('/{id}', [ProjectRoleController::class, 'show'])
        ->middleware('project.permission:PROJECT_ROLE_VIEW');
    
    Route::put('/{id}', [ProjectRoleController::class, 'update'])
        ->middleware('project.permission:PROJECT_ROLE_UPDATE');
    
    Route::delete('/{id}', [ProjectRoleController::class, 'delete'])
        ->middleware('project.permission:PROJECT_ROLE_DELETE');
    
    Route::post('/{id}/assign-permissions', [ProjectRoleController::class, 'assignPermissions'])
        ->middleware('project.permission:PROJECT_ROLE_ASSIGN_PERMISSION');
});
```

---

## Adding New Permissions

### Step-by-Step Guide

#### 1. Add to Config File

```php
// modules/Project/ProjectManagement/Config/permissions.php
'PROJECT_BUDGET_VIEW' => 'project-management.project-management*budget.view',
'PROJECT_BUDGET_UPDATE' => 'project-management.project-management*budget.update',
```

#### 2. Add to Seeder

```php
// modules/Project/ProjectManagement/Database/Seeders/ProjectPermissionsSeeder.php
$configPermissions = config('project-management.permissions', []);

[
    'name' => $configPermissions['PROJECT_BUDGET_VIEW'] ?? 'budget.view',
    'submodule' => 'budget',
    'action' => 'view',
    'title_ar' => 'عرض الميزانية',
    'title_en' => 'View Budget',
    'description' => 'View project budget information',
],
[
    'name' => $configPermissions['PROJECT_BUDGET_UPDATE'] ?? 'budget.update',
    'submodule' => 'budget',
    'action' => 'update',
    'title_ar' => 'تحديث الميزانية',
    'title_en' => 'Update Budget',
    'description' => 'Update project budget',
],
```

#### 3. Run Seeder

```bash
php artisan db:seed --class=Modules\\Project\\ProjectManagement\\Database\\Seeders\\ProjectPermissionsSeeder
```

#### 4. Apply to Routes

```php
Route::get('/budget', [BudgetController::class, 'show'])
    ->middleware('project.permission:PROJECT_BUDGET_VIEW');

Route::put('/budget', [BudgetController::class, 'update'])
    ->middleware('project.permission:PROJECT_BUDGET_UPDATE');
```

#### 5. Add Translation (Optional)

Update `ProjectPermissionLookupPresenter`:

```php
private function getSubmoduleName(string $submodule): string
{
    $translations = [
        // ... existing
        'budget' => [
            'ar' => 'الميزانية',
            'en' => 'Budget',
        ],
    ];
    // ...
}
```

---

## Frontend Integration

### Get User Permissions on Load

```javascript
// When user opens a project
const projectId = 'project-uuid';

fetch(`/api/v1/projects/${projectId}/my-permissions`)
  .then(res => res.json())
  .then(data => {
    const permissions = data.data.permissions;
    
    // Store in state management (Vuex, Redux, etc.)
    store.commit('setProjectPermissions', permissions);
  });
```

### Check Permission in UI

```javascript
// Hide/show buttons based on permissions
const hasPermission = (submodule, action) => {
  const permissions = store.state.projectPermissions;
  
  const module = permissions.find(p => p.key === submodule);
  if (!module) return false;
  
  return module.permissions.some(p => p.action === action);
};

// Usage
if (hasPermission('employee', 'create')) {
  // Show "Add Employee" button
}

if (hasPermission('archiveLibrary', 'delete')) {
  // Show "Delete" button
}
```

### Tree Display

```vue
<template>
  <div v-for="module in permissions" :key="module.key">
    <h3>{{ module.name }}</h3>
    <div v-for="perm in module.permissions" :key="perm.id">
      <input 
        type="checkbox" 
        :value="perm.id" 
        v-model="selectedPermissions"
      />
      <label>{{ perm.name }}</label>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      permissions: [],
      selectedPermissions: []
    };
  },
  mounted() {
    this.loadPermissions();
  },
  methods: {
    async loadPermissions() {
      const res = await fetch('/api/v1/projects/permissions/tree');
      const data = await res.json();
      this.permissions = data.data;
    }
  }
};
</script>
```

---

## Testing

### Test Permission Check

```bash
# Get your permissions for a project
curl -X GET \
  http://localhost/api/v1/projects/{project_id}/my-permissions \
  -H 'Authorization: Bearer {token}' \
  -H 'Accept: application/json'
```

### Test Route Protection

```bash
# Try to access protected route
curl -X POST \
  http://localhost/api/v1/projects/employees/assign \
  -H 'Authorization: Bearer {token}' \
  -H 'Accept: application/json' \
  -d '{
    "project_id": "{project_id}",
    "user_ids": ["{user_id}"]
  }'

# Expected responses:
# 200 - Success (user has permission)
# 403 - Forbidden (user lacks permission)
# 401 - Unauthorized (not logged in)
```

---

## Comparison: Global vs Project Permissions

| Aspect | Global Permissions | Project Permissions |
|--------|-------------------|---------------------|
| **Scope** | Company-wide | Project-specific |
| **Storage** | `permissions` table | `project_permissions` table |
| **Assignment** | Via roles to users | Via project roles to project employees |
| **Route Application** | `->permission(Permission::NAME())` | `->middleware('project.permission:name')` |
| **Config** | `RoleAndPermission/Config/permissions.php` | `ProjectManagement/Config/permissions.php` |
| **Presenter** | `PermissionLookupPresenter` | `ProjectPermissionLookupPresenter` |
| **Middleware** | Built-in permission middleware | `CheckProjectPermission` |
| **Tree API** | `/permissions/hierarchy/` | `/projects/permissions/tree` |
| **User Perms API** | Via user roles | `/projects/{id}/my-permissions` |

---

## Best Practices

1. **Use config KEYS in middleware**: `PROJECT_EMPLOYEE_CREATE`, not short names or full values
2. **Keep config NAME constant**: Only change VALUE if needed
3. **Run seeder after config changes**: Ensures database is in sync
4. **Use update command**: Run `project-permissions:update-names` to verify changes
5. **Cache permissions in frontend**: Reduce API calls
6. **Check permissions on both ends**: Backend (security) + Frontend (UX)
7. **Update translations**: Keep Arabic and English in sync
8. **Document new permissions**: Add to this guide when adding features

---

## Update Permission Names Command

### Overview

When you change permission values in the config file, use this command to check and update the database.

### Command Usage

```bash
# Preview changes (dry run)
php artisan project-permissions:update-names --dry-run

# Update all permissions
php artisan project-permissions:update-names

# Update specific keys only
php artisan project-permissions:update-names --key=PROJECT_EMPLOYEE_CREATE --key=PROJECT_ARCHIVE_VIEW

# Force update without confirmation
php artisan project-permissions:update-names --force

# Delete orphaned permissions (exist in DB but not in config)
php artisan project-permissions:update-names --delete-orphaned

# Create missing permissions
php artisan project-permissions:update-names --create-missing
```

### What It Does

1. ✅ **Checks config vs database** - Compares config file with database
2. ✅ **Shows summary** - Displays what will change
3. ✅ **Finds orphaned permissions** - Permissions in DB but not in config
4. ✅ **Finds missing permissions** - Permissions in config but not in DB
5. ✅ **Safe updates** - Asks for confirmation before changes
6. ✅ **Dry run mode** - Preview without making changes

### Example Output

```
Loading project permissions from config...
Found 36 permissions in config.

✅ Keys with no changes needed (36):
┌──────────────────────────────┬─────────────┐
│ Key                          │ Status      │
├──────────────────────────────┼─────────────┤
│ PROJECT_EMPLOYEE_VIEW        │ Up to date  │
│ PROJECT_EMPLOYEE_CREATE      │ Up to date  │
│ PROJECT_ARCHIVE_VIEW         │ Up to date  │
└──────────────────────────────┴─────────────┘

No updates needed.
```

### When to Use

- ✅ After changing permission values in config
- ✅ After adding new permissions to config
- ✅ To clean up orphaned permissions
- ✅ To verify config and database are in sync

### Benefits

- ✅ **Safe** - Preview before applying
- ✅ **Fast** - Bulk updates
- ✅ **Smart** - Detects config keys vs permission names
- ✅ **Clean** - Removes orphaned permissions

---

## Troubleshooting

### Permission not working?
1. Check if permission exists in database
2. Check if user has a role in the project
3. Check if role has the permission
4. Check middleware is applied correctly
5. Check permission name matches (case-sensitive)

### Tree not showing permissions?
1. Run seeder to populate permissions
2. Check ProjectPermissionLookupPresenter translations
3. Verify API endpoint is correct

### User can't access route?
1. Verify user is assigned to project
2. Verify user has a role
3. Verify role has required permission
4. Check middleware parameter is correct

---

## Summary

✅ Config file stores permission definitions  
✅ Seeder syncs config to database  
✅ Middleware checks permissions on routes  
✅ Tree API presents permissions hierarchically  
✅ User API shows what they can do in a project  
✅ Frontend uses permissions to show/hide features  

**Last Updated**: April 15, 2026  
**Version**: 1.0.0
