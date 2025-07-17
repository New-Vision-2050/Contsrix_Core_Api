# Package Assignment API Documentation

## Overview

This API allows you to assign multiple packages to a company with automatic permission limit handling. When multiple packages contain the same permission with different limits, the system automatically takes the highest limit to avoid conflicts.

## API Endpoint

**POST** `/api/packages/assign-to-company`

### Request Body

```json
{
    "company_id": "550e8400-e29b-41d4-a716-446655440000",
    "package_ids": [
        "550e8400-e29b-41d4-a716-446655440001",
        "550e8400-e29b-41d4-a716-446655440002"
    ]
}
```

### Request Parameters

- `company_id` (required, UUID): The ID of the company to assign packages to
- `package_ids` (required, array): Array of package UUIDs to assign to the company

### Response

#### Success Response (200)

```json
{
    "success": true,
    "message": "Packages successfully assigned to company.",
    "data": {
        "company_id": "550e8400-e29b-41d4-a716-446655440000",
        "assigned_packages": [
            "550e8400-e29b-41d4-a716-446655440001",
            "550e8400-e29b-41d4-a716-446655440002"
        ],
        "permission_limits": {
            "permission-id-1": 100,
            "permission-id-2": 50
        }
    }
}
```

#### Error Response (422)

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "company_id": ["Company ID is required."],
        "package_ids": ["At least one package must be selected."]
    }
}
```

## API Endpoints

### 1. Assign Packages to Company
`POST /api/packages/assign-to-company`

### 2. Sync Package Permissions with Limits  
`POST /api/packages/{package}/assign-permissions`

Assigns permissions to a package with optional limits.

**Request Body:**
```json
{
  "permissions": [
    "permission-uuid-1",
    "permission-uuid-2"
  ],
  "limits": [
    {
      "permission_id": "permission-uuid-1", 
      "number": 100
    }
  ]
}
```

**Features:**
- **permissions**: Array of permission IDs to assign to the package
- **limits**: Optional array to set usage limits for specific permissions
- **Automatic Recalculation**: Updates all companies using this package with new limits
- **Preservation of Usage**: When limits change, used counts are preserved

**Response:**
```json
{
  "message": "Permissions synced successfully with limits."
}
```

**Example Scenarios:**
- Assign unlimited permissions: Only include in `permissions` array
- Assign limited permissions: Include in both `permissions` and `limits` arrays  
- Mix unlimited and limited: Some permissions in `limits`, others unlimited

**Usage Examples**

## Features

### 1. Automatic Limit Resolution

When assigning multiple packages that contain the same permission with different limits, the system automatically:
- Takes the **highest limit** for each permission
- Creates entries in the `company_permissions_limits` table
- Sets both `limit` and `actual_limit` to the maximum value initially

**Example:**
- Package A has Permission X with limit 10
- Package B has Permission X with limit 15
- Result: Company gets Permission X with limit 15

### 2. Permission Usage Tracking

The system tracks permission usage through the middleware:
- When a permission is used, `actual_limit` is decremented
- Access is denied when `actual_limit` reaches 0
- The `limit` field preserves the original maximum

### 3. Dynamic Limit Updates

When package permission limits are updated:
- All companies with those packages get their limits recalculated
- Used permissions are preserved (usage = original_limit - current_actual_limit)
- New limits are applied while maintaining usage history

## Permission Limit Resolution Logic

When multiple packages are assigned to a company and they contain the same permission with different limits, the system applies the following resolution logic:

### Unlimited Takes Precedence
- **If any package grants unlimited access** (limit = null) to a permission, that permission becomes **unlimited** for the company
- **Limited permissions cannot override unlimited permissions**

### Highest Limit Wins (Among Limited Permissions)
- If all packages have limits for the same permission, the **highest limit** is applied
- Example: Package A grants 10 uses, Package B grants 15 uses → Company gets 15 uses

### Example Scenarios

**Scenario 1: Unlimited vs Limited**
- Package A: Permission "create_project" = null (unlimited)  
- Package B: Permission "create_project" = 100 (limited)
- **Result**: Company gets unlimited "create_project" access

**Scenario 2: Multiple Limited**  
- Package A: Permission "export_data" = 50
- Package B: Permission "export_data" = 75
- **Result**: Company gets 75 "export_data" uses

**Scenario 3: Mixed Permissions**
- Package A: "create_project" = null, "export_data" = 25
- Package B: "create_project" = 100, "export_data" = 50  
- **Result**: "create_project" = unlimited, "export_data" = 50

## Architecture

This implementation follows the Repository pattern for better separation of concerns and testability:

### Repository Classes

- **PackageRepository**: Handles package-related database operations
- **CompanyPermissionLimitRepository**: Manages permission limits for companies
- **CompanyRepository**: Company-related operations including package syncing  
- **PermissionRepository**: Permission lookup and management

### Service Layer

- **PackageAssignmentService**: Core business logic for package assignment
  - Uses dependency injection for all repositories
  - Handles complex permission limit calculations
  - Manages transactional integrity

### Benefits of Repository Pattern

1. **Testability**: Easy to mock repositories for unit testing
2. **Separation of Concerns**: Business logic separated from data access
3. **Maintainability**: Centralized database queries for each entity  
4. **Flexibility**: Easy to switch data sources or add caching layers

## Database Schema

### company_permissions_limits Table

| Column | Type | Description |
|--------|------|-------------|
| id | UUID | Primary key |
| company_id | UUID | Foreign key to companies table |
| permission_id | UUID | Foreign key to permissions table |
| limit | INTEGER | Maximum allowed usage |
| actual_limit | INTEGER | Remaining usage |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

## Usage Examples

### 1. Basic Assignment

```bash
curl -X POST /api/packages/assign-to-company \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "company_id": "550e8400-e29b-41d4-a716-446655440000",
    "package_ids": ["pkg-1", "pkg-2"]
  }'
