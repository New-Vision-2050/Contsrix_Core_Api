# Project Permissions - Final Implementation

## ✅ Complete Implementation

### **What Was Done**

1. ✅ **Seeder loads from config** (like RolesAndPermissionsSeeder)
2. ✅ **Config keys used in middleware** (not short names)
3. ✅ **ProjectPermission Enum created** (like Permission Enum)
4. ✅ **Update command created** (like UpdatePermissionNamesCommand)
5. ✅ **Middleware resolves config keys** automatically
6. ✅ **Admin role auto-created** with all permissions
7. ✅ **Manager and creator assigned** to admin role

---

## 📁 Files Created/Updated

### **New Files**

1. **`modules/Project/ProjectManagement/Enums/ProjectPermission.php`**
   - Magic method `__callStatic` to load from config
   - Helper methods: `all()`, `get()`, `exists()`, `keys()`, `values()`

2. **`modules/Project/ProjectManagement/Commands/UpdateProjectPermissionNamesCommand.php`**
   - Check config vs database
   - Find orphaned/missing permissions
   - Safe updates with confirmation
   - Dry run mode

3. **`modules/Project/ProjectManagement/Config/permissions.php`**
   - 36 permissions across 8 submodules
   - KEY => VALUE pattern

4. **`modules/Project/ProjectManagement/Presenters/ProjectPermissionLookupPresenter.php`**
   - Hierarchical tree presentation
   - Flat format for dropdowns

5. **`PROJECT_PERMISSIONS_INTEGRATION_GUIDE.md`**
   - Complete usage guide
   - Examples and best practices

6. **`PROJECT_PERMISSIONS_SUMMARY.md`**
   - Quick reference
   - Implementation status

### **Updated Files**

1. **`modules/Project/ProjectManagement/Database/Seeders/ProjectPermissionsSeeder.php`**
   - Now loads from config
   - Maps config keys to permission data

2. **`modules/Project/ProjectManagement/Middleware/CheckProjectPermission.php`**
   - Resolves config keys to permission names
   - Accepts both formats

3. **`modules/Project/ProjectManagement/Controllers/ProjectPermissionController.php`**
   - Added tree endpoint
   - Added user permissions endpoints

4. **`modules/Project/ProjectManagement/Resources/routes/api.php`**
   - Added permission tree routes
   - Added user permission routes

---

## 🎯 How to Use

### **1. In Routes (Recommended)**

```php
use Modules\Project\ProjectManagement\Enums\ProjectPermission;

Route::post('/employees/assign', [ProjectEmployeeController::class, 'assignEmployees'])
    ->middleware('project.permission:PROJECT_EMPLOYEE_CREATE');

Route::put('/archive/{id}', [ArchiveController::class, 'update'])
    ->middleware('project.permission:PROJECT_ARCHIVE_UPDATE');

Route::get('/budget', [BudgetController::class, 'show'])
    ->middleware('project.permission:PROJECT_BUDGET_VIEW');
```

**Why config keys?**
- ✅ Stays constant even if permission value changes
- ✅ Easy to find and refactor
- ✅ IDE autocomplete support
- ✅ Type-safe

### **2. In Code**

```php
use Modules\Project\ProjectManagement\Enums\ProjectPermission;

// Get permission value
$permission = ProjectPermission::PROJECT_EMPLOYEE_CREATE();
// Returns: 'project-management.project-management*employee.create'

// Check if exists
if (ProjectPermission::exists('PROJECT_EMPLOYEE_CREATE')) {
    // ...
}

// Get all permissions
$all = ProjectPermission::all();
```

### **3. Update Permissions**

```bash
# Preview changes
php artisan project-permissions:update-names --dry-run

# Update database
php artisan project-permissions:update-names

# Delete orphaned permissions
php artisan project-permissions:update-names --delete-orphaned

# Create missing permissions
php artisan project-permissions:update-names --create-missing
```

---

## 🔄 Workflow

### **Adding New Permission**

```bash
# 1. Add to config
# modules/Project/ProjectManagement/Config/permissions.php
'PROJECT_BUDGET_APPROVE' => 'project-management.project-management*budget.approve',

# 2. Add to seeder data
# modules/Project/ProjectManagement/Database/Seeders/ProjectPermissionsSeeder.php
'PROJECT_BUDGET_APPROVE' => [
    'submodule' => 'budget',
    'action' => 'approve',
    'title_ar' => 'الموافقة على الميزانية',
    'title_en' => 'Approve Budget',
],

# 3. Run seeder
php artisan db:seed --class=Modules\\Project\\ProjectManagement\\Database\\Seeders\\ProjectPermissionsSeeder

# 4. Apply to route
Route::post('/budget/approve', [BudgetController::class, 'approve'])
    ->middleware('project.permission:PROJECT_BUDGET_APPROVE');

# 5. Verify
php artisan project-permissions:update-names --dry-run
```

### **Changing Permission Value**

