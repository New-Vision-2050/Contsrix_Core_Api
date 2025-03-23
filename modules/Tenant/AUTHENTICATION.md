# Tenant Authentication System

This document explains how to use the tenant authentication system, which allows CompanyUsers to authenticate within tenant contexts while Users authenticate to the management dashboard.

## Overview

The system implements a dual authentication approach:

1. **Management Dashboard Authentication**: Uses the `User` model and authenticates users to the central application.
2. **Tenant Authentication**: Uses the `TenantUser` model (which extends `CompanyUser`) and authenticates users to tenant-specific applications.

## Welcome Email System

When a new CompanyUser is created, the system automatically sends a welcome email containing:

1. The tenant URL (domain)
2. Login instructions with temporary credentials
3. A direct link to the login page

This email is sent through the `TenantWelcomeNotification` class, which is triggered by the `UserCreated` event.

## How It Works

### Tenant User Authentication

When a CompanyUser logs in through a tenant domain:

1. The system identifies the tenant based on the domain
2. The TenantAuthService authenticates the user against the tenant's database
3. A JWT token is generated with tenant_id included in the claims
4. The VerifyTenantToken middleware validates that the token's tenant_id matches the current tenant

### JWT Token Structure

The JWT token for tenant users includes the following custom claims:

```json
{
  "tenant_id": "tenant-uuid",
  "company_id": "company-uuid"
}
```

## Setup

### 1. Migrate Tenant Databases

Run the tenant migrations to add authentication fields to the company_users table:

```bash
php artisan tenants:migrate
```

### 2. Set Passwords for Company Users

Use the provided command to set passwords for company users:

```bash
php artisan tenant:set-user-password tenant-id user@example.com password123
```

### 3. Send Welcome Emails

You can manually send welcome emails to tenant users using the following commands:

```bash
# Send welcome email to a specific user
php artisan tenant:send-welcome-emails tenant-id --email=user@example.com

# Send welcome emails to all users of a tenant
php artisan tenant:send-welcome-emails tenant-id --all
```

Welcome emails include:
- The tenant URL
- Temporary login credentials
- Instructions for first-time login

## API Endpoints

### Tenant Authentication Endpoints

These endpoints are available on tenant domains (e.g., `company-name.yourdomain.com`):

- **POST /api/auth/login**
  - Request: `{ "email": "user@example.com", "password": "password123" }`
  - Response: `{ "token": "jwt-token", "user": { "id": "user-id", "name": "User Name", "email": "user@example.com", "tenant_id": "tenant-id", "company_id": "company-id" } }`

- **GET /api/auth/me**
  - Headers: `Authorization: Bearer jwt-token`
  - Response: `{ "user": { "id": "user-id", "name": "User Name", "email": "user@example.com", "tenant_id": "tenant-id", "company_id": "company-id" } }`

- **POST /api/auth/refresh**
  - Headers: `Authorization: Bearer jwt-token`
  - Response: `{ "token": "new-jwt-token" }`

- **POST /api/auth/logout**
  - Headers: `Authorization: Bearer jwt-token`
  - Response: `{ "message": "Successfully logged out" }`

## Middleware

The system includes a `tenant.auth` middleware that:

1. Verifies the JWT token is valid
2. Checks that the token's tenant_id matches the current tenant
3. Authenticates the user

Add this middleware to routes that require tenant authentication:

```php
Route::middleware(['tenant.auth'])->group(function () {
    // Protected tenant routes
});
```

## Implementation Details

### Models

- **TenantUser**: Extends the CompanyUser model and implements JWTSubject
  - Located at: `modules/Tenant/Models/TenantUser.php`
  - Used for tenant-specific authentication

### Services

- **TenantAuthService**: Handles tenant-specific authentication
  - Located at: `modules/Tenant/Services/TenantAuthService.php`
  - Methods: login, getAuthenticatedUser, refreshToken, logout

### Controllers

- **TenantAuthController**: Exposes tenant authentication endpoints
  - Located at: `modules/Tenant/Controllers/TenantAuthController.php`
  - Endpoints: login, me, refresh, logout

### Middleware

- **VerifyTenantToken**: Validates tenant JWT tokens
  - Located at: `modules/Tenant/Middleware/VerifyTenantToken.php`
  - Registered as 'tenant.auth' middleware

## Usage Example

### Frontend Login to Tenant

```javascript
// Example using fetch API
async function loginToTenant(email, password) {
  const response = await fetch('https://company-name.yourdomain.com/api/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ email, password }),
  });
  
  const data = await response.json();
  
  if (response.ok) {
    // Store token in localStorage or secure cookie
    localStorage.setItem('tenant_token', data.token);
    return data.user;
  } else {
    throw new Error(data.message || 'Authentication failed');
  }
}
```

### Making Authenticated Requests

```javascript
async function fetchTenantData(endpoint) {
  const token = localStorage.getItem('tenant_token');
  
  const response = await fetch(`https://company-name.yourdomain.com/api/${endpoint}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
  });
  
  return await response.json();
}
```

## Security Considerations

1. The tenant authentication system ensures that users can only access their own tenant's data
2. JWT tokens include the tenant_id to prevent cross-tenant access
3. The VerifyTenantToken middleware validates that the token's tenant_id matches the current tenant
4. Passwords are hashed using Laravel's built-in hashing mechanism