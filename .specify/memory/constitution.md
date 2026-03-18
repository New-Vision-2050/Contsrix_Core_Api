<!--
Sync Impact Report
- Version change: 1.0.0 → 1.0.1
- Bump rationale: PATCH — date correction (2025→2026), no content changes
- Modified principles: None
- Added sections: None
- Removed sections: None
- Templates validated:
  - ✅ .specify/templates/constitution-template.md (source template)
  - ✅ .specify/templates/plan-template.md (Constitution Check section defers to runtime)
  - ✅ .specify/templates/spec-template.md (generic, no constitution refs)
  - ✅ .specify/templates/tasks-template.md (generic, no constitution refs)
  - ✅ .specify/templates/checklist-template.md (generic, no constitution refs)
  - ✅ .specify/templates/agent-file-template.md (dynamic, no constitution refs)
  - ✅ README.md (consistent with constitution principles)
- Follow-up TODOs: None
-->

# Contsrix_Core_Api Constitution

## Project Overview

**Contsrix_Core_Api** is a multi-tenant enterprise management platform built with **Laravel 11.31**. It provides company management, HR, attendance, leave, e-commerce, archive library, project management, subscription, and website CMS capabilities. The platform uses a modular architecture with 30+ modules, serves multiple tenants via `stancl/tenancy`, and runs on **Laravel Octane with RoadRunner** behind Nginx in Docker containers.

---

## Core Principles

### I. Modular Architecture (NON-NEGOTIABLE)

