# Contsrix_Core_Api — Architecture

## 1. Overview

| Property | Value |
|---|---|
| **Company** | Vision |
| **Type** | Multi-tenant enterprise SaaS API |
| **Stack** | Laravel 11 (PHP 8.2), RoadRunner Octane, RabbitMQ |
| **Auth** | JWT (tymon/jwt-auth) + OTP fallback |
| **Permissions** | Spatie RBAC (global) + custom Project-level permissions |
| **Tenancy** | stancl/tenancy 3.x (tenant = Company model) |
| **Media** | Spatie MediaLibrary 11.x |
| **Auditing** | owen-it/laravel-auditing |
| **Queue** | RabbitMQ via vladimir-yuldashev/laravel-queue-rabbitmq + m-tech-stack/rabbit-mq |
| **Realtime** | Laravel Reverb (WebSockets) |
| **Firebase** | kreait/laravel-firebase (FCM push notifications) |

## 2. Module Organization

### Hierarchy (3 levels deep in places)

```
modules/
├── <TopLevelModule>/              # 32 top-level domains
│   ├── module.json
│   ├── Controllers/
│   ├── Services/
│   ├── Repositories/
│   ├── Models/
│   ├── DTO/
│   ├── Requests/
│   ├── Presenters/
│   ├── Resources/routes/api.php
│   ├── Providers/
│   └── Database/Migrations/
│
├── Company/                       # Nested: parent module with 5 sub-modules
│   ├── CompanyCore/               # Tenant model, domain management
│   ├── CompanyField/
│   ├── CompanyRegistrationType/
│   ├── CompanyType/
│   └── ManagementHierarchy/
│
├── Ecommerce/                     # Nested: parent module with 29 sub-modules
│   ├── EcoProduct/
│   ├── EcoCategory/
│   ├── EcoBrand/
│   ├── EcoShop/
│   ├── Order/
│   ├── Coupon/
│   ├── EcoPayment/
│   └── ... (29 total)
│
├── Shared/                        # Nested: parent module with 27 sub-modules
│   ├── Media/
│   ├── Bank/
│   ├── Currency/
│   ├── Language/
│   ├── Payment/
│   └── ... (27 total)
│
├── UserInfo/                      # Nested: 17 sub-modules
│   ├── UserProfessionalData/
│   ├── EmploymentContract/
│   ├── UserSalary/
│   └── ...
│
├── WebsiteCMS/                    # Nested: 18 sub-modules
│   ├── WebsiteHomePage/
│   ├── WebsiteNews/
│   ├── WebsiteProject/
│   └── ...
│
├── Leave/                         # Nested: 3 sub-modules
│   ├── LeavePolicy/
│   ├── LeaveType/
│   └── PublicHoliday/
│
├── Subscription/                  # Nested: 2 sub-modules
│   ├── CompanyAccessProgram/
│   └── Package/
│
├── Project/                       # Nested: 4 sub-modules
│   ├── ProjectManagement/
│   ├── ProjectType/
│   ├── TermServices/
│   └── TermSetting/
│
└── ArchiveLibrary/                # Nested: 2 sub-modules
    ├── File/
    └── Folder/
```

### Total module.json count: **129** service providers registered.

### Full Top-Level Module List (32)

