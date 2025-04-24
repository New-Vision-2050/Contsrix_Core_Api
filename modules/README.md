# Test Module

## Overview
The Test module provides a complete CRUD implementation that can be used as a template for creating new modules. It follows a clean architecture pattern with separation of concerns across controllers, services, repositories, and handlers.

## Directory Structure
```
modules/Test/
├── Commands/                 # Command handlers for complex operations
├── Controllers/             # HTTP request handlers
├── Database/               
│   ├── factories/          # Model factories for testing
│   └── Migrations/         # Database migrations
├── DTO/                    # Data Transfer Objects
├── Filters/                # Query filters
├── Handlers/               # Command handlers
├── Models/                 # Eloquent models
├── Presenters/            # Data presenters/transformers
├── Providers/             # Service providers
├── Repositories/          # Data access layer
├── Requests/              # Form requests/validation
├── Resources/             # Routes and other resources
└── Services/              # Business logic layer
```

## Key Components

### Data Structure
The Test module handles entities with the following structure:

#### Model Attributes
- `id` (UUID) - Primary key
- `name` (string) - Name of the test entity

#### Features
- Uses UUID as primary key
- Implements BaseFilterable for query filtering
- Supports factory pattern for testing
- Uses DTO pattern for data transfer

#### DTO Structure
```php
CreateTestDTO {
    string $name
}
```

### API Routes
All routes require authentication (`auth:api` middleware)

```php
GET    /api/test     - List all tests (paginated)
GET    /api/test/{id} - Get single test by ID
POST   /api/test     - Create new test
PUT    /api/test/{id} - Update existing test
DELETE /api/test/{id} - Delete test
```

### Service Layer
The `TestCRUDService` handles business logic with methods:
- `create()` - Create new test
- `list()` - Get paginated list
- `get()` - Get single test by ID

### Request Validation
Separate request classes for each operation:
- CreateTestRequest
- UpdateTestRequest
- DeleteTestRequest
- GetTestRequest
- GetTestListRequest

## Creating a New Module

1. Copy the Test module structure to create a new module
2. Update the `module.json`:
   ```json
   {
       "name": "YourModuleName",
       "alias": "your-module",
       "description": "",
       "providers": [
           "Modules\\YourModule\\Providers\\YourModuleServiceProvider"
       ]
   }
   ```

3. Rename all Test classes to your module name
4. Update namespaces in all files
5. Modify the Model to reflect your data structure
6. Update the DTO and Request validation rules
7. Implement your business logic in the Service layer
8. Configure routes in `Resources/routes/api.php`

## Key Features
- UUID-based models
- Request validation
- DTO pattern
- Repository pattern
- Command/Handler pattern for complex operations
- Presenter pattern for API responses
- Factory pattern for testing
- Pagination support
- Query filtering

## Best Practices
- Use DTOs for data transfer between layers
- Implement form requests for validation
- Use repositories for database operations
- Keep business logic in services
- Use handlers for complex operations
- Use presenters for consistent API responses
- Follow SOLID principles