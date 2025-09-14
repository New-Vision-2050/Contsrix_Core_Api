# Route Permission Application Guide

## Quick Reference for Applying Permissions to Routes

### **Essential Template - Copy & Use Every Time**

```php
// ALWAYS IMPORT THIS
use Modules\RoleAndPermission\Enums\Permission;

// Standard Route Group with Middleware
Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    
    // LIST (Index) - GET /
    Route::get('/', [Controller::class, 'index'])
        ->permission(Permission::ENTITY_LIST());
    
    // CREATE - POST /
    Route::post('/', [Controller::class, 'store'])
        ->permission(Permission::ENTITY_CREATE());
    
    // EXPORT - POST /export
    Route::post('/export', [Controller::class, 'export'])
        ->permission(Permission::ENTITY_EXPORT());
    
    // VIEW (Show) - GET /{id}
    Route::get('/{id}', [Controller::class, 'show'])
        ->permission(Permission::ENTITY_VIEW());
    
    // UPDATE - PUT /{id}
    Route::put('/{id}', [Controller::class, 'update'])
        ->permission(Permission::ENTITY_UPDATE());
    
    // DELETE - DELETE /{id}
    Route::delete('/{id}', [Controller::class, 'delete'])
        ->permission(Permission::ENTITY_DELETE());
});
```

---

## **Permission Naming Convention**

| Route Action | Permission Format | Example |
|-------------|------------------|---------|
| List/Index | `{MODULE}_{ENTITY}_LIST` | `LEAVE_TYPE_LIST` |
| View/Show | `{MODULE}_{ENTITY}_VIEW` | `LEAVE_TYPE_VIEW` |
| Create | `{MODULE}_{ENTITY}_CREATE` | `LEAVE_TYPE_CREATE` |
| Update | `{MODULE}_{ENTITY}_UPDATE` | `LEAVE_TYPE_UPDATE` |
| Delete | `{MODULE}_{ENTITY}_DELETE` | `LEAVE_TYPE_DELETE` |
| Export | `{MODULE}_{ENTITY}_EXPORT` | `LEAVE_TYPE_EXPORT` |
| Activate | `{MODULE}_{ENTITY}_ACTIVATE` | `LEAVE_TYPE_ACTIVATE` |

---

## **Multiple Permissions Syntax**

```php
// Single Permission
->permission(Permission::ENTITY_VIEW())

// Multiple Permissions (comma-separated)
->permission(Permission::ENTITY_VIEW(), Permission::ENTITY_UPDATE())

// Real Example
Route::get('/{id}', [PublicHolidayController::class, 'show'])
    ->permission(Permission::PUBLIC_HOLIDAY_VIEW(), Permission::PUBLIC_HOLIDAY_UPDATE());
```

---

## **Special Route Types**

```php
// Status/Toggle Routes (use UPDATE permission)
Route::put('/{id}/status', [Controller::class, 'updateStatus'])
    ->permission(Permission::ENTITY_UPDATE());

Route::put('/{id}/rollover-allowed', [Controller::class, 'updateRolloverAllowed'])
    ->permission(Permission::LEAVE_POLICY_UPDATE());

// Activation Routes (use ACTIVATE permission)
Route::put('/{id}/activate', [Controller::class, 'activate'])
    ->permission(Permission::ENTITY_ACTIVATE());

// Bulk Operations
Route::post('/bulk-delete', [Controller::class, 'bulkDelete'])
    ->permission(Permission::ENTITY_DELETE());
```

---

## **Leave Module Examples (Copy These Patterns)**

### **LeaveType Routes**
```php
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [LeaveTypeController::class, 'index'])
        ->permission(Permission::LEAVE_TYPE_LIST());
    Route::post('/', [LeaveTypeController::class, 'store'])
        ->permission(Permission::LEAVE_TYPE_CREATE());
    Route::post('/export', [LeaveTypeController::class, 'export'])
        ->permission(Permission::LEAVE_TYPE_EXPORT());
    Route::get('/{id}', [LeaveTypeController::class, 'show'])
        ->permission(Permission::LEAVE_TYPE_VIEW());
    Route::put('/{id}', [LeaveTypeController::class, 'update'])
        ->permission(Permission::LEAVE_TYPE_UPDATE());
    Route::delete('/{id}', [LeaveTypeController::class, 'delete'])
        ->permission(Permission::LEAVE_TYPE_DELETE());
});
```