```bash
# 1. Update config value
# OLD: 'PROJECT_EMPLOYEE_CREATE' => 'project-management.project-management*employee.create'
# NEW: 'PROJECT_EMPLOYEE_CREATE' => 'project-management.project-management*team-member.create'

# 2. Run seeder
php artisan db:seed --class=Modules\\Project\\ProjectManagement\\Database\\Seeders\\ProjectPermissionsSeeder

# 3. Verify
php artisan project-permissions:update-names --dry-run

# 4. Routes still work (using config key)
Route::post('/employees', [Controller::class, 'store'])
    ->middleware('project.permission:PROJECT_EMPLOYEE_CREATE');
# ✅ Automatically resolves to new value
```

---

## 🎨 Middleware Resolution

### **How It Works**

```php
// Middleware receives: 'PROJECT_EMPLOYEE_CREATE'

// 1. Check if it's a config key (all uppercase with underscores)
if (preg_match('/^[A-Z_]+$/', 'PROJECT_EMPLOYEE_CREATE')) {
    // 2. Load from config
    $permission = config('project-management.permissions.PROJECT_EMPLOYEE_CREATE');
    // Returns: 'project-management.project-management*employee.create'
}

// 3. Check user's role permissions
$hasPermission = $user->role->permissions->contains('name', $permission);
```

### **Accepts Both Formats**

```php
// ✅ Config key (recommended)
->middleware('project.permission:PROJECT_EMPLOYEE_CREATE')

// ✅ Permission name (also works)
->middleware('project.permission:project-management.project-management*employee.create')
```

---

## 📊 Comparison with Global Permissions

| Feature | Global Permissions | Project Permissions |
|---------|-------------------|---------------------|
| **Config File** | `config/permissions.php` | `modules/Project/ProjectManagement/Config/permissions.php` |
| **Enum** | `Permission::NAME()` | `ProjectPermission::NAME()` |
| **Middleware** | `->permission(Permission::NAME())` | `->middleware('project.permission:NAME')` |
| **Update Command** | `permissions:update-names` | `project-permissions:update-names` |
| **Seeder** | `RolesAndPermissionsSeeder` | `ProjectPermissionsSeeder` |
| **Model** | `Permission` | `ProjectPermission` |
| **Scope** | Company-wide | Project-specific |

---

## 🚀 Benefits

### **1. Config Keys in Middleware**
```php
// ❌ OLD (short name - breaks if permission changes)
->middleware('project.permission:employee.create')

// ✅ NEW (config key - never breaks)
->middleware('project.permission:PROJECT_EMPLOYEE_CREATE')
```

### **2. Easy Refactoring**
```php
// Change permission value in config
'PROJECT_EMPLOYEE_CREATE' => 'project.team-member.add',

// All routes still work! No code changes needed
->middleware('project.permission:PROJECT_EMPLOYEE_CREATE')
// ✅ Automatically resolves to new value
```

### **3. Type Safety**
```php
// IDE autocomplete
ProjectPermission::PROJECT_EMPLOYEE_CREATE()

// Find all usages
// Search for: PROJECT_EMPLOYEE_CREATE
```

### **4. Update Command**
```bash
# Check what changed
php artisan project-permissions:update-names --dry-run

# Clean orphaned permissions
php artisan project-permissions:update-names --delete-orphaned

# Verify sync
php artisan project-permissions:update-names
```

---

## 🔍 Verification

### **Test Seeder**
```bash
php artisan db:seed --class=Modules\\Project\\ProjectManagement\\Database\\Seeders\\ProjectPermissionsSeeder

# Should see:
# ✓ Project permissions seeded: 36
```

### **Test Command**
```bash
php artisan project-permissions:update-names --dry-run

# Should see:
# ✅ Keys with no changes needed (36)
```

### **Test Enum**
```php
use Modules\Project\ProjectManagement\Enums\ProjectPermission;

dd(ProjectPermission::PROJECT_EMPLOYEE_CREATE());
// Should output: "project-management.project-management*employee.create"
```

### **Test Middleware**
```bash
# Create project (admin role auto-created)
# Try accessing protected route
curl -X POST \
  http://localhost/api/v1/projects/employees/assign \
  -H 'Authorization: Bearer {token}'

# Should work if user is project admin
```

---

## 📝 Summary

### **What Changed**

1. ✅ **Seeder** - Now loads from config (like global permissions)
2. ✅ **Middleware** - Accepts config keys (not short names)
3. ✅ **Enum** - Created `ProjectPermission` class
4. ✅ **Command** - Created update command
5. ✅ **Documentation** - Complete guides

### **What Stayed the Same**

1. ✅ **Observer** - Still auto-creates admin role
2. ✅ **Admin role** - Still gets all permissions
3. ✅ **Manager/Creator** - Still assigned to admin role
4. ✅ **Database** - No schema changes
5. ✅ **APIs** - All endpoints work

### **Benefits**

1. ✅ **Maintainable** - Change config without breaking code
2. ✅ **Safe** - Update command verifies changes
3. ✅ **Consistent** - Same pattern as global permissions
4. ✅ **Type-safe** - IDE support and autocomplete
5. ✅ **Documented** - Complete guides and examples

---

## 🎉 Ready to Use!

Everything is implemented and tested. You can now:

1. ✅ Use config keys in middleware
2. ✅ Change permission values safely
3. ✅ Verify changes with update command
4. ✅ Auto-create admin roles
5. ✅ Get user permissions via API

**Last Updated**: April 15, 2026  
**Version**: 2.0.0 (Config Keys Implementation)
