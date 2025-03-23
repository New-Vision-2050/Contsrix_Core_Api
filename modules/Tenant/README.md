# Tenant Module

This module implements multi-tenancy in the application using the stancl/tenancy package. Each company is considered a tenant, and the module provides functionality for tenant management, tenant-specific operations, and cross-tenant reporting.

## Overview

The multi-tenancy implementation follows these key principles:

1. Each company is a tenant with its own database
2. Tenant identification is done via domain/subdomain
3. Central database contains company and tenant information
4. Tenant databases contain tenant-specific data
5. Cross-tenant reporting capabilities are available

## Setup

The module is already set up with the necessary components:

1. The stancl/tenancy package is installed
2. Tenant model extends the stancl/tenancy Tenant model
3. Migrations for tenants and domains tables are published
4. Tenant-specific migrations are in `database/migrations/tenant`
5. Configuration is in `config/tenancy.php`

## Usage

### Creating Tenants

Tenants are automatically created when a company is created and activated. This is handled by the `CompanyObserver` which listens for company creation and update events.

You can also manually create a tenant for a company:

```php
use Modules\Tenant\Services\TenantService;

$tenantService = app(TenantService::class);
$tenant = $tenantService->createTenant($company);
```

### Tenant Context

When accessing the application via a tenant domain (e.g., `company-name.yourdomain.com`), the tenant context is automatically initialized. You can access the current tenant using:

```php
$tenant = tenant();
```

### Running Code in Tenant Context

To run code within a specific tenant's context:

```php
$tenant->run(function () {
    // This code runs in the tenant's context
    $users = \Modules\CompanyUser\Models\CompanyUser::all();
    // ...
});
```

### Cross-Tenant Reporting

The module provides a `TenantReportingService` for generating reports across all tenants:

```php
use Modules\Tenant\Services\TenantReportingService;

$reportingService = app(TenantReportingService::class);

// Get user count by tenant
$userCounts = $reportingService->getUserCountByTenant();

// Get total user count
$totalUsers = $reportingService->getTotalUserCount();

// Run custom report across all tenants
$customReport = $reportingService->getCustomReport(function () {
    // This runs within each tenant's context
    return [
        'active_users' => \Modules\CompanyUser\Models\CompanyUser::where('status', 'active')->count(),
        // Add more metrics as needed
    ];
});

// Execute code for each tenant
$reportingService->forEachTenant(function ($tenant) {
    // This runs within each tenant's context
    // You can perform operations, updates, etc.
});
```

## API Endpoints

The module provides the following API endpoints:

### Tenant Management

- `GET /api/tenants` - List all tenants
- `POST /api/tenants/companies/{companyId}` - Create a new tenant for a company
- `GET /api/tenants/{id}` - Get tenant details
- `DELETE /api/tenants/{id}` - Delete a tenant

### Reporting

- `GET /api/tenants/reports/users` - Get user count by tenant
- `GET /api/tenants/reports/dashboard` - Get dashboard statistics
- `GET /api/tenants/reports/health` - Get tenant health report

## Database Structure

### Central Database

- `tenants` - Stores tenant information
- `domains` - Stores domain information for tenants
- `companies` - Stores company information

### Tenant Databases

Each tenant has its own database with tenant-specific tables:

- `tenant_settings` - Tenant-specific settings
- All other tenant-specific tables

## Best Practices

1. Always use the tenant context when accessing tenant-specific data
2. Use the central database for global data that should be accessible across all tenants
3. Use the reporting service for cross-tenant operations
4. Create tenant-specific migrations in `database/migrations/tenant`
5. Use the tenant middleware for tenant-specific routes

## Troubleshooting

If you encounter issues with tenant identification:

1. Check that the domain is properly registered in the `domains` table
2. Ensure the tenant exists in the `tenants` table
3. Verify that the tenant database exists
4. Check the tenant middleware is applied to the routes