### **LeavePolicy Routes**
```php
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [LeavePolicyController::class, 'index'])
        ->permission(Permission::LEAVE_POLICY_LIST());
    Route::post('/', [LeavePolicyController::class, 'store'])
        ->permission(Permission::LEAVE_POLICY_CREATE());
    Route::post('/export', [LeavePolicyController::class, 'export'])
        ->permission(Permission::LEAVE_POLICY_EXPORT());
    Route::get('/{id}', [LeavePolicyController::class, 'show'])
        ->permission(Permission::LEAVE_POLICY_VIEW());
    Route::put('/{id}', [LeavePolicyController::class, 'update'])
        ->permission(Permission::LEAVE_POLICY_UPDATE());
    Route::put('/{id}/rollover-allowed', [LeavePolicyController::class, 'updateRolloverAllowed'])
        ->permission(Permission::LEAVE_POLICY_UPDATE());
    Route::put('/{id}/half-day-allowed', [LeavePolicyController::class, 'updateHalfDayAllowed'])
        ->permission(Permission::LEAVE_POLICY_UPDATE());
    Route::delete('/{id}', [LeavePolicyController::class, 'delete'])
        ->permission(Permission::LEAVE_POLICY_DELETE());
});
```

### **PublicHoliday Routes**
```php
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [PublicHolidayController::class, 'index'])
        ->permission(Permission::PUBLIC_HOLIDAY_LIST());
    Route::post('/', [PublicHolidayController::class, 'store'])
        ->permission(Permission::PUBLIC_HOLIDAY_CREATE());
    Route::post('/export', [PublicHolidayController::class, 'export'])
        ->permission(Permission::PUBLIC_HOLIDAY_EXPORT());
    Route::get('/{id}', [PublicHolidayController::class, 'show'])
        ->permission(Permission::PUBLIC_HOLIDAY_VIEW(), Permission::PUBLIC_HOLIDAY_UPDATE());
    Route::put('/{id}', [PublicHolidayController::class, 'update'])
        ->permission(Permission::PUBLIC_HOLIDAY_UPDATE());
    Route::delete('/{id}', [PublicHolidayController::class, 'delete'])
        ->permission(Permission::PUBLIC_HOLIDAY_DELETE());
});
```

---

## **Permission Configuration Template**

**File**: `modules/{Module}/Config/permissions.php`

```php
<?php

return [
    'permissions' => [
        // ================================================================================================
        // {MODULE_NAME} MODULE PERMISSIONS
        // {Description of module purpose}
        // ================================================================================================

        // {Entity} Management
        '{ENTITY}_LIST' => '{module}.{sub-entity}.list',
        '{ENTITY}_VIEW' => '{module}.{sub-entity}.view',
        '{ENTITY}_CREATE' => '{module}.{sub-entity}.create',
        '{ENTITY}_UPDATE' => '{module}.{sub-entity}.update',
        '{ENTITY}_DELETE' => '{module}.{sub-entity}.delete',
        '{ENTITY}_ACTIVATE' => '{module}.{sub-entity}.activate',
        '{ENTITY}_EXPORT' => '{module}.{sub-entity}.export',
    ]
];
```

---

## **Implementation Checklist**

- [ ] Import `Permission` enum at top of route file
- [ ] Add tenancy middleware to route group
- [ ] Apply appropriate permission to each route
- [ ] Use standard naming convention for permissions
- [ ] Create permissions in module's `Config/permissions.php`
- [ ] Test permission enforcement works correctly

---

## **Quick Commands**

```bash
# Test if permissions are loaded correctly
php artisan tinker
>>> \Modules\RoleAndPermission\Enums\Permission::LEAVE_TYPE_LIST()

# Clear permission cache if needed
php artisan cache:clear
```

---

**Last Updated**: 2025-08-19  
**Usage**: Copy and customize the templates above for any new module or route implementation.