| Module | Alias | Type | Description |
|---|---|---|---|
| ActivityLog | activitylog | Flat | Activity tracking |
| AdminRequest | adminrequest | Flat | Admin request handling |
| ArchiveLibrary | archivelibrary | Nested (2) | Document/file archives |
| Attendance | attendance | Flat | Employee attendance, clock-in/out, overtime |
| Audit | audit | Flat | Model audit integration |
| Auth | auth | Flat | Login, OTP, password reset, JWT |
| ClientRequest | clientrequest | Flat | Client-submitted requests |
| Company | company | Nested (5) | Tenant/company CRUD, hierarchies |
| CompanyUser | companyuser | Flat | Company-user associations |
| Country | country | Flat | Country/state/city data |
| DocumentType | documenttype | Flat | Document type definitions |
| Ecommerce | ecommerce | Nested (29) | Full ecommerce platform |
| JobTitle | jobtitle | Flat | Job title definitions |
| Leave | leave | Nested (3) | Leave policies, types, holidays |
| MedicalInsurance | medicalinsurance | Flat | Medical insurance management |
| NotificationSettings | notificationsettings | Flat | Push notification config |
| PageBuilder | page-builder | Flat | Dynamic form/table builder from DB schema |
| ProcedureSetting | proceduresetting | Flat | Procedure settings |
| ProdactInfo | *no module.json* | Flat | Product info (exports, reports) |
| Program | program | Flat | Program/project management |
| Project | project | Nested (4) | Project management, types, term services |
| Reports | reports | Flat | Wizard-driven HR/attendance reports |
| RoleAndPermission | roleandpermission | Flat | Spatie RBAC integration |
| Setting | setting | Flat | App/tenant settings |
| Shared | shared | Nested (27) | Shared utilities, enums, common models |
| SubEntity | subentity | Flat | Sub-entity management |
| Subscription | subscription | Nested (2) | Packages, company access programs |
| TermServiceSetting | termservicesetting | Flat | Term service settings |
| Unit | unit | Flat | Organizational units |
| User | user | Flat | Core user CRUD, roles, permissions |
| UserInfo | userinfo | Nested (17) | Extended user data (salary, contracts, etc.) |
| WebsiteCMS | websitecms | Nested (18) | CMS: pages, news, themes, services |

## 3. Request Workflow (Standard Module Pattern)

```
HTTP Request
  → Route (modules/XXX/Resources/routes/api.php)
    → Controller (modules/XXX/Controllers/)
      → FormRequest validates (modules/XXX/Requests/)
        → Controller calls Service/Handler (modules/XXX/Services/ or Handlers/)
          → Service calls Repository (modules/XXX/Repositories/)
            → Repository interacts with Model (modules/XXX/Models/)
          → Service returns Model/Collection to Controller
        → Controller formats via Presenter (modules/XXX/Presenters/)
      → JSON Response
```

### Key Pattern: Command/Handler for Complex Operations

Some modules use a Command→Handler pattern for complex multi-step operations:
- `modules/XXX/Commands/` — Command DTOs
- `modules/XXX/Handlers/` — Handler classes that execute commands

Example from User module: `AssignRoleForUserHandler`, `DeleteUserHandler`, `UpdateUserHandler`

### When to use Handler vs Service:
- **Service** (e.g., `UserCRUDService`): Standard CRUD operations
- **Handler** (e.g., `DeleteUserHandler`): Complex multi-step operations with side effects

## 4. Authentication

### JWT (Primary)
- Guard: `api` driver = `jwt`
- Provider: `users` → `Modules\User\Models\User`
- User implements `JWTSubject`
- Middleware: `auth:api`

### OTP (Fallback)
- Package: `ichtrojan/laravel-otp`
- Endpoints: `/login-step`, `/login-otp`, `/resend-otp`, `/validate-reset-password-otp`
- Multi-step login flow: step1 (email/phone) → OTP → step2 (password/questions)

### Login Ways
- System supports multiple login methods per user (configurable via `LoginWay` model in Setting module)

### Tenant-Scoped Auth Routes
```
POST /login                          # InitializeTenancyByRequestData
POST /login-as-admin                 # Admin impersonation
POST /login-step                     # Step-based login
POST /login-otp                      # OTP verification
POST /forget-password                # Password reset request
POST /reset-password                 # Password reset (after OTP)
POST /logout                         # auth:api required
```

## 5. Multi-Tenancy (stancl/tenancy)

### Configuration Highlights
- **Tenant model:** `Modules\Company\CompanyCore\Models\Company`
- **Domain model:** `Modules\Company\CompanyCore\Models\Domain`
- **ID generator:** UUID-based
- **Central domains:** localhost, 127.0.0.1, core-be-stage.constrix-nv.com
- **Bootstrappers:** ALL COMMENTED OUT — means manual tenant scoping

