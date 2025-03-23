# Multi-Tenancy Setup and Testing Guide

This guide explains how to set up your local environment to test the multi-tenancy implementation with subdomains and how to create and manage tenants.

## Setting Up Local Subdomains

To test multi-tenancy with subdomains locally, you need to configure your system to resolve subdomains to your local machine.

### 1. Modify Your Hosts File

#### On macOS/Linux:

```bash
sudo nano /etc/hosts
```

#### On Windows:

Open Notepad as Administrator and open the file:
```
C:\Windows\System32\drivers\etc\hosts
```

#### Add the following entries:

```
127.0.0.1       localhost
127.0.0.1       tenant1.localhost
127.0.0.1       tenant2.localhost
127.0.0.1       company-name.localhost
# Add more subdomains as needed
```

Save the file and exit.

### 2. Configure Your Web Server

#### Using Laravel Valet (macOS):

If you're using Laravel Valet, you can use the `valet link` command to create a symbolic link to your project:

```bash
cd /path/to/your/project
valet link contsrix-api
```

Then, you can access your application at:
- Central application: http://contsrix-api.test
- Tenant applications: http://tenant1.contsrix-api.test, http://tenant2.contsrix-api.test, etc.

#### Using Laravel Sail:

If you're using Laravel Sail, you need to configure Nginx to handle subdomains. Create a custom Nginx configuration file:

```bash
# Create a custom Nginx configuration
mkdir -p docker/nginx/conf.d
```

Create a file `docker/nginx/conf.d/tenant.conf` with the following content:

```nginx
server {
    listen 80;
    server_name *.localhost;
    root /var/www/html/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass laravel.test:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

Update your `docker-compose.yml` file to include this configuration:

```yaml
services:
    nginx:
        volumes:
            - ./docker/nginx/conf.d:/etc/nginx/conf.d
```

Restart Sail:

```bash
./vendor/bin/sail down
./vendor/bin/sail up -d
```

#### Using PHP's Built-in Server:

You can use PHP's built-in server with a custom router script:

Create a file `server.php` in your project root:

```php
<?php

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// This file allows us to emulate Apache's "mod_rewrite" functionality
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
```

Run the server:

```bash
php -S localhost:8000 server.php
```

### 3. Configure Laravel for Subdomains

Update your `.env` file to include the domain configuration:

```
APP_URL=http://localhost:8000
TENANCY_DOMAIN=localhost
```

## Creating and Managing Tenants

### Quick Start: Create a Test Tenant

For quick testing, you can use the provided command to create a test tenant with a company and user:

```bash
php artisan tenant:create-test
```

This will:
1. Create a company named "Test Company"
2. Create a tenant for that company
3. Create a company user with email "user@example.com", password "password123", and role "admin"
4. Set up everything needed for testing

You can customize the command with options:

```bash
php artisan tenant:create-test --name="My Company" --username="my-company" --email="company@example.com" --user-name="John Doe" --user-email="john@example.com" --user-password="secure123" --user-role="manager" --send-welcome-email
```

After running this command, you can access your tenant at:
```
http://my-company.localhost:8000
```

### Manual Setup

If you prefer to set up everything manually, follow these steps:

#### 1. Run Migrations

First, run the central migrations:

```bash
php artisan migrate
```

#### 2. Create a Company

Before creating a tenant, you need to create a company. You can do this through your application's API or using Tinker:

```bash
php artisan tinker
```

```php
$company = new \Modules\Company\CompanyCore\Models\Company();
$company->id = \Ramsey\Uuid\Uuid::uuid4();
$company->name = 'Test Company';
$company->user_name = 'test-company';
$company->email = 'company@example.com';
$company->is_active = true;
$company->save();
echo "Company ID: " . $company->id;
```

Note down the company ID, you'll need it to create a tenant.

### 3. Create a Tenant

You can create a tenant in several ways:

#### Using the API:

```bash
curl -X POST \
  http://localhost:8000/api/tenants/companies/{company_id} \
  -H 'Authorization: Bearer {your_token}' \
  -H 'Content-Type: application/json'
