# Project Permissions - Quick Start Guide

## 🚀 TL;DR

### **Use Config Keys in Middleware**

```php
// ✅ CORRECT - Use config key
Route::post('/employees/assign', [Controller::class, 'assignEmployees'])
    ->middleware('project.permission:PROJECT_EMPLOYEE_CREATE');

// ❌ WRONG - Don't use short name
Route::post('/employees/assign', [Controller::class, 'assignEmployees'])
    ->middleware('project.permission:employee.create');
```

---

## 📋 Quick Reference

### **Available Permissions**

```php
// Employee Management
PROJECT_EMPLOYEE_VIEW
PROJECT_EMPLOYEE_LIST
PROJECT_EMPLOYEE_CREATE
PROJECT_EMPLOYEE_UPDATE
PROJECT_EMPLOYEE_DELETE
PROJECT_EMPLOYEE_ASSIGN

// Archive Library
PROJECT_ARCHIVE_VIEW
PROJECT_ARCHIVE_LIST
PROJECT_ARCHIVE_CREATE
PROJECT_ARCHIVE_UPDATE
PROJECT_ARCHIVE_DELETE
PROJECT_ARCHIVE_DOWNLOAD
PROJECT_ARCHIVE_UPLOAD

// Project Settings
PROJECT_SETTINGS_VIEW
PROJECT_SETTINGS_UPDATE
PROJECT_SETTINGS_DELETE

// Role Management
PROJECT_ROLE_VIEW
PROJECT_ROLE_LIST
PROJECT_ROLE_CREATE
PROJECT_ROLE_UPDATE
PROJECT_ROLE_DELETE
PROJECT_ROLE_ASSIGN_PERMISSION

// Task Management
PROJECT_TASK_VIEW
PROJECT_TASK_LIST
PROJECT_TASK_CREATE
PROJECT_TASK_UPDATE
PROJECT_TASK_DELETE
PROJECT_TASK_ASSIGN
PROJECT_TASK_COMPLETE

// Budget
PROJECT_BUDGET_VIEW
PROJECT_BUDGET_UPDATE

// Expense
PROJECT_EXPENSE_CREATE
PROJECT_EXPENSE_APPROVE

// Reports
PROJECT_REPORT_VIEW
PROJECT_REPORT_EXPORT
PROJECT_REPORT_GENERATE
```

---

## 🎯 Common Tasks

### **1. Apply Permission to Route**

```php
Route::post('/employees', [ProjectEmployeeController::class, 'store'])
    ->middleware('project.permission:PROJECT_EMPLOYEE_CREATE');

Route::get('/archive', [ArchiveController::class, 'index'])
    ->middleware('project.permission:PROJECT_ARCHIVE_LIST');

Route::put('/budget', [BudgetController::class, 'update'])
    ->middleware('project.permission:PROJECT_BUDGET_UPDATE');
```

### **2. Get Permission Value in Code**

```php
use Modules\Project\ProjectManagement\Enums\ProjectPermission;

$permission = ProjectPermission::PROJECT_EMPLOYEE_CREATE();
// Returns: 'project-management.project-management*employee.create'
```

### **3. Check User's Permissions**

```bash
GET /api/v1/projects/{project_id}/my-permissions
Authorization: Bearer {token}
```

### **4. Run Seeder**

```bash
php artisan db:seed --class=Modules\\Project\\ProjectManagement\\Database\\Seeders\\ProjectPermissionsSeeder
```

### **5. Verify Permissions**

```bash
php artisan project-permissions:update-names --dry-run
```

---

## 🔧 Adding New Permission

```bash
# 1. Add to config
# File: modules/Project/ProjectManagement/Config/permissions.php
'PROJECT_NEW_FEATURE_CREATE' => 'project-management.project-management*new-feature.create',

# 2. Add to seeder
# File: modules/Project/ProjectManagement/Database/Seeders/ProjectPermissionsSeeder.php
'PROJECT_NEW_FEATURE_CREATE' => [
    'submodule' => 'newFeature',
    'action' => 'create',
    'title_ar' => 'إنشاء ميزة جديدة',
    'title_en' => 'Create New Feature',
],

# 3. Run seeder
php artisan db:seed --class=Modules\\Project\\ProjectManagement\\Database\\Seeders\\ProjectPermissionsSeeder

# 4. Use in route
Route::post('/new-feature', [Controller::class, 'store'])
    ->middleware('project.permission:PROJECT_NEW_FEATURE_CREATE');
```

---

## 🎨 Route Examples

### **CRUD Routes**

```php
Route::prefix('employees')->group(function () {
    Route::get('/', [Controller::class, 'index'])
        ->middleware('project.permission:PROJECT_EMPLOYEE_LIST');
    
    Route::post('/', [Controller::class, 'store'])
        ->middleware('project.permission:PROJECT_EMPLOYEE_CREATE');
    
    Route::get('/{id}', [Controller::class, 'show'])
        ->middleware('project.permission:PROJECT_EMPLOYEE_VIEW');
    
    Route::put('/{id}', [Controller::class, 'update'])
        ->middleware('project.permission:PROJECT_EMPLOYEE_UPDATE');
    
    Route::delete('/{id}', [Controller::class, 'delete'])
        ->middleware('project.permission:PROJECT_EMPLOYEE_DELETE');
});
```

---

## ⚡ Commands

```bash
# Preview changes
php artisan project-permissions:update-names --dry-run

# Update all
php artisan project-permissions:update-names

# Delete orphaned
php artisan project-permissions:update-names --delete-orphaned

# Create missing
php artisan project-permissions:update-names --create-missing

# Force update
php artisan project-permissions:update-names --force
```

---

## 🔍 Troubleshooting

### **Permission not working?**

```bash
# 1. Check if permission exists
php artisan project-permissions:update-names --dry-run

# 2. Check user has role in project
GET /api/v1/projects/{project_id}/my-permissions

# 3. Check middleware syntax
->middleware('project.permission:PROJECT_EMPLOYEE_CREATE')
# NOT: ->middleware('project.permission:employee.create')
```

### **Admin role not created?**

```php
// Check observer is registered
// File: modules/Project/ProjectManagement/Providers/ProjectManagementServiceProvider.php
ProjectManagement::observe(ProjectManagementObserver::class);
```

---

## 📚 Full Documentation

- **Complete Guide**: `PROJECT_PERMISSIONS_INTEGRATION_GUIDE.md`
- **Implementation Details**: `PROJECT_PERMISSIONS_FINAL_IMPLEMENTATION.md`
- **Summary**: `PROJECT_PERMISSIONS_SUMMARY.md`

---

## ✅ Checklist

- [x] Seeder loads from config
- [x] Config keys used in middleware
- [x] ProjectPermission Enum created
- [x] Update command available
- [x] Admin role auto-created
- [x] Manager/Creator assigned to admin
- [x] Documentation complete

---

**Version**: 2.0.0  
**Last Updated**: April 15, 2026
