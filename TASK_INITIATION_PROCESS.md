# Task Initiation Process for Contsrix_Core_Api

This document outlines the standard process to follow when initiating a new complex task, feature request, or significant change within the Contsrix_Core_Api project. Adhering to this process ensures clarity, alignment, and efficient development while maintaining architectural consistency.

## Step 1: Define the Task & Create a Product Requirements Document (PRD)

Before development begins, a clear understanding of the task is essential. We will collaboratively create a PRD covering the following aspects:

*   **1. Task/Feature Title:** A concise name for the task.
*   **2. Requester:** Who initiated this task?
*   **3. Date:** When was this task defined?
*   **4. Problem Statement / Goal:**
    *   What specific problem is this task trying to solve?
    *   What is the primary objective or desired outcome?
*   **5. Target Audience / User Stories:**
    *   Who will benefit from this feature/change?
    *   Describe the feature from the user's perspective (e.g., "As a [type of user], I want to [perform an action] so that [I can achieve a benefit].").
*   **6. Proposed Solution / Key Features:**
    *   Describe the proposed solution at a high level.
    *   List the key functionalities and components involved.
*   **7. Acceptance Criteria:**
    *   How will we determine that the task is complete and implemented correctly?
    *   List specific, measurable, achievable, relevant, and time-bound (SMART) criteria.
*   **8. Success Metrics (Optional but Recommended):**
    *   How will we measure the success or impact of this feature after deployment?
*   **9. Technical Considerations / Constraints:**
    *   Are there specific technologies, libraries, or architectural patterns to use or avoid?
    *   Are there any performance, security, or scalability requirements?
    *   Are there any dependencies on other modules or services?
*   **10. Out of Scope:**
    *   What functionalities or aspects are explicitly *not* part of this task?
*   **11. Open Questions / Points for Discussion:**
    *   List any unresolved questions or areas needing further clarification.

*(Cascade, the AI assistant, will help prompt for these details.)*

## Step 2: Gather Up-to-Date Context (if applicable)

If the task involves specific external libraries, frameworks, or technologies where recent updates, best practices, or potential breaking changes are crucial:

*   **Utilize Context7:** The AI assistant (Cascade) will use the Context7 service to:
    1.  Resolve the correct library/product ID.
    2.  Fetch the latest documentation, relevant examples, or updates for the identified technology.
*   **Review Findings:** Briefly review the information gathered to ensure development aligns with current standards and avoids known issues.

## Step 3: Architectural Patterns & Implementation Standards

All development must strictly adhere to the following architectural patterns and coding standards:

### 3.1 Laravel Base Package Patterns

**Data Transfer Objects (DTOs):**
- Use constructor property promotion with public readonly properties
- Include `toArray()` method for array conversion
- Provide getter methods for all properties
- Example structure:
```php
class ExampleDTO
{
    public function __construct(
        public readonly string $property1,
        public readonly ?string $property2 = null,
    ) {}

    public function toArray(): array { /* implementation */ }
    public function getProperty1(): string { /* implementation */ }
}
```

**JSON Responses:**
- **MANDATORY:** Use `BasePackage\Shared\Presenters\Json` class for ALL JSON responses
- `Json::item($data, message: "message")` for single items
- `Json::items($data, message: "message")` for collections
- `Json::success("message")` for success responses without data
- **NEVER** use Laravel's default `response()->json()` method

**Exception Handling:**
- **MANDATORY:** Use `App\Exceptions\CustomException` class extending Exception
- Include `statusCode` property for HTTP status codes
- **NEVER** use try-catch blocks in controllers
- Let exceptions propagate to the global exception handler
- Create specific exception classes for each module (e.g., `AttendanceException`)

### 3.2 Controller Standards

**Controller Requirements:**
- Controllers handle ONLY HTTP and presentation logic
- Use dependency injection for services, handlers, and presenters
- **MANDATORY:** Use DTOs from Form Requests, never raw validated arrays
- **MANDATORY:** Use `Json` presenter class for all responses
- **NEVER** include try-catch blocks in controller methods
- **NEVER** include business logic in controllers

**Controller Method Structure:**
```php
public function methodName(CustomFormRequest $request): JsonResponse
{
    $dto = $request->createDTO();
    $result = $this->service->performAction($dto);
    return $this->presenter->present($result);
}
```

### 3.3 Service Layer Standards

**Service Requirements:**
- Services encapsulate ALL business logic
- **MANDATORY:** Accept DTOs/Commands as parameters, never raw arrays
- Throw custom exceptions for business rule violations
- Use repositories for data persistence
- Handle complex business operations and validations
- Return models, collections, or simple data types

### 3.4 Repository Pattern

**Repository Requirements:**
- Handle ALL data persistence and querying
- Provide methods for CRUD operations
- Include filtering, sorting, and pagination capabilities
- Use Eloquent models and Query Builder
- Return models, collections, or query results
- **NEVER** include business logic