```

### 2. Checking Permission Limits

```php
// Get company's permission limits
$company = Company::find($companyId);
$limits = $company->permissionLimits()->with('permission')->get();

foreach ($limits as $limit) {
    echo "Permission: {$limit->permission->name}\n";
    echo "Max Limit: {$limit->limit}\n";
    echo "Remaining: {$limit->actual_limit}\n";
    echo "Used: " . ($limit->limit - $limit->actual_limit) . "\n\n";
}
```

### 3. Manual Limit Management

```php
// Find a specific limit
$limit = CompanyPermissionLimit::where('company_id', $companyId)
    ->where('permission_id', $permissionId)
    ->first();

// Decrease limit (use permission)
$limit->decreaseLimit(1);

// Increase limit (restore usage)
$limit->increaseLimit(1);

// Check if exceeded
if ($limit->isLimitExceeded()) {
    throw new Exception('Permission limit exceeded');
}

// Reset to maximum
$limit->resetLimit();
```

## Error Handling

The API handles various error scenarios:

1. **Invalid Company ID**: Returns 422 with validation error
2. **Invalid Package IDs**: Returns 422 with validation error
3. **Non-existent Company**: Returns 422 with validation error
4. **Non-existent Packages**: Returns 422 with validation error
5. **Database Errors**: Returns 500 with generic error message

## Middleware Integration

The `PermissionMiddleware` automatically:
1. Checks if a permission has usage limits for the company
2. Verifies remaining usage before allowing access
3. Decrements `actual_limit` when permission is used
4. Throws `UnauthorizedException` when limits are exceeded

## Testing

Run the test suite to verify functionality:

```bash
php artisan test modules/Subscription/Package/Tests/Feature/PackageAssignmentTest.php
```

## Security Considerations

1. **Authorization**: All API endpoints require authentication
2. **Validation**: All inputs are validated and sanitized
3. **Database Transactions**: All operations are wrapped in database transactions
4. **Permission Checks**: Middleware prevents unauthorized access
5. **Limit Enforcement**: Strict enforcement of usage limits

## Performance Notes

1. **Indexing**: The `company_permissions_limits` table has proper indexes
2. **Batch Operations**: Limit updates use batch inserts for efficiency
3. **Caching**: Consider implementing caching for frequently accessed limits
4. **Cleanup**: Periodically clean up expired or unused limit records