### Tenant Identification
- `InitializeTenancyByRequestData` middleware used on most API routes — identifies tenant from request data (header/body)
- `InitializeTenancyByDomain` used on web routes only

### Database
- Central connection: default `DB_CONNECTION`
- Tenant DB prefix: `tenant_`
- Manual scoping likely done via `tenancy()->initialize()` calls or custom repository-level scoping

## 6. Permissions & RBAC

### Spatie Laravel-Permission
- Global roles & permissions via `spatie/laravel-permission`
- User model uses `HasRoles` trait
- Custom `Permission` enum per module (e.g., `Permission::USER_LIST()`)

### Route-Level Permission Checks
- Custom `->permission()` route macro used extensively
- Example: `Route::get('/users', ...)->permission(Permission::USER_LIST())`

### Admin Roles
- Super Admin
- Admin
- Tenant-scoped roles assigned per company

## 7. Key Infrastructure

### Queue / RabbitMQ
- `vladimir-yuldashev/laravel-queue-rabbitmq` for Laravel queue driver
- `m-tech-stack/rabbit-mq` custom package for advanced RabbitMQ features
- `php-amqplib/php-amqplib` for direct AMQP operations

### Server Runtime
- RoadRunner Octane (`laravel/octane` + `spiral/roadrunner-http`)
- High-performance PHP application server

### Realtime
- Laravel Reverb for WebSocket broadcasting
- Firebase Cloud Messaging (FCM) for push notifications

### File Storage
- Spatie MediaLibrary 11.x for media attachments
- S3-compatible storage via Flysystem

### Auditing
- `owen-it/laravel-auditing` on models implementing `Auditable`
- Separate `Audit` module wraps auditing functionality

## 8. Core App Infrastructure

### app/ Structure
```
app/
├── Casts/UuidCast.php
├── Channels/SmsChannel.php
├── Console/
├── EnumToArray.php
├── Events/
├── Exceptions/CustomException.php
├── Exports/
├── Http/Controllers/Controller.php    # Base controller
├── Listeners/
├── Mail/
├── Notifications/
├── Providers/
│   ├── AppServiceProvider.php
│   ├── TelescopeServiceProvider.php
│   └── TenancyServiceProvider.php
├── Rules/
├── Scopes/
├── Traits/
│   ├── CalculateTreeManagementHierarchy.php
│   ├── CustomBelongsToTenant.php
│   ├── ForcedBelongsToTenant.php
│   ├── HasExport.php
│   ├── HasExportController.php
│   ├── HasExportService.php
│   └── Shareable.php
└── helpers.php                          # ~3000 lines of global helpers
```

### Custom Package: m-tech-stack/base-package
- `BasePackage\Shared\Module\ModuleServiceProvider` — base provider for all modules
- `BasePackage\Shared\Traits\UuidTrait` — UUID PK support
- `BasePackage\Shared\Traits\BaseFilterable` — query filtering
- `BasePackage\Shared\Traits\HasTranslations` — multi-language support
- `BasePackage\Shared\Repositories\BaseRepository` — base repository with CRUD
- `BasePackage\Shared\Presenters\Json` — base JSON presenter

## 9. Database

- **465 total migrations** (43 root + 422 across modules)
- Root migrations: cache, jobs, countries, states, cities, domains, audits, telescope
- Module migrations: each module manages its own tables
- UUID primary keys throughout (via `UuidTrait`)
- Soft deletes used on key models

## 10. Module Discovery

The system auto-discovers modules. ModuleServiceProvider.php handles:
- Registering translations
- Registering migrations
- Registering commands
- Registering schedules
- Loading routes from `Resources/routes/api.php`
- Registering event listeners
- Registering model observers

Modules are NOT listed in `config/app.php` — they are dynamically discovered via `module.json` files.
