# Documentation for Contsrix_Core_Api (for LLMs and AI Code Editors)

## 1. Project Overview

**Project Name:** Contsrix_Core_Api
**Type:** Laravel API Backend
**PHP Version:** ^8.2 (as per `composer.json`)
**Description:** This is the core API for the Contsrix platform. While the specific domain isn't explicitly detailed in a project-level README, the module names (e.g., `Company`, `Program`, `ArchiveLibrary`, `SubEntity`) suggest it's an enterprise-level application likely focused on company management, program administration, content/archive handling, and related functionalities, built with a multi-tenant architecture.

**Key Technologies & Dependencies:**

*   **Laravel Framework:** Version ^11.31. The core framework for the application.
*   **Multi-Tenancy:** Implemented using `stancl/tenancy` (^3.9). This is a critical aspect, meaning the application serves multiple distinct tenants with isolated data.
*   **Authentication:** `tymon/jwt-auth` (^2.1) is used for API authentication via JSON Web Tokens.
*   **Permissions & Roles:** `spatie/laravel-permission` (^6.10) manages user roles and permissions.
*   **Media Management:** `spatie/laravel-medialibrary` (^11.12) handles file uploads and associations.
*   **Auditing:** `owen-it/laravel-auditing` (^14.0) for tracking model changes.
*   **Background Queues:** `vladimir-yuldashev/laravel-queue-rabbitmq` (^14.1) and `php-amqplib/php-amqplib` (^3.7) suggest RabbitMQ is used for robust background job processing.
*   **Custom Packages:**
    *   `m-tech-stack/base-package` (^1.0)
    *   `m-tech-stack/rabbit-mq` (^1.0)
    These are likely internal packages providing foundational functionalities or specific integrations for the M-Tech Stack.
*   **Database & ORM:** Laravel Eloquent ORM with database agnostic schema migrations.
*   **API Development:**
    *   Uses DTOs (Data Transfer Objects).
    *   Presenter pattern for API responses.
    *   Request validation classes.
*   **Development Tools:**
    *   `laravel/telescope`: For debugging (though `dont-discover` is set).
    *   `fakerphp/faker`: For generating fake data.
    *   `phpunit/phpunit`: For testing.

## 2. Architecture

The project follows a modular architecture, with distinct business domains encapsulated within their own modules located in the `modules/` directory. This promotes separation of concerns and scalability.

### 2.1. Modular Structure

