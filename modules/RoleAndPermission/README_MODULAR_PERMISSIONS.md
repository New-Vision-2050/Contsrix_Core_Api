# Modular Permissions System

This document explains the new modular permissions system that splits the monolithic permissions configuration into module-specific files while maintaining full backward compatibility.

## Overview

The system automatically merges permission configurations from all modules into a single array accessible via `config('permissions.permissions')`, ensuring the existing Permission enum and all code continues to work without modification.

## Architecture

### Components

1. **PermissionConfigService** - Handles discovery and merging of module permission files
2. **Updated Permission Enum** - Uses the service while maintaining backward compatibility
3. **Updated Service Provider** - Registers merged permissions at application boot
4. **Module Permission Files** - Individual `permissions.php` files in each module's Config directory

### File Structure

```
modules/
├── User/Config/permissions.php
├── UserInfo/Config/permissions.php
├── Company/Config/permissions.php
├── JobTitle/Config/permissions.php
├── Country/Config/permissions.php
├── Setting/Config/permissions.php
└── RoleAndPermission/Config/permissions.php
```

## Usage

### Creating Module Permissions

Create a `permissions.php` file in your module's `Config` directory:

```php
<?php

return [
    'permissions' => [
        'MODULE_PERMISSION_VIEW' => 'module.permission.view',
        'MODULE_PERMISSION_CREATE' => 'module.permission.create',
        // ... more permissions
    ]
];
```

### Using Permissions (Unchanged)

The Permission enum continues to work exactly as before:

```php
// In routes
Route::get('/users', [UserController::class, 'index'])
    ->permission(Permission::USER_LIST());

// In code
$canView = auth()->user()->can(Permission::USER_VIEW());

// Get all permissions
$allPermissions = Permission::all();
```

### Management Commands

Use the new Artisan command to manage permissions:

```bash
# List all permissions
php artisan permissions:manage list

# List permissions for specific module
php artisan permissions:manage list --module=User

# Show modules with permissions
php artisan permissions:manage show-modules

# Validate for conflicts/duplicates
php artisan permissions:manage validate

# Clear permissions cache
php artisan permissions:manage clear-cache
```

## Features

### Automatic Discovery

The system automatically discovers all modules with `Config/permissions.php` files and merges their permissions.

### Caching

Permissions are cached for 1 hour to improve performance. The cache is automatically cleared when needed.

### Validation

Built-in validation checks for:
- Duplicate permission keys across modules
- Permission conflicts
- Module structure integrity

### Backward Compatibility

- Existing Permission enum usage unchanged
- Route macros continue to work
- Seeders continue to work
- All existing code continues to work

### New Methods

The Permission enum now includes additional methods:

```php
// Get permissions for specific module
Permission::getModulePermissions('User');

// Get all modules with permissions
Permission::getModulesWithPermissions();

// Clear permissions cache
Permission::clearCache();
```

## Migration Process

1. **Backup**: Original permissions.php is backed up as permissions.backup.php
2. **Split**: Permissions split into module-specific files
3. **Merge**: System automatically merges all module permissions
4. **Validate**: Use validation command to check for conflicts

## Benefits

- **Modularity**: Each module manages its own permissions
- **Maintainability**: Easier to find and modify module-specific permissions
- **Scalability**: New modules automatically integrate their permissions
- **Organization**: Permissions grouped logically by module functionality
- **No Breaking Changes**: Existing code requires no modifications

## File Locations

- **Service**: `modules/RoleAndPermission/Services/PermissionConfigService.php`
- **Command**: `modules/RoleAndPermission/Commands/ManageModulePermissionsCommand.php`
- **Enum**: `modules/RoleAndPermission/Enums/Permission.php` (updated)
- **Provider**: `modules/RoleAndPermission/Providers/RoleAndPermissionServiceProvider.php` (updated)

## Cache Management

The system uses Laravel's cache to store merged permissions for 1 hour. Cache is automatically managed but can be manually cleared:

```bash
# Clear permissions cache specifically
php artisan permissions:manage clear-cache

# Or clear all application cache
php artisan cache:clear
```

## Troubleshooting

### Permission Not Found Error

If you get "Permission constant 'X' not found", check:

1. The permission exists in a module's permissions.php file
2. The module's Config directory exists
3. The permissions.php file has correct structure
4. Cache is cleared: `php artisan permissions:manage clear-cache`

### Validation Issues

Run the validation command to check for conflicts:

```bash
php artisan permissions:manage validate
```

This will show any duplicate keys or permission conflicts between modules.