Every feature MUST be developed as an independent module inside the `modules/` directory. Modules follow PSR-4 autoloading under the `Modules\` namespace. Each module MUST be self-contained with its own Controllers, Services, Repositories, DTOs, Models, Presenters, Requests, Providers, Routes, Migrations, Seeders, Factories, and Tests.

**Module generation:**
```bash
php artisan module:make ModuleName
# Or with export functionality:
php artisan make:module-with-export ModuleName --fields="name:string,price:decimal"
```

**Required module structure:**
```
modules/ModuleName/
├── Commands/                    # Update commands for complex operations
├── Controllers/                 # HTTP request handlers (thin, no business logic)
├── Database/
│   ├── factories/               # Model factories for testing
│   ├── Migrations/              # Database schema migrations
│   └── Seeders/                 # Data seeders
├── DTO/                         # Data Transfer Objects
├── Exports/                     # Excel/CSV export classes
├── Filters/                     # Query filter classes
├── Handlers/                    # Command handlers (Update/Delete)
├── Models/                      # Eloquent models
├── Presenters/                  # Data formatters for API responses
├── Providers/
│   └── ModuleNameServiceProvider.php
├── Repositories/                # Data access layer
├── Requests/                    # Form Request validation classes
├── Resources/
│   └── routes/
│       └── api.php              # Module API routes
├── Services/                    # Business logic layer
├── Tests/
│   ├── Unit/                    # Unit tests
│   └── Feature/                 # Feature/integration tests
└── module.json                  # Module metadata & provider registration
```

**module.json format:**
```json
{
    "name": "ModuleName",
    "alias": "module-name",
    "description": "",
    "providers": [
        "Modules\\ModuleName\\Providers\\ModuleNameServiceProvider"
    ]
}
```

**Sub-module support:** Modules MAY contain sub-modules (e.g., `Company/CompanyCore`, `Ecommerce/Banner`, `Leave/LeaveType`). Sub-modules are scanned from `modules/*/*` via `config/modules.php` scan paths. Each sub-module MUST have its own `module.json` and ServiceProvider.

**Current active modules (30+):**
- **Core:** Auth, User, CompanyUser, Company (CompanyCore, CompanyField, CompanyType, CompanyRegistrationType, ManagementHierarchy)
- **HR:** Attendance, Leave (LeavePolicy, LeaveType, PublicHoliday), UserInfo (multiple sub-modules)
- **Business:** Subscription, SubEntity, Project (ProjectType, TermServices), ClientRequest, AdminRequest
- **E-commerce:** Ecommerce (Banner, Coupon, Dashboard, EcoCategory, EcoProduct, Order, Payment, etc. — 20+ sub-modules)
- **Content:** WebsiteCMS (multiple sub-modules), PageBuilder
- **System:** Setting, RoleAndPermission, Country, JobTitle, DocumentType, Audit, ActivityLog, ArchiveLibrary, Shared, Unit, Program, NotificationSettings

### II. Clean Architecture Layers (NON-NEGOTIABLE)

Every API endpoint MUST follow this layered architecture with strict separation of concerns:

**Controller → Service → Repository → Model**

- **Controllers:** Handle ONLY HTTP concerns and presentation logic. MUST use dependency injection. MUST accept Form Requests. MUST use DTOs from Form Requests (never raw validated arrays). MUST use `Json` presenter for responses. MUST NOT contain try-catch blocks. MUST NOT contain business logic.
```php
public function methodName(CustomFormRequest $request): JsonResponse
{
    $dto = $request->createDTO();
    $result = $this->service->performAction($dto);
    return $this->presenter->present($result);
}
```

- **Services:** Encapsulate ALL business logic. MUST accept DTOs/Commands as parameters (never raw arrays). MUST throw `CustomException` for business rule violations. MUST use repositories for data persistence. Return models, collections, or simple data types.

- **Repositories:** Handle ALL data persistence and querying. Provide CRUD operations, filtering, sorting, pagination. Use Eloquent models and Query Builder. MUST NOT contain business logic.

- **DTOs:** Use constructor property promotion with `public readonly` properties. Include `toArray()` method. Provide getter methods.
```php
class ExampleDTO
{
    public function __construct(
        public readonly string $property1,
        public readonly ?string $property2 = null,
    ) {}
    public function toArray(): array { /* ... */ }
}
```

- **Form Requests:** Include comprehensive validation rules and custom messages. Implement `prepareForValidation()` for data prep. MUST provide DTO creation methods (e.g., `createExampleDTO()`). Handle authorization in `authorize()`.

- **Presenters:** Extend `BasePackage\Shared\Presenters\AbstractPresenter`. Format model data into arrays. Provide different view methods (e.g., `getCalendarData()`, `getReportData()`). MUST NOT contain business logic.

- **Handlers:** Used for complex update/delete operations following Command/Handler pattern.

### III. JSON Response Standard (NON-NEGOTIABLE)

ALL API responses MUST use `BasePackage\Shared\Presenters\Json` class:
- `Json::item($data, message: "message")` — single items
- `Json::items($data, message: "message")` — collections
- `Json::success("message")` — success without data

**NEVER** use Laravel's default `response()->json()` in module code.

### IV. Exception Handling (NON-NEGOTIABLE)

- Use `App\Exceptions\CustomException` (extends Exception) with `statusCode` property
- **NEVER** use try-catch blocks in controllers — let exceptions propagate to global handler (`App\Exceptions\Handler`)
- Create module-specific exception classes when appropriate (e.g., `AttendanceException`)
- Global handler maps exceptions: ValidationException→422, AuthenticationException→401, AuthorizationException→403, NotFoundHttpException→404, CustomException→dynamic, default→500

### V. Multi-Tenancy (NON-NEGOTIABLE)

- Tenant model: `Modules\Company\CompanyCore\Models\Company`
- Tenant identification: via `X-Tenant` header (set by `DomainToTenantMiddleware` from `X-Domain` header)
- Tenant column: `company_id` (configured via `BelongsToTenant::$tenantIdColumn = 'company_id'`)
- Use `tenant('id')` for company_id when applicable
- ALL models MUST be tenant-aware using `stancl/tenancy`
- Ensure data isolation between tenants
- Central domains: localhost, 127.0.0.1, APP_URL, core-be-stage.constrix-nv.com

**Middleware chain for tenancy:**
1. `DomainToTenantMiddleware` (prepended) — converts `X-Domain` header to `X-Tenant`
2. `TenantCompatibilityMiddleware` — validates user's company_id matches tenant
3. `InitializeTenancyByRequestData` — initializes tenancy from `X-Tenant` header
4. `TenancePermision` — sets Spatie permission team to user's company_id

### VI. Testing Discipline (NON-NEGOTIABLE)

Every new module or API endpoint MUST include unit tests. Tests are organized in:
- `tests/Unit/` and `tests/Feature/` — global tests
- `modules/*/Tests/Unit/` and `modules/*/Tests/Feature/` — module-level tests
- `modules/*/*/Tests/Unit/` and `modules/*/*/Tests/Feature/` — sub-module tests

**PHPUnit configuration** (`phpunit.xml`) scans all three patterns automatically.

**Testing requirements:**
- Write unit tests for services and repositories
- Create feature/integration tests for complete API workflows
- Test DTO creation and validation
- Test exception handling scenarios
- Test multi-tenant data isolation
- Test permission and authorization logic
- Use factories for test data generation
- Test Octane state isolation (no state leakage between requests)

**Run tests:**
```bash
php artisan test
# Or specific suite:
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
# Or specific module:
php artisan test --filter=ModuleName
```

### VII. Postman Collection Export (NON-NEGOTIABLE)

Every new API endpoint MUST have an accompanying Postman collection JSON file exported to the project root. The collection MUST follow the existing Postman v2.1 schema format.

**Required Postman collection structure:**
```json
{
    "info": {
        "_postman_id": "kebab-case-module-api",
        "name": "Module Name API",
        "description": "API collection for ...",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "item": [
        {
            "name": "Endpoint Name",
            "event": [
                {
                    "listen": "test",
                    "script": {
                        "exec": ["pm.test(\"Status code is 200\", function () { ... });"],
                        "type": "text/javascript"
                    }
                }
            ],
            "request": {
                "method": "POST|GET|PUT|DELETE",
                "header": [
                    { "key": "Accept", "value": "application/json" },
                    { "key": "Content-Type", "value": "application/json" },
                    { "key": "Authorization", "value": "Bearer {{access_token}}" },
                    { "key": "company-id", "value": "{{company_id}}" }
                ],
                "body": { "mode": "raw", "raw": "{...}" },
                "url": {
                    "raw": "{{base_url}}/api/v1/...",
                    "host": ["{{base_url}}"],
                    "path": ["api", "v1", "..."]
                }
            },
            "response": [
                { "name": "Success Response", "code": 200, "body": "..." },
                { "name": "Validation Error Response", "code": 422, "body": "..." }
            ]
        }
    ],
    "variable": [
        { "key": "base_url", "value": "https://your-api-domain.com" },
        { "key": "access_token", "value": "your-bearer-token-here" },
        { "key": "company_id", "value": "your-company-uuid-here" }
    ]
}
```

**Naming convention:** `ModuleName_API.postman_collection.json` (placed in project root).

**Each request MUST include:**
- All required headers (Accept, Content-Type, Authorization, company-id/X-Tenant)
- Example request body with realistic test data
- At least one success and one error response example
- Postman test scripts for status code validation

### VIII. Security & Permissions

- **Authentication:** JWT-based via `tymon/jwt-auth` v2.1, guard name: `api`
- **Authorization:** `spatie/laravel-permission` v6.10 with team (company) support
- **Permission middleware aliases:**
  - `permission` → `App\Http\Middleware\PermissionMiddleware` (extends Spatie, adds status check + subscription limit check)
  - `role` → `App\Http\Middleware\RoleMiddleware` (extends Spatie, adds status check)
  - `role_or_permission` → `App\Http\Middleware\RoleOrPermissionMiddleware`
- **Route-level permissions:** Use `->permission(Permission::PERMISSION_NAME())` macro or `->middleware('permission:permission-name')`
- **Super admin bypass:** Users with `super-admin` role or `admin@constrix-nv.com` email bypass permission checks
- Sanitize all user inputs
- Use HTTPS for all communications (Traefik handles TLS termination)

### IX. Localization

- `Localization` middleware is appended globally
- Reads `Lang` header from request (fallback to session, then `config('app.locale')`)
- Supported locales defined in `config('app.available_locales')` (ar, en)
- All user-facing messages MUST use Laravel translation files (`lang/` directory)

### X. Simplicity & Code Quality

- Follow PSR-12 coding standards (enforced via `laravel/pint`)
- Use meaningful variable and method names
- Include PHPDoc comments for public methods
- Use type hints for all parameters and return types
- YAGNI — do not over-engineer; start simple
- Use `declare(strict_types=1)` in all new files

---

## Technology Stack

### Core Dependencies
| Package | Version | Purpose |
|---------|---------|---------|
| `laravel/framework` | ^11.31 | Core framework |
| `laravel/octane` | ^2.5 | High-performance server (RoadRunner) |
| `stancl/tenancy` | ^3.9 | Multi-tenancy |
| `tymon/jwt-auth` | ^2.1 | JWT authentication |
| `spatie/laravel-permission` | ^6.10 | Role & permission management |
| `spatie/laravel-medialibrary` | ^11.12 | Media/file management |
| `owen-it/laravel-auditing` | ^14.0 | Model auditing |
| `maatwebsite/excel` | ^3.1 | Excel/CSV export |
| `kreait/laravel-firebase` | ^5.8 | Push notifications |
| `sentry/sentry-laravel` | ^4.13 | Error monitoring |
| `m-tech-stack/base-package` | ^1.0 | Custom base classes (Presenters, ModuleGenerator, etc.) |
| `m-tech-stack/rabbit-mq` | ^1.0 | RabbitMQ integration |
| `intervention/image` | ^3.11 | Image processing |
| `league/flysystem-aws-s3-v3` | ^3.29 | S3/DigitalOcean Spaces storage |
| `ranium/laravel-seedonce` | ^1.6 | Idempotent seeders |

### Dev Dependencies
| Package | Version | Purpose |
|---------|---------|---------|
| `phpunit/phpunit` | ^11.0.1 | Testing framework |
| `laravel/pint` | ^1.13 | Code style (PSR-12) |
| `mockery/mockery` | ^1.6 | Mocking in tests |
| `fakerphp/faker` | ^1.23 | Test data generation |
| `laravel/telescope` | ^5.7 | Debug dashboard (don't-discover in production) |

---

## Database & Migration Standards

### Central Database Migrations
Located in `database/migrations/`. These run on the central (shared) database and contain:
- Cache, jobs, and queue tables
- Countries, states, cities reference tables
- Domains table for tenancy
- Audits table
- Telescope entries
- Permission key migrations
- Package assignment migrations

### Module Migrations
Each module contains its own migrations in `modules/ModuleName/Database/Migrations/`. These are handled by the module system and run automatically.

### Tenant Migrations
Located in `database/migrations/tenant/`. These run per-tenant via `php artisan tenants:migrate`.

**Migration requirements:**
- Use descriptive table and column names
- Include proper indexes for performance
- Set up foreign key constraints with cascade options
- Use appropriate data types and lengths
- Include comments for complex fields
- Consider multi-tenancy requirements (`company_id` column where needed)
- Use UUID primary keys (project standard)

---

## Seeder Architecture

### Central Seeders (`database/seeders/DatabaseSeeder.php`)
Uses `SeedOnce` trait for idempotent execution. Seeds reference/lookup data:
- Currencies, timezones, languages, countries
- Admin user, company modules
- Roles and permissions
- Academic qualifications, banks, universities
- Job types, salary types, periods
- Default settings, identifiers, login ways
- Public holidays, payment methods
- Document types, notification settings

### Tenant Seeders (`database/seeders/TenantDatabaseSeeder.php`)
Runs per-company on tenant creation via `TenancyServiceProvider` events:
- General admin user for tenant
- Company package assignment
- Archive library storage/folder limits
- Default settings, login ways, identifiers
- Leave policies, document types
- Website CMS defaults (theme, contact, home page, about us, terms)
- Project types and schemas

### Module Seeders
Each module MAY have seeders in `modules/ModuleName/Database/Seeders/`. These MUST be registered in either `DatabaseSeeder` (central) or `TenantDatabaseSeeder` (per-tenant) as appropriate.

**Run seeders:**
```bash
php artisan db:seed                    # Central seeders
php artisan db:seed --force            # Force in production
php artisan tenant:seed --force        # Tenant seeders for all companies
```

---

## Middleware Stack

### Global Middleware (applied to all requests, in order)
1. `DomainToTenantMiddleware` (prepended) — Reads `X-Domain` header, looks up domain in DB, sets `X-Tenant` header with `company_id`
2. `Localization` (appended) — Sets app locale from `Lang` header
3. `TenancePermision` (appended) — Sets Spatie permission team ID to user's `company_id`
4. `TenantCompatibilityMiddleware` (appended) — Validates authenticated user's `company_id` matches `X-Tenant`

### Route Middleware Aliases
| Alias | Class | Purpose |
|-------|-------|---------|
| `lang` | `Localization` | Set locale from header |
| `role` | `RoleMiddleware` | Check user role (with status check) |
| `permission` | `PermissionMiddleware` | Check user permission (with status + subscription limit check) |
| `role_or_permission` | `RoleOrPermissionMiddleware` | Check role OR permission |
| `domain.tenant` | `DomainToTenantMiddleware` | Convert X-Domain to X-Tenant |
| `tenant.compatibility` | `TenantCompatibilityMiddleware` | Validate tenant match |

### Tenancy Middleware (highest priority, from TenancyServiceProvider)
- `PreventAccessFromCentralDomains`
- `DomainToTenantMiddleware`
- `TenantCompatibilityMiddleware`
- `InitializeTenancyByDomain`
- `InitializeTenancyBySubdomain`
- `InitializeTenancyByDomainOrSubdomain`
- `InitializeTenancyByPath`
- `InitializeTenancyByRequestData`

### Route-Level Authentication
Most module routes use: `middleware(['auth:api', InitializeTenancyByRequestData::class])`

Auth module routes use: `middleware(['throttle:35,1', InitializeTenancyByRequestData::class])` (no auth required for login/register)

---

## API Route Standards

### Route File Location
Each module defines routes in `modules/ModuleName/Resources/routes/api.php`. Routes are registered via the module's ServiceProvider.

### Route Conventions
- API prefix: `/api/v1/` (managed by module RouteServiceProvider)
- Use resource routes where appropriate (`Route::apiResource`)
- Apply proper middleware (auth, permissions, tenant)
- Group related routes logically with `Route::prefix()`
- Use descriptive route names
- Use permission macros: `->permission(Permission::PERMISSION_NAME())`

### Standard Headers for All API Requests
| Header | Value | Required |
|--------|-------|----------|
| `Accept` | `application/json` | Yes |
| `Content-Type` | `application/json` | Yes (for POST/PUT) |
| `Authorization` | `Bearer {jwt_token}` | Yes (except auth routes) |
| `X-Tenant` | `{company_uuid}` | Yes (for tenant-scoped routes) |
| `X-Domain` | `{domain_name}` | Alternative to X-Tenant |
| `Lang` | `en` or `ar` | Optional (defaults to app locale) |

---

## DevOps & Deployment

### Infrastructure Stack
- **Container Runtime:** Docker with custom PHP 8.2 image (`mabou7agar/custom-nginx-php8-2`)
- **Application Server:** Laravel Octane with RoadRunner (4 workers, max 500 requests)
- **Reverse Proxy (in-container):** Nginx → proxies to Octane on port 8000
- **Edge Proxy:** Traefik (external) with automatic Let's Encrypt TLS
- **Queue Worker:** `php artisan queue:work database` (1 process, 3 tries, 512MB memory)
- **Scheduler:** `php artisan schedule:run` via bash loop (every 60s)
- **Process Manager:** Supervisord manages all processes (octane, nginx, worker, scheduler)
- **Object Storage:** DigitalOcean Spaces (S3-compatible)
- **Database:** MySQL
- **Error Monitoring:** Sentry
- **Push Notifications:** Firebase

### Docker Configuration
**Dockerfile** (`devops/Dockerfile`):
- Base: `mabou7agar/custom-nginx-php8-2`
- Installs zip extension, downloads RoadRunner binary
- Copies Nginx and Supervisord configs
- Sets PHP limits: upload_max_filesize=500M, memory_limit=512M, max_execution_time=300
- Health check: `curl -f http://localhost/health`

**docker-compose.yml** (`devops/docker-compose.yml`):
- Single `backend` service with Traefik labels for automatic routing
- Domain pattern: `core-be-{DEPLOYMENT_ID}.constrix-nv.com`
- Connected to external `traefik_network`
- Replicas configurable (default 1)

### Nginx Configuration
- Proxies all non-static requests to Octane at `127.0.0.1:8000`
- Serves static files directly (images, CSS, JS, fonts) with 1-year cache
- Client max body size: 500MB (uploads), 1G (nginx global)
- Proxy timeouts: 300s

### Supervisord Processes
| Process | Command | Workers |
|---------|---------|---------|
| `octane` | `php artisan octane:start --server=roadrunner --host=127.0.0.1 --port=8000 --workers=4 --max-requests=500` | 1 |
| `nginx` | `nginx -g "daemon off;"` | 1 |
| `laravel-worker` | `php artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --memory=512 --timeout=60` | 1 |
| `laravel-scheduler` | `schedule:run` loop every 60s | 1 |

### Entrypoint Script (`devops/entrypoint.sh`)
On container start, the entrypoint:
1. Validates `.env` file exists
2. Creates storage directories with proper permissions
3. Tests database connection
4. Runs `composer dump-autoload`
5. Creates storage link
6. Runs `php artisan migrate --force`
7. Runs `php artisan db:seed --force` (central seeders)
8. Runs `php artisan tenant:seed --force` (tenant seeders)
9. Clears and caches config, routes, views, events
10. Installs Octane with RoadRunner
11. Fixes permissions
12. Starts Supervisord

### GitHub Actions CI/CD

**Pipeline:** `.github/workflows/ci_cd.yml`

**Triggers:** Push to `production`, `dev`, or `stage` branches.

**Deployment flow:**
1. Checkout code
2. Set `DEPLOYMENT_ID` from branch name (e.g., `dev`, `stage`, `production`, or `pr{N}`)
3. Clean old deployment directory on server via SSH
4. SCP transfer repo files to server at `/home/deployer/laravel/deployments/{DEPLOYMENT_ID}/code/`
5. Set environment variables based on branch (production=production, dev=development, stage=stage)
6. SSH into server, export all env vars, run `devops/deploy.sh`
7. `deploy.sh` creates `.env`, builds Docker image, runs `docker compose up`, cleans old containers/images
8. Inject Firebase credentials into running container
9. Cleanup deployment directory
10. Post PR comment with deployment URL (for PR deployments)

**Cleanup job:** Runs when a PR is closed without merge — stops container, removes image and deployment directory.

**AI Code Review:** `.github/workflows/core_review.yml`
- Triggers on PR open/sync/reopen for `.ts`, `.js`, `.tsx`, `.jsx`, `.php` files
- Uses `New-Vision-2050/ai-codereviewer@main` with DeepSeek model
- Focuses on code structure, security, performance (not style/formatting)

**Required GitHub Secrets:**
`DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PORT`, `DEPLOY_SSH_KEY`, `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`, `APP_KEY`, `JWT_SECRET`, `AWS_KEY`, `AWS_SECRET`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `GOOGLE_MAPS_API_KEY`, `SMS_MORA_KEY`, `SMS_MORA_USER`, `SMS_MORA_SENDER`, `OPENROUTER_API_KEY`, `FIREBASE_CREDENTIALS`, `PERSONAL_GITHUB_TOKEN`, `OPENAI_API_KEY`

### Pull Request Template
`.github/pull_request_template.md` requires:
- Summary, Related Jira Ticket
- Type of Change (Feature/Bugfix/Refactor/Hotfix/Chore)
- How to Test section
- Checklist: self-review, validations, API docs/Postman collection, tests passing, no breaking changes
- Migration/Database Changes section

### Branch Strategy
| Branch | Environment | Debug | URL Pattern |
|--------|-------------|-------|-------------|
| `production` | production | false | `core-be-production.constrix-nv.com` |
| `stage` | stage | true | `core-be-stage.constrix-nv.com` |
| `dev` | development | true | `core-be-dev.constrix-nv.com` |
| PR branches | pr | true | `core-be-pr{N}.constrix-nv.com` |

---

## Laravel Octane Considerations

Running on Octane with RoadRunner introduces specific requirements:

### State Isolation Between Requests
- `FlushTenancyState` listener runs on `RequestTerminated` event
  - Ends current tenancy
  - Resets Spatie permission cache key
  - Disconnects tenant database connections
  - Flushes presenter static caches
  - Resets ManagementHierarchy presenter state
- Flushed bindings in `config/octane.php`:
  - `Stancl\Tenancy\Tenancy`
  - `Spatie\Permission\PermissionRegistrar`
  - `UserRepository`, `CompanyUserRepository`, `CompanyRepository`
  - All Attendance constraint services (Time, Location, Device, Role, Behavioral, Security, Compliance)

### Octane-Safe Coding Rules
- **NEVER** use static properties that persist state between requests (unless explicitly flushed)
- **NEVER** store request-scoped data in singleton services
- **ALWAYS** register services that hold state in `octane.flush` config
- **ALWAYS** test with Octane to verify no state leakage
- Garbage collection threshold: 50MB
- Max execution time: 30s per request

---

## Export System

The project includes an automated export system with base classes:
- `App\Exports\BaseExport` — Foundation export class with Excel styling
- `App\Traits\HasExport` — Repository trait
- `App\Traits\HasExportService` — Service trait
- `App\Traits\HasExportController` — Controller trait

Every module with data listing SHOULD include export functionality via `GET /api/{module}/export` endpoint supporting `xlsx` and `csv` formats with filtering.

---

## Module Creation Checklist

When creating a new module or API endpoint, EVERY deliverable MUST include:

- [ ] Module structure follows the standard directory layout
- [ ] `module.json` with correct ServiceProvider registration
- [ ] ServiceProvider registered and routes loaded
- [ ] Controllers use dependency injection, Form Requests, DTOs, and Json presenter
- [ ] No try-catch blocks in controllers
- [ ] Services contain all business logic, accept DTOs
- [ ] Repositories handle data access only
- [ ] Presenters format data (extend AbstractPresenter)
- [ ] Custom exceptions created for module-specific errors
- [ ] Models include proper relationships, casts, fillable, UUID primary keys
- [ ] Migrations include indexes, foreign keys, proper types
- [ ] Seeders registered in DatabaseSeeder or TenantDatabaseSeeder as appropriate
- [ ] Multi-tenancy properly implemented (company_id, tenant scoping)
- [ ] Permission/role middleware applied to routes
- [ ] **Unit tests written for services and repositories**
- [ ] **Feature tests written for API endpoints**
- [ ] **Postman collection JSON file created and placed in project root**
- [ ] Localization strings added for user-facing messages
- [ ] Octane flush list updated if service holds state
- [ ] Code follows PSR-12 standards
- [ ] PHPDoc comments on public methods

---

## Development Workflow

### Local Development
```bash
# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate keys
php artisan key:generate
php artisan jwt:secret

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Start with Octane (recommended - matches production)
php artisan octane:start --server=roadrunner --host=127.0.0.1 --port=8000

# Or start with standard dev server
composer dev
# This runs: php artisan serve + queue:listen + npm run dev concurrently
```

### Creating a New Module
```bash
# Generate module scaffold
php artisan module:make ModuleName

# Generate module with export
php artisan make:module-with-export ModuleName --fields="name:string" --relationships="category"

# Generate migration for module
php artisan module:make-migration create_table_name_table ModuleName

# Generate seeder for module
php artisan module:make-seed SeederName ModuleName

# Generate factory for module
php artisan module:make-factory ModelName ModuleName
```

### Running Tests
```bash
# All tests
php artisan test

# Specific suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Specific module tests
php artisan test --filter=Attendance

# With coverage
php artisan test --coverage
```

### Code Quality
```bash
# Format code (PSR-12)
./vendor/bin/pint

# Run static analysis (if configured)
./vendor/bin/phpstan analyse
```

---

## Governance

This constitution is the authoritative reference for all development practices in the Contsrix_Core_Api project. It supersedes all other informal practices or ad-hoc decisions.

**Amendment Process:**
1. Propose changes via PR with description of rationale
2. Review by team lead / architect
3. Update this constitution document
4. Increment version per semantic versioning:
   - **MAJOR:** Backward-incompatible principle removals or redefinitions
   - **MINOR:** New principles/sections or materially expanded guidance
   - **PATCH:** Clarifications, typo fixes, non-semantic refinements

**Compliance:**
- All PRs MUST verify compliance with this constitution
- AI code reviewer checks structure, security, and performance
- PR template checklist enforces documentation and testing requirements
- Refer to `TASK_INITIATION_PROCESS.md` for the full task initiation workflow (PRD → Context → Architecture → Planning → Implementation → Checklist)

**Version**: 1.0.1 | **Ratified**: 2026-03-19 | **Last Amended**: 2026-03-19
