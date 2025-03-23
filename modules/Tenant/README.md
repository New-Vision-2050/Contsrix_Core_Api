# Multi-Tenant Module

This module provides multi-tenancy functionality for the Laravel SAAS application using separate PostgreSQL schemas for each tenant, along with cross-tenant functionality for user management and reporting.

## Overview

The multi-tenant architecture is implemented using PostgreSQL schemas, where each tenant (Company) has its own schema with tenant-specific tables. This approach provides strong data isolation between tenants while allowing for efficient cross-tenant operations when needed.

## Key Components

### TenantManager

The `TenantManager` service is responsible for:
- Setting and retrieving the current tenant context
- Switching between tenant schemas
- Creating new tenant schemas
- Running migrations for tenant schemas

### Middleware

The `TenantMiddleware` automatically sets the tenant context based on the request:
- Checks for tenant ID in headers, query parameters, or route parameters
- Sets the appropriate tenant context for the request
- Resets the tenant context after the request is processed

### Models

Tenant-specific models use the `BelongsToTenant` trait, which:
- Automatically scopes queries to the current tenant's schema
- Provides helper methods for working with tenant data

### Cross-Tenant Functionality

The module includes cross-tenant functionality for:
- User management across tenants
- Reporting and analytics across all tenants
- Tenant activity monitoring

## Database Structure

### Shared Tables (Public Schema)

These tables are shared across all tenants:
- companies
- company_users
- company_users_companies
- countries
- company_types
- company_fields
- company_registration_types
- job_titles
- time_zones
- languages
- currencies
- audits

### Tenant-Specific Tables (Tenant Schemas)

Each tenant has its own schema with the following tables:
- projects
- tasks
- documents

## Usage

### Creating a New Tenant

```bash
php artisan tenant:create "Tenant Name" "tenant@example.com" --subdomain=tenant1 --plan=basic
```

### Setting Tenant Context in Code

```php
use Modules\Tenant\Facades\Tenant;
use Modules\Company\CompanyCore\Models\Company;

// Set tenant by Company model
$company = Company::find($companyId);
Tenant::setTenant($company);

// Set tenant by ID
Tenant::setTenantById($companyId);

// Reset tenant context
Tenant::resetTenant();
```

### Creating Tenant-Aware Models

```php
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Traits\BelongsToTenant;

class YourModel extends Model
{
    use BelongsToTenant;
    
    // Your model implementation
}
```

### Cross-Tenant Reporting

The `CrossTenantReportController` provides endpoints for:
- Listing all tenants
- Getting tenant summaries
- Viewing users across tenants
- Analyzing tenant activity
- Tracking user activity across tenants

## Configuration

Configuration options are available in `config/tenant.php`:
- Database connection settings
- Default schema
- Shared tables list
- Domain configuration

## Security Considerations

- Tenant data is isolated at the database level using schemas
- Cross-tenant operations require specific permissions
- Middleware ensures requests only access the appropriate tenant's data