*   **Location:** `modules/`
*   **Namespace:** `Modules\` (e.g., `Modules\User`, `Modules\Company`)
*   **Purpose:** Each module represents a specific feature or domain of the application (e.g., `Auth`, `Company`, `ArchiveLibrary`, `Setting`).
*   **`Shared` Module:** A significant module named `Shared` likely contains common utilities, base classes, traits, or services used across multiple other modules.
*   **Module Configuration:** Each module typically has a `module.json` file defining its name, alias, description, and service providers.

### 2.2. Understanding Module Anatomy and Workflow (for LLMs)

Each module in the `modules/` directory is a self-contained unit representing a specific business domain. To effectively work with or create modules, an LLM should understand the following standard structure and the role of each component, as exemplified by the `Test` module template:

*   **`modules/YourModuleName/`**: The root directory for your specific module.

    *   **`Commands/`**:
        *   **Role:** Houses Command classes and their Handlers. This pattern is used for complex operations that don't fit neatly into a simple CRUD service method or when you want to decouple the invoker of an operation from its executor.
        *   **LLM Action:** When a task involves a multi-step, complex business process, consider if a Command/Handler is appropriate. Look for existing commands to understand their structure.

    *   **`Controllers/`**:
        *   **Role:** Entry point for HTTP requests related to the module. Controllers are responsible for receiving requests, validating essential parameters (often delegated to Form Request classes), invoking the appropriate Service methods, and formatting the response (often using Presenters).
        *   **LLM Action:** When adding new API endpoints, create methods in the module's controller. Keep controllers lean; business logic belongs in Services.

    *   **`Database/`**:
        *   `factories/`: Contains model factories (e.g., `YourModuleNameFactory.php`) used for generating test data and seeding.
        *   `Migrations/`: Holds database schema migration files for the module's tables.
        *   **LLM Action:** When creating a new entity, define its factory and migration here. Ensure migrations are tenant-aware if necessary.

    *   **`DTO/ (Data Transfer Objects)`**:
        *   **Role:** Simple PHP objects used to pass structured data between layers (e.g., from a Controller/Request to a Service, or from a Service to a Repository). They define clear data contracts and improve type safety.
        *   **LLM Action:** For any data being passed, especially for creation or update operations, define a DTO. This helps in understanding expected data structures.

    *   **`Filters/`**:
        *   **Role:** Contains classes for applying dynamic query filters to Eloquent models. Often used for searching or refining list results.
        *   **LLM Action:** If an entity needs to be searchable by various criteria, implement filter classes here and apply them in the Repository or Service.

    *   **`Handlers/`**:
        *   **Role:** (Can be synonymous with or part of `Commands/`) Contains the logic for executing commands. Each handler typically corresponds to a specific command.
        *   **LLM Action:** Implement the core logic of a Command in its respective Handler class.

    *   **`Models/`**:
        *   **Role:** Eloquent models representing the module's database entities (e.g., `YourModuleName.php`). These define attributes, relationships, scopes, and other data-related logic. Often use UUIDs and may include traits for common functionality (e.g., filtering, auditing).
        *   **LLM Action:** Define the primary data structures and their relationships here. Pay attention to `$fillable`, `$casts`, and relationships.

    *   **`Presenters/`**:
        *   **Role:** Transform Eloquent models or collections into a standardized format for API responses. This ensures consistency in how data is exposed to clients.
        *   **LLM Action:** When returning data from an API endpoint, use a Presenter to format the output. This helps maintain a consistent API structure.

    *   **`Providers/`**:
        *   **Role:** Module-specific Service Providers (e.g., `YourModuleNameServiceProvider.php`). Used to register the module's services, bindings, event listeners, route model bindings, etc., with Laravel's IoC container. Also often loads module routes and migrations.
        *   **LLM Action:** Ensure the module's Service Provider is correctly configured and registered in the `module.json` file. Add any necessary bindings or configurations here.

    *   **`Repositories/`**:
        *   **Role:** Abstract the data access logic. Repositories interact directly with Eloquent Models to perform CRUD operations and complex queries. They provide a clean API for services to fetch and persist data without knowing the underlying database details.
        *   **LLM Action:** All database interactions from the Service layer should go through a Repository. Implement methods for fetching, creating, updating, and deleting entities.

    *   **`Requests/ (Form Requests)`**:
        *   **Role:** Dedicated classes for validating incoming HTTP request data (e.g., `CreateYourModuleNameRequest.php`). They contain authorization logic and validation rules.
        *   **LLM Action:** For every API endpoint that accepts data, create a Form Request class to handle validation. This keeps controllers clean and centralizes validation logic.

    *   **`Resources/`**:
        *   `routes/api.php`: Defines the API routes specific to this module. These are typically loaded by the module's Service Provider.
        *   Other resources like views (if any, though less common for pure APIs) or language files might also reside here.
        *   **LLM Action:** Define all API endpoints for the module in this file. Ensure they are prefixed appropriately and use the correct middleware (e.g., `auth:api`).

    *   **`Services/`**:
        *   **Role:** The core of the module's business logic. Services orchestrate operations by interacting with Repositories, DTOs, other Services (from the same or different modules), and external systems. They handle data manipulation, business rule enforcement, and complex workflows.
        *   **LLM Action:** This is where the primary business logic for any feature should be implemented. Services should be injected into Controllers.

    *   **`module.json` (in the module's root)**:
        *   **Role:** A manifest file for the module. It defines the module's `name`, `alias` (used in paths/URLs), `description`, and an array of `providers` (listing the module's service providers to be registered by the application).
        *   **LLM Action:** This file is crucial for the module to be recognized and loaded by the application. Ensure it's correctly configured when creating a new module.

**Typical Request Workflow within a Module:**

1.  **HTTP Request** hits a route defined in `modules/YourModuleName/Resources/routes/api.php`.
2.  The **Route** directs the request to a method in `modules/YourModuleName/Controllers/YourModuleNameController.php`.
3.  The **Controller** method type-hints a specific **Form Request** class (e.g., `CreateYourModuleNameRequest`) from `modules/YourModuleName/Requests/`.
4.  The **Form Request** class automatically performs authorization and validates the incoming data.
5.  If validation passes, the **Controller** method instantiates or receives an instance of the module's **Service** (e.g., `YourModuleNameService` from `modules/YourModuleName/Services/`).
6.  The **Controller** calls a method on the **Service**, passing necessary data (often as a **DTO** created from the validated request).
7.  The **Service** executes the business logic. This may involve:
    *   Calling methods on its **Repository** (e.g., `YourModuleNameRepository` from `modules/YourModuleName/Repositories/`) to interact with the database (Models).
    *   Using **DTOs** to pass data to the Repository.
    *   Invoking other **Services** or **Handlers/Commands**.
8.  The **Repository** interacts with the **Eloquent Model** (`modules/YourModuleName/Models/YourModuleName.php`) to perform database operations.
9.  The **Service** receives data back from the Repository (often Eloquent models or collections).
10. The **Service** returns the result to the **Controller**.
11. The **Controller** uses a **Presenter** (`modules/YourModuleName/Presenters/`) to format the data for the API response.
12. The **Controller** returns the HTTP response.

### 2.3. Multi-Tenancy

*   Implemented with `stancl/tenancy`.
*   LLMs should be aware that database queries, file storage, and other aspects might be tenant-specific.
*   Understanding the tenant identification mechanism (e.g., domain, subdomain, request header) and how tenant context is applied will be crucial for generating correct code or analysis.

### 2.4. Authentication

*   JWT-based (`tymon/jwt-auth`).
*   API routes are generally protected by the `auth:api` middleware.
*   The `Auth` module likely handles user registration, login, token generation, etc.

### 2.5. Helper Functions

*   A significant `app/helpers.php` file (80KB+) exists. This file likely contains numerous global utility functions that might be used throughout the application, including within modules.

## 3. Key Modules (Inferred from `modules/` directory)

*   **`ActivityLog`**: Tracks user or system activities.
*   **`AdminRequest`**: Potentially for handling requests made by administrators or to an admin panel.
*   **`ArchiveLibrary`**: Manages an archive or library of documents/media.
*   **`Audit`**: Likely integrates with `owen-it/laravel-auditing` for detailed model change tracking.
*   **`Auth`**: Handles user authentication, registration, password management, JWTs.
*   **`Company`**: Manages company-level entities and data. Core to a multi-tenant B2B system.
*   **`CompanyUser`**: Manages users within companies, possibly roles and relationships.
*   **`Country`**: Provides country-related data.
*   **`JobTitle`**: Manages job titles, likely for users.
*   **`PageBuilder`**: Suggests functionality for creating or managing dynamic pages.
*   **`Program`**: Manages programs or projects.
*   **`RoleAndPermission`**: Manages roles and permissions, likely integrating `spatie/laravel-permission`.
*   **`Setting`**: Handles application or tenant-specific settings.
*   **`Shared`**: Contains common code, base classes, traits, DTOs, etc., used across multiple modules. This is a very important module to understand for shared functionality.
*   **`SubEntity`**: Manages sub-entities, possibly related to companies or other primary entities.
*   **`User`**: Manages core user data.
*   **`UserInfo`**: Manages additional user information or profiles.

## 4. Development Guidelines

### 4.1. LLM Guide: Creating a New Module

To create a new module (e.g., "NewFeature"), an LLM should follow these detailed steps, using the `modules/Test/` directory as a structural and conceptual template:

1.  **Understand the Requirements:**
    *   What is the primary entity of the module (e.g., `NewFeature`)?
    *   What are its attributes and relationships?
    *   What CRUD operations are needed?
    *   Are there any complex business processes involved?

2.  **Duplicate and Rename the Template Module:**
    *   Copy the entire `modules/Test/` directory.
    *   Rename the copied directory to `modules/NewFeature/`.

3.  **Configure `module.json`:**
    *   Open `modules/NewFeature/module.json`.
    *   Update its content:
        ```json
        {
            "name": "NewFeature",
            "alias": "new-feature", // or a suitable URL-friendly alias
            "description": "Manages NewFeature entities and related functionalities.",
            "providers": [
                "Modules\\NewFeature\\Providers\\NewFeatureServiceProvider"
            ]
        }
        ```

4.  **Global Search and Replace for Namespacing and Class Names:**
    *   Within the `modules/NewFeature/` directory:
        *   Search for `Modules\Test` and replace with `Modules\NewFeature`.
        *   Search for `Test` (as a whole word, case-sensitive, for class names like `TestController`, `TestService`, `TestModel`, `TestDTO`, etc.) and replace with `NewFeature` (e.g., `NewFeatureController`, `NewFeatureService`, `NewFeatureModel`, `NewFeatureDTO`).
        *   Search for `test` (lowercase, for variable names, route segments, etc., e.g., `route('api.test.index')`) and replace with `newFeature` (camelCase) or `new-feature` (kebab-case) as appropriate for the context.

5.  **Refine Core Components:**

    *   **Model (`Models/NewFeature.php`):**
        *   Define `$fillable` attributes based on the new entity.
        *   Set up `$casts` for attribute types.
        *   Implement relationships (`belongsTo`, `hasMany`, etc.).
        *   Ensure it uses UUIDs if that's the project standard.

    *   **Database Migration (`Database/Migrations/create_new_features_table.php` - rename the file too):**
        *   Update the `up()` method to define the schema for the `new_features` table (or your entity's table name).
        *   Update the `down()` method to drop the table.

    *   **Database Factory (`Database/factories/NewFeatureFactory.php`):**
        *   Update the `definition()` method to return appropriate fake data for the `NewFeature` model.

    *   **DTOs (`DTO/`):**
        *   Create or modify DTOs (e.g., `CreateNewFeatureDTO.php`, `UpdateNewFeatureDTO.php`) to reflect the attributes required for creating and updating the `NewFeature` entity.

    *   **Requests (`Requests/`):**
        *   Update validation rules and authorization logic in classes like `CreateNewFeatureRequest.php`, `UpdateNewFeatureRequest.php`, `GetNewFeatureRequest.php`, `GetNewFeatureListRequest.php`, `DeleteNewFeatureRequest.php`.

    *   **Repository (`Repositories/NewFeatureRepository.php`):**
        *   Ensure methods (`create`, `find`, `update`, `delete`, `list`, etc.) correctly interact with the `NewFeature` model and use relevant DTOs.

    *   **Service (`Services/NewFeatureService.php`):**
        *   Implement the core business logic for `NewFeature` operations. This is where you'll use the Repository, handle DTOs, and orchestrate workflows.

    *   **Controller (`Controllers/NewFeatureController.php`):**
        *   Ensure controller methods correctly type-hint the new Form Requests and DTOs.
        *   Call the appropriate `NewFeatureService` methods.
        *   Use `NewFeaturePresenter` (if created) for responses.

    *   **Presenter (`Presenters/NewFeaturePresenter.php` - if applicable):**
        *   Define how `NewFeature` model data should be transformed for API responses.

    *   **Routes (`Resources/routes/api.php`):**
        *   Update route definitions to use the correct controller, method names, and route parameters (e.g., `/api/new-feature`, `/api/new-feature/{newFeature}`).
        *   Ensure route model binding uses `newFeature` (or the chosen parameter name).

    *   **Service Provider (`Providers/NewFeatureServiceProvider.php`):**
        *   Verify that it loads routes, migrations, and registers any necessary bindings for the `NewFeature` module.
        *   Ensure the namespace is correct: `Modules\NewFeature\Providers`.

6.  **Register the Module (if not auto-detected):**
    *   The application likely auto-discovers modules based on their `module.json` files. If not, ensure the `NewFeatureServiceProvider` is registered in `config/app.php` (though this is less common with dedicated module systems like `nWidart/laravel-modules` which this project might be using or emulating). Given the `module.json` structure, auto-discovery is probable.

7.  **Testing:**
    *   Write PHPUnit tests for the new module's functionality, covering services, repository interactions, and API endpoints. Place tests in the main `tests/` directory, possibly under a `tests/Feature/Modules/NewFeature` or `tests/Unit/Modules/NewFeature` structure.

By following these steps, an LLM can systematically create a new module that aligns with the project's established architecture and conventions.

### 4.2. Coding Standards & Best Practices (from `modules/README.md`)

*   **SOLID Principles:** Adhere to SOLID principles for maintainable and scalable code.
*   **DTOs:** Use DTOs for data transfer between layers.
*   **Form Requests:** Implement Form Requests for validation of incoming data.
*   **Repositories:** Use Repositories for all database operations to abstract data access.
*   **Services:** Keep business logic concentrated in Service classes.
*   **Handlers/Commands:** Use Handlers for complex operations or to implement the Command Bus pattern.
*   **Presenters:** Use Presenters to ensure consistent API responses.
*   **UUIDs:** Prefer UUIDs for primary keys in models.
*   **Testing:** Utilize model factories for testing.
*   **Filtering:** Implement query filtering (e.g., via `BaseFilterable` trait).
*   **Pagination:** Support pagination for list endpoints.

## 5. API Interaction

*   **Base Path:** API endpoints are generally prefixed with `/api/`. Module-specific routes often include the module alias (e.g., `/api/your-module-alias/...`).
*   **Authentication:** Most, if not all, API endpoints require JWT authentication (`auth:api` middleware).
*   **Request/Response Format:** Expect JSON for requests and responses. Responses are likely structured by Presenters.
*   **Standard CRUD Operations:** Modules often provide standard CRUD endpoints:
    *   `GET /api/{module}` - List items (paginated)
    *   `GET /api/{module}/{id}` - Get single item
    *   `POST /api/{module}` - Create new item
    *   `PUT /api/{module}/{id}` - Update existing item
    *   `DELETE /api/{module}/{id}` - Delete item

## 6. For AI/LLM Assistance

When assisting with this codebase, pay attention to:

*   **The active module:** Code generation or modifications should typically occur within the context of a specific module.
*   **The architectural layers:** Place code in the correct layer (Controller, Service, Repository, DTO, etc.).
*   **Multi-tenancy:** Ensure that data access and business logic correctly consider tenant isolation. Use `tenancy()->...` helpers or be mindful of global scopes.
*   **Shared Module:** Check the `Shared` module for existing utilities or base classes before implementing new common functionality.
*   **Custom Packages:** Be aware of `m-tech-stack/base-package` and `m-tech-stack/rabbit-mq` as they might provide core functionalities.
*   **`helpers.php`:** This file may contain relevant global functions.
*   **Following Patterns:** Adhere to the established patterns (DTOs, Repositories, Services, etc.) when adding new features.