```

Replace `{company_id}` with the ID of the company you created and `{your_token}` with a valid JWT token.

#### Using Tinker:

```bash
php artisan tinker
```

```php
$tenantService = app(\Modules\Tenant\Services\TenantService::class);
$company = \Modules\Company\CompanyCore\Models\Company::find('your-company-id');
$tenant = $tenantService->createTenant($company);
echo "Tenant created with ID: " . $tenant->id;

// Get the domain
$domain = '';
foreach ($tenant->domains as $tenantDomain) {
    $domain = $tenantDomain->domain;
    break;
}
echo "Tenant domain: " . $domain;
```

#### Using the CompanyObserver:

The `CompanyObserver` automatically creates a tenant when a company is created and activated. If you've already created a company but no tenant was created, you can update the company to trigger the observer:

```bash
php artisan tinker
```

```php
$company = \Modules\Company\CompanyCore\Models\Company::find('your-company-id');
$company->is_active = true;
$company->save();
```

### 4. Run Tenant Migrations

After creating a tenant, you need to run the tenant-specific migrations:

```bash
php artisan tenants:migrate
```

This will run the migrations in the `database/migrations/tenant` directory for all tenants.

### 5. Create a Company User

Create a company user that will be associated with the tenant:

```bash
php artisan tinker
```

```php
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
use Ramsey\Uuid\Uuid;

$companyUserService = app(CompanyUserCRUDService::class);

$createDTO = new CreateCompanyUserDTO(
    name: 'Test User',
    email: 'user@example.com',
    phone: '1234567890',
    country_id: null,
    border_number: null,
    residence: null,
    passport: null,
    identity: null,
    job_title_id: null
);

$companyRoleDTO = new CreateCompanyUserCompanyRoleDTO(
    company_id: Uuid::fromString('your-company-id'),
    role: 'admin'
);

$companyUser = $companyUserService->create($createDTO, $companyRoleDTO);
echo "Company User created with ID: " . $companyUser->id;
```

### 6. Set a Password for the Company User

Set a password for the company user:

```bash
php artisan tenant:set-user-password --tenant=your-tenant-id user@example.com password123
```

You can also specify a role for the user:

```bash
php artisan tenant:set-user-password --tenant=your-tenant-id user@example.com password123 --role=admin
```

Replace `your-tenant-id` with the ID of the tenant you created.

Note: A CompanyUser can belong to multiple companies with different roles and passwords. The password is stored in the CompanyUser record, while the role is stored in the CompanyUserCompany pivot table.

### 7. Send a Welcome Email

Send a welcome email to the company user:

```bash
php artisan tenant:send-welcome-emails --tenant=your-tenant-id --email=user@example.com
```

Or send welcome emails to all users of the tenant:

```bash
php artisan tenant:send-welcome-emails --tenant=your-tenant-id --all
```

## Testing the Multi-Tenancy Implementation

### 1. Access the Central Application

Access the central application at:

```
http://localhost:8000
```

### 2. Access a Tenant Application

Access a tenant application at:

```
http://tenant-subdomain.localhost:8000
```

Replace `tenant-subdomain` with the subdomain of your tenant (usually the company's user_name).

### 3. Test Authentication

#### Central Application Authentication:

Use the central application's authentication endpoints to authenticate as a management user.

#### Tenant Application Authentication:

Use the tenant application's authentication endpoints to authenticate as a company user:

```bash
curl -X POST \
  http://tenant-subdomain.localhost:8000/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{
    "email": "user@example.com",
    "password": "password123"
}'
```

### 4. Test with Postman

Use the provided Postman collection to test the APIs:

1. Import the collection and environment files
2. Update the environment variables
3. Run the authentication requests
4. Test the tenant-specific endpoints

## Troubleshooting

### Tenant Not Found

If you get a "Tenant not found" error, check that:

1. The subdomain is correctly configured in your hosts file
2. The tenant exists in the database
3. The tenant has a domain record with the correct subdomain

### Database Connection Issues

If you encounter database connection issues:

1. Check that the tenant database exists
2. Verify the database credentials in your `.env` file
3. Make sure the tenant migrations have been run

### Authentication Issues

If you encounter authentication issues:

1. Verify that the company user exists in the tenant database
2. Check that the password has been set correctly
3. Make sure the JWT token includes the correct tenant_id