### 3.5 Form Request Standards

**Form Request Requirements:**
- Include comprehensive validation rules and custom messages
- Implement `prepareForValidation()` for data preparation
- Use `withValidator()` for complex validation logic
- **MANDATORY:** Provide DTO creation methods (e.g., `createExampleDTO()`)
- Handle authorization logic in `authorize()` method
- Apply default values and data transformation

### 3.6 Presenter Standards

**Presenter Requirements:**
- Extend `BasePackage\Shared\Presenters\AbstractPresenter`
- Format model data into arrays suitable for JSON responses
- Include computed properties and formatted data
- Provide different view methods (e.g., `getCalendarData()`, `getReportData()`)
- Handle relationship data formatting
- **NEVER** include business logic

### 3.7 Model Standards

**Model Requirements:**
- Use proper casting for attributes (`$casts` array)
- Define clear relationships with proper return types
- Include scopes for common queries
- Implement business logic methods when appropriate
- Use constants for status values and enums
- Include proper fillable/guarded properties
- Use traits for common functionality (e.g., auditing, soft deletes)

### 3.8 Database & Migration Standards

**Migration Requirements:**
- Use descriptive table and column names
- Include proper indexes for performance
- Set up foreign key constraints with cascade options
- Use appropriate data types and lengths
- Include comments for complex fields
- Consider multi-tenancy requirements

### 3.9 API Route Standards

**Route Requirements:**
- Use resource routes where appropriate
- Apply proper middleware (auth, permissions, tenant)
- Group related routes logically
- Use descriptive route names
- Include rate limiting where necessary
- Document all endpoints

### 3.10 Multi-Tenancy Considerations

**Tenant-Aware Development:**
- All models must be tenant-aware using stancl/tenancy
- Use `tenant('id')` for company_id when applicable
- Ensure data isolation between tenants
- Consider tenant-specific configurations
- Test multi-tenant scenarios

### 3.11 Security & Permission Standards

**Security Requirements:**
- Use JWT authentication for API access
- Implement Spatie permissions for role-based access
- Validate user permissions in Form Requests or middleware
- Sanitize all user inputs
- Use HTTPS for all communications
- Implement proper CORS policies

### 3.12 Testing Requirements

**Testing Standards:**
- Write unit tests for services and repositories
- Create integration tests for complete workflows
- Test DTO creation and validation
- Test exception handling scenarios
- Test multi-tenant data isolation
- Test permission and authorization logic
- Use factories for test data generation

## Step 4: Module Structure Requirements

When creating new modules, follow this structure:

```
modules/ModuleName/
├── Controllers/
├── Services/
├── Repositories/
├── Models/
├── DTO/
├── Requests/
├── Presenters/
├── Exceptions/
├── Providers/
│   ├── ModuleServiceProvider.php
│   └── RouteServiceProvider.php
├── Routes/
│   ├── api.php
│   └── web.php
├── Database/
│   ├── Migrations/
│   ├── Factories/
│   └── Seeders/
└── Tests/
    ├── Unit/
    └── Feature/
```

## Step 5: Code Quality & Documentation

**Quality Standards:**
- Follow PSR-12 coding standards
- Use meaningful variable and method names
- Include comprehensive PHPDoc comments
- Maintain consistent code formatting
- Use type hints for all parameters and return types
- Implement proper error handling and logging

**Documentation Requirements:**
- Update API documentation for new endpoints
- Document complex business logic
- Include usage examples in README files
- Document configuration options
- Maintain changelog for significant changes

## Step 6: Proceed with Planning & Development

Once the PRD is sufficiently detailed and any necessary external context has been gathered, development planning and execution can begin. This typically involves:

*   Breaking down the task into smaller sub-tasks following the architectural patterns
*   Identifying affected modules and code areas
*   Creating necessary DTOs, Form Requests, Services, and Repositories
*   Implementing controllers with proper dependency injection
*   Creating comprehensive tests
*   Updating documentation
*   Following the code review process

## Step 7: Implementation Checklist

Before considering a task complete, ensure:

- [ ] All components follow the architectural patterns outlined above
- [ ] DTOs are used throughout the service layer
- [ ] JSON responses use the `Json` presenter class
- [ ] No try-catch blocks exist in controllers
- [ ] Custom exceptions are properly implemented
- [ ] Form Requests include DTO creation methods
- [ ] Services contain business logic and use repositories
- [ ] Repositories handle data persistence only
- [ ] Presenters format data appropriately
- [ ] Models include proper relationships and casting
- [ ] Multi-tenancy is properly implemented
- [ ] Security and permissions are enforced
- [ ] Tests cover all major functionality
- [ ] Documentation is updated
- [ ] Code follows PSR-12 standards

---

By following this comprehensive process, we ensure robust, maintainable, and architecturally consistent features that align with the Contsrix_Core_Api standards and Laravel best practices.
