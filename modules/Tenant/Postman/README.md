# Tenant API Postman Collection

This directory contains Postman collection and environment files for testing the Tenant API.

## Files

- `TenantAPI.postman_collection.json`: The Postman collection containing all API requests
- `TenantAPI.postman_environment.json`: The Postman environment containing variables used in the requests

## Setup Instructions

### 1. Import the Collection and Environment

1. Open Postman
2. Click on "Import" in the top left corner
3. Select both files:
   - `TenantAPI.postman_collection.json`
   - `TenantAPI.postman_environment.json`
4. Click "Import"

### 2. Configure the Environment

1. Click on the "Environments" tab in Postman
2. Select the "Tenant API Environment"
3. Update the following variables:
   - `central_domain`: The domain of your central application (e.g., `http://localhost:8000`)
   - `tenant_domain`: The domain of your tenant application (e.g., `http://company-name.localhost:8000`)
   - `company_id`: The ID of the company you want to create a tenant for
   - `tenant_user_email`: The email of a company user
   - `tenant_user_password`: The password of the company user

### 3. Authenticate to the Central Application

Before testing tenant management endpoints, you need to authenticate to the central application:

1. Use your central application's authentication endpoint to get a token
2. Set the `central_token` environment variable with the obtained token

### 4. Test the APIs

The collection is organized into the following folders:

#### Tenant Authentication

These endpoints are for tenant-specific authentication:

1. **Login**: Authenticate a tenant user and get a JWT token
2. **Get Authenticated User**: Get information about the authenticated user
3. **Refresh Token**: Refresh the JWT token
4. **Logout**: Invalidate the JWT token

#### Tenant Information

These endpoints provide information about the current tenant:

1. **Get Tenant Info**: Get information about the current tenant

#### Tenant Management

These endpoints are for managing tenants (from the central application):

1. **List All Tenants**: Get a list of all tenants
2. **Get Tenant Details**: Get details of a specific tenant
3. **Create Tenant**: Create a new tenant for a company
4. **Delete Tenant**: Delete a tenant

#### Tenant Reporting

These endpoints provide cross-tenant reporting:

1. **Get User Count By Tenant**: Get user count by tenant
2. **Get Dashboard Statistics**: Get dashboard statistics across all tenants
3. **Get Tenant Health**: Get health report for all tenants

## Testing Flow

1. Authenticate to the central application (set `central_token`)
2. Create a tenant for a company
3. Set the `tenant_id` environment variable with the created tenant ID
4. Authenticate to the tenant application (set `tenant_token`)
5. Test tenant-specific endpoints
6. Test reporting endpoints
7. Test tenant management endpoints

## Notes

- The collection uses environment variables to store tokens and IDs
- The login request automatically sets the `tenant_token` variable
- The refresh token request automatically updates the `tenant_token` variable
- Make sure to set the correct domains in the environment variables