# Project Module — Deep Documentation

> **Version**: 1.1  
> **Scope**: Complete architectural, structural, behavioural, and frontend/UI reference for the `Modules\Project` namespace and all its sub-modules.

---

## Table of Contents

1. [Overview & Module Architecture](#1-overview--module-architecture)
2. [Sub-Module: ProjectManagement](#2-sub-module-projectmanagement)
   - 2.1 [Models](#21-models)
   - 2.2 [Database Schema & Migrations](#22-database-schema--migrations)
   - 2.3 [DTOs, Handlers & Commands](#23-dtos-handlers--commands)
   - 2.4 [Services](#24-services)
   - 2.5 [Repositories](#25-repositories)
   - 2.6 [Controllers & API Endpoints](#26-controllers--api-endpoints)
   - 2.7 [Observers](#27-observers)
   - 2.8 [Middleware](#28-middleware)
   - 2.9 [Presenters](#29-presenters)
   - 2.10 [Filters](#210-filters)
   - 2.11 [Mail & Notifications](#211-mail--notifications)
   - 2.12 [Events](#212-events)
   - 2.13 [Enums](#213-enums)
   - 2.14 [Commands (Artisan)](#214-commands-artisan)
   - 2.15 [Seeders](#215-seeders)
   - 2.16 [Config](#216-config)
3. [Sub-Module: ProjectType](#3-sub-module-projecttype)
   - 3.1 [Models](#31-models)
   - 3.2 [Database Schema & Migrations](#32-database-schema--migrations)
   - 3.3 [Services & Repositories](#33-services--repositories)
   - 3.4 [Controllers & API Endpoints](#34-controllers--api-endpoints)
   - 3.5 [Observers](#35-observers)
   - 3.6 [Seeders](#36-seeders)
   - 3.7 [Presenters](#37-presenters)
   - 3.8 [Maintenance & Emergency Settings (Schema 12)](#38-maintenance--emergency-settings-schema-12)
4. [Sub-Module: TermServices](#4-sub-module-termservices)
5. [Sub-Module: TermSetting](#5-sub-module-termsetting)
5a. [Sub-Module: TermServiceSetting](#5a-sub-module-termservicesetting)
6. [Shared: ResourceShare System](#6-shared-resourceshare-system)
7. [Project Config (Global Permissions)](#7-project-config-global-permissions)
8. [Cross-Module Relationships](#8-cross-module-relationships)
9. [Architecture Patterns & Conventions](#9-architecture-patterns--conventions)
10. [Installation & Setup](#10-installation--setup)
11. [Troubleshooting](#11-troubleshooting)
12. [Frontend / UI Mapping](#12-frontend--ui-mapping)

---

## 1. Overview & Module Architecture

The `Modules\Project` namespace is the core project-management domain of the Constrix API. It is composed of **five sub-modules** and one **shared infrastructure module**:

| Sub-Module | Alias | Route Prefix | Purpose |
|---|---|---|---|
| `ProjectManagement` | `projectmanagement` | `api/v1/projects` | Project CRUD, employees, roles, permissions, sharing, attachment requests, dashboard widgets |
| `ProjectType` | `projecttype` | `api/v1/project-types` | Hierarchical project type tree, schema assignment, per-type settings (data, contracts, archive, roles, sharing) |
| `TermServices` | `termservices` | `api/v1/term-services` | Lookup table of term services (global, not tenant-scoped) |
| `TermSetting` | `termsetting` | `api/v1/term-settings` | Hierarchical term settings tree linked to project types and services |
| `TermServiceSetting` | `termservicesetting` | `api/v1/term-service-settings` | Tenant-scoped grouping of term settings into named service configurations (used by ClientRequest) |
| `Shared\ResourceShare` | — | `api/v1/resource-shares` | Polymorphic resource sharing infrastructure used by `ProjectManagement` |

### Key Architectural Concepts

- **Multi-Tenancy**: Uses `stancl/tenancy` with `BelongsToTenant` trait and `InitializeTenancyByRequestData` middleware. Tenant is resolved from request data; `tenant('id')` is used throughout.
- **UUID Primary Keys**: Most `ProjectManagement` models use `UuidTrait` (UUID PKs). `ProjectType`, `TermServices`, and `TermSetting` use auto-incrementing integer PKs.
- **Hierarchical Trees**: `ProjectType` and `TermSetting` use the `nevadskiy/laravel-tree` package (`AsTree` trait) for parent-child tree structures with `path` column.
- **Shareable Trait**: `App\Traits\Shareable` adds a global scope to include both owned and accepted-shared resources, plus helper methods for sharing.
- **Project-Specific Permissions**: A dedicated RBAC system scoped to individual projects, separate from the global `RoleAndPermission` module. Uses config-driven permission names, a custom middleware, and a seeder.
- **Consistent Layering**: `Request → DTO → Service → Repository → Model → Presenter` across all sub-modules.

---

## 2. Sub-Module: ProjectManagement

### 2.1 Models

#### 2.1.1 `ProjectManagement` (table: `projects`)

**File**: `Modules\Project\ProjectManagement\Models\ProjectManagement`

**Traits**: `HasFactory`, `UuidTrait`, `BaseFilterable`, `Shareable`

**Primary Key**: UUID (`string`)

**Fillable Fields**:
```
project_type_id, sub_project_type_id, sub_sub_project_type_id,
name, manager_id, created_by_user_id, branch_id,
project_owner_type, project_owner_id, contract_id, client_id,
project_classification_id, cost_center_branch_id, management_id,
currency_id, project_value, company_id, status, serial_number
```

**Relationships**:
- `projectType()` → `belongsTo(ProjectType, 'project_type_id')` — first-level type
- `subProjectType()` → `belongsTo(ProjectType, 'sub_project_type_id')` — second-level type
- `subSubProjectType()` → `belongsTo(ProjectType, 'sub_sub_project_type_id')` — third-level type
- `manager()` → `belongsTo(User, 'manager_id')`
- `branch()` → `belongsTo(Branch, 'branch_id')`
- `projectOwner()` → `morphTo()` — polymorphic owner (company or individual)
- `client()` → `belongsTo(Client, 'client_id')`
- `costCenterBranch()` → `belongsTo(Branch, 'cost_center_branch_id')`
- `management()` → `belongsTo(ManagementHierarchy, 'management_id')`
- `currency()` → `belongsTo(Currency, 'currency_id')`
- `company()` → `belongsTo(Company, 'company_id')`
- `creator()` → `belongsTo(User, 'created_by_user_id')`
- `employees()` → `hasMany(ProjectEmployee, 'project_id')`
- `projectRoles()` → `hasMany(ProjectRole, 'project_id')`
- `shares()` → `morphMany(ResourceShare, 'shareable')` (via `Shareable` trait)
- `acceptedShares()` → `morphMany(ResourceShare, 'shareable')->where('status', 'accepted')`

**Key Methods**:
- `getRelationshipToPrimaryModel(): string` — returns `"company"` (for tenant filtering)

---

#### 2.1.2 `ProjectRole` (table: `project_roles`)

**File**: `Modules\Project\ProjectManagement\Models\ProjectRole`

**Traits**: `UuidTrait`

**Fillable Fields**:
```
project_id, name, slug, description, is_default, is_active
```

**Relationships**:
- `project()` → `belongsTo(ProjectManagement, 'project_id')->withoutGlobalScopes()`
- `permissions()` → `belongsToMany(ProjectPermission, 'project_role_permissions', 'project_role_id', 'project_permission_id')->withTimestamps()`
- `projectEmployees()` → `hasMany(ProjectEmployee, 'project_role_id')`

**Key Behaviours**:
- The `is_default` flag marks the auto-created "Project Admin" role. This role is protected from updates and deletion by the `ProjectRoleObserver`.

---

#### 2.1.3 `ProjectPermission` (table: `project_permissions`)

**File**: `Modules\Project\ProjectManagement\Models\ProjectPermission`

**Traits**: `UuidTrait`, `HasTranslations`

**Fillable Fields**:
```
name, submodule, action, title, description, is_active
```

**Translation**: The `title` field is a JSON column supporting `ar` and `en` locales via the `HasTranslations` trait.

**Relationships**:
- `roles()` → `belongsToMany(ProjectRole, 'project_role_permissions', 'project_permission_id', 'project_role_id')`

**Permission Name Format**: `project-management.project-management*{submodule}.{action}`  
Example: `project-management.project-management*employee.view`

---

#### 2.1.4 `ProjectEmployee` (table: `project_employees`)

**File**: `Modules\Project\ProjectManagement\Models\ProjectEmployee`

**Traits**: `UuidTrait`

**Fillable Fields**:
```
project_id, user_id, company_id, assigned_at, assigned_by_user_id, project_role_id
```

**Relationships**:
- `project()` → `belongsTo(ProjectManagement, 'project_id')`
- `user()` → `belongsTo(User, 'user_id')`
- `company()` → `belongsTo(Company, 'company_id')`
- `assignedBy()` → `belongsTo(User, 'assigned_by_user_id')`
- `projectRole()` → `belongsTo(ProjectRole, 'project_role_id')`

**Unique Constraint**: `['project_id', 'user_id']` — a user can be assigned to a project only once.

---

#### 2.1.5 `AttachmentRequest` (table: `attachment_requests`)

**File**: `Modules\Project\ProjectManagement\Models\AttachmentRequest`

**Traits**: `UuidTrait`

**Fillable Fields**:
```
serial_number, name, date, project_id, sender_company_id,
receiver_company_id, attachment_type_id, attachment_sub_type_id,
attachment_sub_sub_type_id, status, created_by_user_id,
responded_by_user_id, responded_at, notes
```

**Casts**: `date` → `date`, `responded_at` → `datetime`, attachment type IDs → `string`

**Relationships**:
- `project()` → `belongsTo(ProjectManagement, 'project_id')->withoutGlobalScopes()`
- `senderCompany()` → `belongsTo(Company, 'sender_company_id')->withoutGlobalScopes()`
- `receiverCompany()` → `belongsTo(Company, 'receiver_company_id')->withoutGlobalScopes()`
- `createdByUser()` → `belongsTo(User, 'created_by_user_id')->withoutGlobalScopes()`
- `respondedByUser()` → `belongsTo(User, 'responded_by_user_id')->withoutGlobalScopes()`
- `items()` → `hasMany(AttachmentRequestItem, 'attachment_request_id')`
- `history()` → `hasMany(AttachmentRequestHistory, 'attachment_request_id')->orderBy('created_at', 'asc')`

**Status Flow**: `pending` → `semi-approved` → `approved` | `declined`

**Key Methods**:
- `isPending()`, `isApproved()`, `isDeclined()`, `isSemiApproved()` — status checks
- `updateStatusBasedOnItems()` — recalculates request status from individual item statuses
- `approveAll(string $userId)` — approves all items and the request itself
- `declineAll(string $userId)` — declines all items and the request itself

---

#### 2.1.6 `AttachmentRequestItem` (table: `attachment_request_items`)

**File**: `Modules\Project\ProjectManagement\Models\AttachmentRequestItem`

**Traits**: `UuidTrait`, `InteractsWithMedia` (Spatie Media Library)

**Implements**: `HasMedia`

**Fillable Fields**:
```
attachment_request_id, file_name, file_path, file_type, file_size,
status, responded_by_user_id, responded_at, response_notes
```

**Relationships**:
- `attachmentRequest()` → `belongsTo(AttachmentRequest, 'attachment_request_id')`
- `respondedByUser()` → `belongsTo(User, 'responded_by_user_id')->withoutGlobalScopes()`

**Status Flow**: `pending` → `approved` | `declined` | `update_requested`

**Key Methods**:
- `approve(string $userId, ?string $notes)` — sets status to `approved`, updates parent request status
- `decline(string $userId, ?string $notes)` — sets status to `declined`, updates parent request status
- `requestUpdate(string $userId, ?string $notes)` — sets status to `update_requested`, updates parent request status

**Media Collection**: `attachments` — stores uploaded files via Spatie Media Library.

---

#### 2.1.7 `AttachmentRequestHistory` (table: `attachment_request_history`)

**File**: `Modules\Project\ProjectManagement\Models\AttachmentRequestHistory`

**Traits**: `HasUuids`

**Note**: Uses Laravel's native `HasUuids` (not the custom `UuidTrait`).

**Fillable Fields**:
```
attachment_request_id, attachment_request_item_id, action,
description, user_id, metadata, created_at
```

**Casts**: `metadata` → `array`, `created_at` → `datetime`

**Timestamps**: `$timestamps = false` (only `created_at` is used, manually set)

**Static Method**:
- `log(string $requestId, string $action, string $description, ?string $userId, ?string $itemId, ?array $metadata): self` — creates a history entry

**Tracked Actions**: `request_created`, `attachment_approved`, `attachment_declined`, `attachment_update_requested`, `request_approved`, `request_declined`, `media_replaced`

---

#### 2.1.8 `ProjectNotification` (table: `project_notifications`)

**File**: `Modules\Project\ProjectManagement\Models\ProjectNotification`

**Traits**: `UuidTrait`, `BaseFilterable`, `CustomBelongsToTenant`, `InteractsWithMedia` (Spatie), `SoftDeletes`

**Implements**: `HasMedia`

**Primary Key**: UUID (`string`)

**Fillable Fields**:
```
company_id, project_id, employee_task_request_id, notification_number,
notification_type, severity, work_type, magdy_number, work_description,
contractor_name, contractor_number, contractor_technical_number,
contractor_category, contractor_notes, contractor_mobile,
task_latitude, task_longitude, location_radius, location_link, repair_point,
assigned_user_id, selected_distance_meters, status, created_by_user_id,
approved_by, approved_at, rejected_by, rejected_at, rejection_reason,
task_date, duration_hours, notes
```

**Casts**: `task_latitude` → `decimal:7`, `task_longitude` → `decimal:7`, `location_radius` → `integer`, `selected_distance_meters` → `integer`, `duration_hours` → `decimal:2`, `approved_at`/`rejected_at` → `datetime`, `task_date` → `date:Y-m-d`

**Relationships**:
- `project()` → `belongsTo(ProjectManagement, 'project_id')->withoutGlobalScopes()`
- `employeeTask()` → `belongsTo(EmployeeTaskRequest, 'employee_task_request_id')->withoutGlobalScopes()`
- `assignedUser()` → `belongsTo(User, 'assigned_user_id')->withoutGlobalScopes()`
- `creator()` → `belongsTo(User, 'created_by_user_id')->withoutGlobalScopes()`
- `approver()` → `belongsTo(User, 'approved_by')->withoutGlobalScopes()`
- `rejecter()` → `belongsTo(User, 'rejected_by')->withoutGlobalScopes()`

**Media Collection**: `attachments` — stores uploaded files via Spatie Media Library.

**Key Behaviour**: When a `ProjectNotification` is created via `ProjectNotificationService::create()`, a linked `EmployeeTaskRequest` is automatically created via the `EmployeeTask` module using the `CreateProjectNotificationTask` form key. The notification status is synced from the task status via `syncNotificationStatusFromTask()`.

---

### 2.2 Database Schema & Migrations

#### `projects` Table

**Created**: `2025_02_20_000001_create_projects_table.php`  
**Modified**: `2025_02_24_000001_add_contract_fields_to_projects_table.php`  
**Modified**: `2026_03_02_000000_add_serial_number_to_projects_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | UUID | Primary key |
| `project_type_id` | UUID FK | → `project_types.id`, restrict delete |
| `sub_project_type_id` | UUID FK | → `project_types.id`, restrict delete |
| `sub_sub_project_type_id` | UUID FK | → `project_types.id`, restrict delete |
| `name` | string | nullable |
| `manager_id` | UUID FK | → `users.id`, set null (renamed from `responsible_employee_id`) |
| `created_by_user_id` | UUID | Set by observer |
| `branch_id` | UUID FK | → `branches.id` (added in contract fields migration) |
| `project_owner_type` | string | Polymorphic type (added in contract fields migration) |
| `project_owner_id` | UUID | Polymorphic ID (added in contract fields migration) |
| `contract_id` | UUID | nullable |
| `client_id` | UUID | nullable |
| `project_classification_id` | UUID | nullable |
| `cost_center_branch_id` | UUID | nullable |
| `management_id` | UUID FK | → `management_hierarchies.id` |
| `currency_id` | UUID FK | → `currencies.id` |
| `project_value` | decimal | nullable |
| `company_id` | UUID FK | → `companies.id`, cascade delete |
| `status` | integer | default 1 |
| `serial_number` | string | unique, not null (added later, backfilled with `PRJ-0001` format) |
| `created_at`, `updated_at` | timestamps | |

**Indexes**: `project_type_id`, `sub_project_type_id`, `sub_sub_project_type_id`, `manager_id`, `company_id`, `branch_id`, `project_owner_id`, `contract_id`, `serial_number` (unique)

#### `project_roles` Table

**Created**: `2026_04_14_000002_create_project_roles_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | UUID | Primary key |
| `project_id` | UUID FK | → `projects.id`, cascade delete |
| `name` | string | |
| `slug` | string | |
| `description` | text | nullable |
| `is_default` | boolean | default false — marks auto-created admin role |
| `is_active` | boolean | default true |
| `created_at`, `updated_at` | timestamps | |

**Constraints**: `unique(['project_id', 'slug'])`, index on `project_id`, `is_default`

#### `project_permissions` Table

**Created**: `2026_04_14_000003_create_project_permissions_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | UUID | Primary key |
| `name` | string | unique — full permission identifier |
| `submodule` | string | e.g., `employee`, `archive-library`, `role` |
| `action` | string | e.g., `view`, `list`, `create`, `update`, `delete` |
| `title` | JSON | Translatable title (`{ar: "...", en: "..."}`) |
| `description` | text | nullable |
| `is_active` | boolean | default true |
| `created_at`, `updated_at` | timestamps | |

**Indexes**: `['submodule', 'action']`, `is_active`

#### `project_role_permissions` Table (Pivot)

**Created**: `2026_04_14_000004_create_project_role_permissions_table.php`

| Column | Type | Notes |
|---|---|---|
| `project_role_id` | UUID FK | → `project_roles.id`, cascade delete |
| `project_permission_id` | UUID FK | → `project_permissions.id`, cascade delete |
| `created_at`, `updated_at` | timestamps | |

**Primary Key**: composite `['project_role_id', 'project_permission_id']`

#### `project_employees` Table

**Created**: `2026_04_08_140000_create_contract_employees_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | UUID | Primary key |
| `project_id` | UUID FK | → `projects.id`, cascade delete |
| `user_id` | UUID FK | → `users.id`, cascade delete |
| `company_id` | UUID FK | → `companies.id`, cascade delete |
| `assigned_at` | timestamp | default current |
| `assigned_by_user_id` | UUID FK | → `users.id`, set null |
| `project_role_id` | UUID | nullable (added later) |
| `created_at`, `updated_at` | timestamps | |

**Constraints**: `unique(['project_id', 'user_id'])`, indexes on `project_id`, `user_id`, `company_id`

#### `resource_shares` Table

**Created**: `2026_04_08_130000_create_resource_shares_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | UUID | Primary key |
| `shareable_type` | string | Polymorphic type |
| `shareable_id` | UUID | Polymorphic ID |
| `owner_company_id` | UUID FK | → `companies.id`, cascade delete |
| `shared_with_company_id` | UUID FK | → `companies.id`, cascade delete |
| `status` | enum | `pending`, `accepted`, `rejected` — default `pending` |
| `schema_ids` | JSON | nullable — array of schema IDs shared |
| `shared_by_user_id` | UUID | nullable |
| `responded_by_user_id` | UUID | nullable |
| `responded_at` | timestamp | nullable |
| `notes` | text | nullable |
| `created_at`, `updated_at` | timestamps | |

**Constraints**: `unique(['shareable_type', 'shareable_id', 'shared_with_company_id'])`

#### `attachment_requests` Table

**Created**: `2026_04_08_150000_create_attachment_requests_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | UUID | Primary key |
| `serial_number` | string | unique |
| `name` | string | |
| `date` | date | |
| `project_id` | UUID FK | → `projects.id`, cascade delete |
| `sender_company_id` | UUID FK | → `companies.id`, cascade delete |
| `receiver_company_id` | UUID FK | → `companies.id`, cascade delete |
| `attachment_type_id` | unsignedBigInteger | nullable — folder ID |
| `attachment_sub_type_id` | unsignedBigInteger | nullable — subfolder ID |
| `attachment_sub_sub_type_id` | unsignedBigInteger | nullable — sub-subfolder ID |
| `status` | string | default `pending` |
| `created_by_user_id` | UUID FK | → `users.id`, set null |
| `responded_by_user_id` | UUID FK | → `users.id`, set null |
| `responded_at` | timestamp | nullable |
| `notes` | text | nullable |
| `created_at`, `updated_at` | timestamps | |

#### `attachment_request_items` Table

**Created**: `2026_04_08_150001_create_attachment_request_items_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | UUID | Primary key |
| `attachment_request_id` | UUID FK | → `attachment_requests.id`, cascade delete |
| `file_name` | string | |
| `file_path` | string | |
| `file_type` | string | nullable |
| `file_size` | unsignedBigInteger | nullable |
| `status` | string | default `pending` |
| `responded_by_user_id` | UUID FK | → `users.id`, set null |
| `responded_at` | timestamp | nullable |
| `response_notes` | text | nullable |
| `created_at`, `updated_at` | timestamps | |

#### `attachment_request_history` Table

**Created**: `2026_04_09_000000_create_attachment_request_history_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | UUID | Primary key (auto-generated via `HasUuids`) |
| `attachment_request_id` | UUID FK | → `attachment_requests.id` |
| `attachment_request_item_id` | UUID | nullable |
| `action` | string | |
| `description` | string | |
| `user_id` | UUID | nullable |
| `metadata` | JSON | nullable |
| `created_at` | timestamp | manually set |

#### Performance Indexes Migration

**File**: `2026_04_15_140000_add_indexes_to_project_permissions_tables.php`

Adds indexes to:
- `project_permissions`: `name`, `submodule`, `action`, `is_active`, `['submodule', 'action']`
- `project_role_permissions`: `project_role_id`, `project_permission_id`
- `project_employees`: `project_id`, `user_id`, `project_role_id`, `['project_id', 'user_id']`
- `project_roles`: `project_id`, `slug`, `is_active`, `is_default`

---

### 2.3 DTOs, Handlers & Commands

#### `CreateProjectManagementDTO`

**File**: `Modules\Project\ProjectManagement\DTO\CreateProjectManagementDTO`

Constructor parameters:
```
projectTypeId (int), subProjectTypeId (int), subSubProjectTypeId (int),
name (?string), managerId (?string), branchId (?string),
projectOwnerType (?string), projectOwnerId (?string), contractId (?string),
clientId (?string), projectClassificationId (?string), costCenterBranchId (?string),
managementId (?string), currencyId (?string), projectValue (?float), status (int = 1)
```

`toArray()` method automatically injects `company_id => tenant('id')`.

#### `UpdateProjectManagementCommand`

Passed to `UpdateProjectManagementHandler` which delegates to `ProjectManagementRepository::updateProjectManagement()`.

#### `DeleteProjectManagementHandler`

Accepts a `UuidInterface` and calls `ProjectManagementRepository::deleteProjectManagement()`.

#### `CreateProjectNotificationDTO`

**File**: `Modules\Project\ProjectManagement\DTO\CreateProjectNotificationDTO`

Constructor parameters:
```
projectId (string), createdByUserId (string), assignedUserId (string),
taskDate (string), durationHours (float), taskLatitude (float), taskLongitude (float),
notificationType (?string), severity (?string = 'منخفض'), workType (?string),
magdyNumber (?string), workDescription (?string),
contractorName (?string), contractorNumber (?string), contractorTechnicalNumber (?string),
contractorCategory (?string), contractorNotes (?string), contractorMobile (?string),
locationRadius (?int), locationLink (?string), repairPoint (?string),
selectedDistanceMeters (?int), notes (?string), files (?array),
approvalResponsibleId (?string), assignmentResponsibleId (?string)
```

`toArray()` returns all fields except `files`, `approvalResponsibleId`, `assignmentResponsibleId`.

#### `UpdateProjectNotificationDTO`

**File**: `Modules\Project\ProjectManagement\DTO\UpdateProjectNotificationDTO`

Used by `ProjectNotificationService::update()`.

#### `FilterProjectNotificationDTO`

**File**: `Modules\Project\ProjectManagement\DTO\FilterProjectNotificationDTO`

Used by `index`, `myTasks`, and `export` endpoints. Provides `toFilters()` method for repository filtering.

---

### 2.4 Services

#### `ProjectManagementCRUDService`

- `create(CreateProjectManagementDTO $dto): ProjectManagement`
- `list(int $page, int $perPage, ?string $userId = null): array` — paginated list with user filtering
- `get(string $id): ProjectManagement`

#### `ProjectEmployeeService`

- `assignEmployeesToProject(string $projectId, array $userIds, ?string $projectRoleId, ?string $companyId): Collection` — syncs (replaces) employees
- `appendEmployeesToProject(...)` — adds employees without removing existing
- `getProjectEmployees(string $projectId, ?string $companyId): Collection`
- `removeEmployeeFromProject(string $projectEmployeeId): bool` — verifies ownership via `withoutGlobalScope('shareable')`
- `getEmployeeProjects(string $userId): Collection`
- `getEmployeesNotInProject(string $projectId, ?string $companyId): Collection`
- `assignRoleToEmployee(string $projectEmployeeId, string $projectRoleId)` — updates the `project_role_id` on a `ProjectEmployee`

#### `ProjectRoleService`

- `getProjectRoles(string $projectId): Collection`
- `createRole(string $projectId, array $data, array $permissionIds = []): array` — auto-generates slug from name if not provided
- `updateRole(string $id, array $data, ?array $permissionIds = null): array`
- `assignPermissions(string $roleId, array $permissionIds): array` — adds permissions (append)
- `syncPermissions(string $roleId, array $permissionIds): array` — replaces all permissions
- `deleteRole(string $id): bool` — throws if `is_default` is true

#### `ProjectPermissionService`

- `getAllPermissions(): Collection` — all active permissions
- `getPermissionsBySubmodule(string $submodule): Collection`
- `updatePermission(string $id, array $data): array`

#### `AttachmentRequestService`

- `createRequest(array $data): AttachmentRequest` — verifies project access, auto-generates serial number, creates with items, logs history, broadcasts to receiver company
- `getAllRequests(array $filters = []): LengthAwarePaginator` — incoming + outgoing for current company
- `getOutgoingRequests(?string $projectId): Collection`
- `getIncomingRequests(?string $projectId): Collection`
- `getPendingIncoming(?string $projectId): Collection`
- `getRequest(string $requestId): AttachmentRequest` — verifies sender/receiver access
- `respondToItem(string $itemId, string $action, ?string $notes)` — approve/decline/request_update per item, saves approved attachments to ArchiveLibrary folders
- `approveRequest(string $requestId): AttachmentRequest` — approves all items, saves to folders, broadcasts to sender
- `declineRequest(string $requestId): AttachmentRequest` — declines all items, broadcasts to sender
- `replaceMedia(string $itemId, UploadedFile $newFile): AttachmentRequestItem` — replaces media for pending/update_requested items, logs history, updates parent status
- `getFolderChildren(?string $parentId, ?string $projectId): Collection` — for dropdown selection of attachment target folders

**Cross-Tenant Media Handling**: When sender and receiver are different tenants, the service switches tenant context (`tenancy()->end()` / `tenancy()->initialize()`) to fetch media from the sender's tenant and replicate it to the receiver's tenant.

#### `ProjectManagementDashboardWidgetsService`

- `getWidgetsData(string $companyId, array $dateRange = []): array`

Returns four widgets with current count/value, previous period count/value, percentage change, and trend (`up`/`down`/`stable`):
1. `total_projects` — total project count
2. `total_value` — sum of `project_value`
3. `active_projects` — projects with `status = 1`
4. `inactive_projects` — projects with `status != 1`

#### `ProjectNotificationService`

**File**: `Modules\Project\ProjectManagement\Services\ProjectNotificationService`

Manages project notifications — dashboard-created task assignments dispatched to employees. Each notification creates a linked `EmployeeTaskRequest` via the `EmployeeTask` module using the `CreateProjectNotificationTask` form key. The mobile lifecycle uses `ConfirmProjectNotificationPresence` as the confirm-receive step, which moves the task from the employee inbox (`approved`) to the assigned tasks list (`in_progress`).

Key methods:
- `create(CreateProjectNotificationDTO $dto): ProjectNotification` — Creates the notification row, then delegates to `EmployeeTaskRequestService::create()` with `InternalProcessForm::CreateProjectNotificationTask->value` as the form key. The linked `EmployeeTaskRequest` gets `is_project_notification = true`, `task_source = 'dashboard'`, and `project_notification_id` set. The `currentLatitude`/`currentLongitude` are explicitly `null` because the admin creates this from the dashboard, not from the employee's current GPS context.
- `list(FilterProjectNotificationDTO $dto): LengthAwarePaginator` — Paginated list with filters.
- `myTasks(FilterProjectNotificationDTO $dto, string $userId): LengthAwarePaginator` — Mobile endpoint: notifications whose `assigned_user_id` matches the current user and status is `approved`, `in_progress`, `completed`, or `rejected` (tasks assigned to the user that are already approved, started, finished, or rejected).
- `myInbox(FilterProjectNotificationDTO $dto, string $userId): LengthAwarePaginator` — Mobile endpoint: pending notifications that need workflow action. Selected from the `processes` table where the linked `project_notification_task` has an `in_progress` process with a `pending` step assigned to the current user (`assigned_user_id` or `authorized_user_ids`), and the notification status is `pending`. This matches the procedure/action-taker check.
- `inboxCounts(string $userId, array $filters = []): array` — Pending count for the workflow inbox badge, scoped by the same process-based workflow inbox filter.
- `filterMetadata(string $userId, array $filters = []): array` — Filter metadata for the mobile filter UI: status counts, project counts, min/max duration; scoped by the same process-based workflow inbox filter.
- `get(string $id): ProjectNotification`
- `update(string $id, UpdateProjectNotificationDTO $dto): ProjectNotification`
- `delete(string $id): bool`
- `approve(string $id, string $userId): ProjectNotification` — Approves the notification; if the linked task has an active workflow process, it advances that too.
- `reject(string $id, string $userId, string $reason): ProjectNotification`
- `syncNotificationStatusFromTask(ProjectNotification $notification, $task): void` — Maps task status to notification status.
- `confirmReceive(string $notificationId, StartTaskDTO $dto, User $user): EmployeeTaskRequest` — Mobile confirm-receive. If the linked task is still `pending`, it is auto-approved first, then the task is started. This moves the notification from the inbox (`approved`) to the assigned tasks list (`in_progress`). Form key: `ConfirmProjectNotificationPresence`.
- `startTask(string $notificationId, StartTaskDTO $dto, User $user): EmployeeTaskRequest` — Mobile: backward-compatible alias that delegates to `confirmReceive()` internally.
- `endTask(string $notificationId, EndTaskDTO $dto): EmployeeTaskRequest` — Mobile: employee ends the linked task. Form key: `EndProjectNotificationTask`.
- `takeAction(string $notificationId, string $procedureSettingId, string $userId): array` — Records a generic internal procedure action (e.g., `UpdateProjectNotificationTask`).
- `availableActions(string $notificationId): array` — Lists available workflow actions for the notification, same as `GET /employee-tasks/{id}/available-actions`.

**Cross-Module Relationship with EmployeeTask**:

The `create()` method builds a `CreateEmployeeTaskRequestDTO` with:
- `taskLatitude` / `taskLongitude` from the notification DTO (task location)
- `currentLatitude` / `currentLongitude` set to `null` (no employee GPS at dashboard creation time)
- `itemType = 'project_notification'`, `itemId = notification->id`
- `employee_task_type_id` resolved from `EmployeeTaskType` where `key = 'project_notification'`

It then calls `EmployeeTaskRequestService::create($taskDto, InternalProcessForm::CreateProjectNotificationTask->value)`.

When creating the workflow for `CreateProjectNotificationTask`, the frontend may choose `assigned_user` as the action taker type. In that case the action taker is resolved to the `EmployeeTaskRequest.user_id` (the employee the notification is assigned to), so the task is sent to that employee's inbox.

**Condition Evaluation for Project Notifications**:

Only `InsideCustomLocations` is evaluated for `CreateProjectNotificationTask`. The admin sets the task location from the dashboard, so the employee's real-time context (current shift, current GPS, today's holiday status) is not relevant at creation time. `EmployeeTaskFormConditionService::checkCreateTaskConditions()` resolves the condition map from `InternalProcessForm::CreateProjectNotificationTask->conditions()`, which now contains only `InsideCustomLocations`. If `InsideCustomLocations` is active and the task location falls outside the configured polygons, creation fails.

Normal employee task creation (`CreateTask` form) is unaffected — all conditions are evaluated.

#### `ProjectNotificationLocationService`

**File**: `Modules\Project\ProjectManagement\Services\ProjectNotificationLocationService`

- `getProjectEmployeesWithLocations(?string $projectId, float $lat, float $lng, ?float $radius): array` — Returns project employees sorted by distance from the given coordinates, used for the "nearest employees" selection UI when creating a notification.

---

### 2.5 Repositories

#### `ProjectManagementRepository`

- `paginated(int $page, int $perPage, ?string $userId = null): array` — loads project types, manager, branch, owner, client, currency, company, shares
- `getProjectManagement(string $id): ProjectManagement`
- `createProjectManagement(array $data): ProjectManagement`
- `updateProjectManagement(string $id, array $data): bool`
- `deleteProjectManagement(string $id): bool` — checks constraints (employees, roles) before deletion
- `getTotalProjectsCount(string $companyId, $endDate): int`
- `getTotalProjectsValue(string $companyId, $endDate): float`
- `getActiveProjectsCount(string $companyId, $endDate): int`
- `getInactiveProjectsCount(string $companyId, $endDate): int`

#### `ProjectEmployeeRepository`

- `syncEmployees(...)` — replaces all employees for a project
- `appendEmployees(...)` — adds new employees without removing existing
- `getByProject(string $projectId, ?string $companyId): Collection`
- `getEmployeesNotInProject(string $projectId, string $companyId): Collection`
- `getProjectsByEmployee(string $userId): Collection`
- `findOneOrFail(string $id, array $relations = []): ProjectEmployee`
- `update(string $id, array $data): bool`
- `delete(string $id): bool`

#### `ProjectRoleRepository`

- `getByProject(string $projectId): Collection`
- `createRole(array $data, array $permissionIds): ProjectRole`
- `updateRole(string $id, array $data, ?array $permissionIds): ProjectRole`
- `assignPermissions(string $roleId, array $permissionIds): ProjectRole` — attach (append)
- `syncPermissions(string $roleId, array $permissionIds): ProjectRole` — sync (replace)
- `findOneOrFail(string $id): ProjectRole`
- `delete(string $id): bool`

#### `ProjectPermissionRepository`

- `getAllActive(): Collection`
- `getBySubmodule(string $submodule): Collection`
- `updatePermission(string $id, array $data): ProjectPermission`

#### `AttachmentRequestRepository`

- `createWithItems(array $requestData, array $items): AttachmentRequest`
- `getAllRequests(string $companyId, array $filters): LengthAwarePaginator`
- `getOutgoingRequests(string $companyId, ?string $projectId): Collection`
- `getIncomingRequests(string $companyId, ?string $projectId): Collection`
- `getPendingIncoming(string $companyId, ?string $projectId): Collection`
- `getWithItems(string $requestId): ?AttachmentRequest`
- `generateSerialNumber(): string`

---

### 2.6 Controllers & API Endpoints

All routes are prefixed with `api/v1/projects` and protected by `auth:api` and `InitializeTenancyByRequestData` middleware.

#### `ProjectManagementController`

| Method | Endpoint | Permission | Description |
|---|---|---|---|
| GET | `/` | `PROJECT_MANAGEMENT_LIST` | Paginated project list |
| POST | `/` | `PROJECT_MANAGEMENT_CREATE` | Create project |
| POST | `/export` | `PROJECT_MANAGEMENT_EXPORT` | Excel export |
| GET | `/widgets` | `PROJECT_MANAGEMENT_LIST` | Dashboard widgets data |
| GET | `/{id}` | `PROJECT_MANAGEMENT_VIEW` | Single project details |
| PUT | `/{id}` | `PROJECT_MANAGEMENT_UPDATE` | Update project |
| DELETE | `/{id}` | `PROJECT_MANAGEMENT_DELETE` | Delete project |

#### `ProjectShareController`

| Method | Endpoint | Permission | Description |
|---|---|---|---|
| POST | `/{project_id}/share` | `PROJECT_MANAGEMENT_UPDATE` | Share project with company |
| GET | `/{project_id}/shares` | `PROJECT_MANAGEMENT_VIEW` | List shares for project |
| GET | `/shares/pending` | `PROJECT_MANAGEMENT_LIST` | Pending invitations for current company |
| POST | `/shares/{share_id}/accept` | `PROJECT_MANAGEMENT_UPDATE` | Accept share invitation |
| POST | `/shares/{share_id}/reject` | `PROJECT_MANAGEMENT_UPDATE` | Reject share invitation |
| DELETE | `/shares/{share_id}` | `PROJECT_MANAGEMENT_UPDATE` | Remove share (owner only) |
| GET | `/shared-with-me` | `PROJECT_MANAGEMENT_LIST` | Projects shared with current company |
| GET | `/{project_id}/shared-companies` | `PROJECT_MANAGEMENT_VIEW` | Companies the project is shared with |

Sends email notifications via `ProjectShareMail` to receiver company owners. Action URLs are built from company domain/serial number.

#### `ProjectEmployeeController`

| Method | Endpoint | Middleware | Description |
|---|---|---|---|
| POST | `/{project_id}/employees` | `project.permission:PROJECT_EMPLOYEE_CREATE` | Assign employees |
| GET | `/{project_id}/employees` | `project.permission:PROJECT_EMPLOYEE_LIST` | List project employees |
| DELETE | `/{project_id}/employees/{employee_id}` | `project.permission:PROJECT_EMPLOYEE_DELETE` | Remove employee |
| GET | `/{project_id}/employees/not-in` | `project.permission:PROJECT_EMPLOYEE_LIST` | Employees not in project |
| PUT | `/{project_id}/employees/{employee_id}/role` | `project.permission:PROJECT_EMPLOYEE_UPDATE` | Assign role to employee |

Sends email via `EmployeeAssignedMail`.

#### `ProjectPermissionController`

| Method | Endpoint | Description |
|---|---|---|
| GET | `/permissions` | List all permissions |
| GET | `/permissions/submodule/{submodule}` | Filter by submodule |
| PUT | `/permissions/{id}` | Update permission title/description |
| GET | `/permissions/tree` | Hierarchical tree structure |
| GET | `/permissions/flat` | Flat list for dropdowns |
| GET | `/{project_id}/user-permissions/{user_id}` | User's permissions (flat) |
| GET | `/{project_id}/user-permissions/{user_id}/tree` | User's permissions (tree) |
| POST | `/{project_id}/check-permissions` | Bulk permission check |
| GET | `/{project_id}/permissions/{permission_name}/users` | Users with specific permission |

#### `ProjectRoleController`

| Method | Endpoint | Middleware | Description |
|---|---|---|---|
| GET | `/{project_id}/roles` | `project.permission:PROJECT_ROLE_LIST` | List roles |
| POST | `/{project_id}/roles` | `project.permission:PROJECT_ROLE_CREATE` | Create role |
| GET | `/{project_id}/roles/{role_id}` | `project.permission:PROJECT_ROLE_VIEW` | Role details |
| PUT | `/{project_id}/roles/{role_id}` | `project.permission:PROJECT_ROLE_UPDATE` | Update role |
| DELETE | `/{project_id}/roles/{role_id}` | `project.permission:PROJECT_ROLE_DELETE` | Delete role |
| POST | `/{project_id}/roles/{role_id}/permissions` | `project.permission:PROJECT_ROLE_UPDATE` | Assign permissions |
| PUT | `/{project_id}/roles/{role_id}/permissions` | `project.permission:PROJECT_ROLE_UPDATE` | Sync permissions |

#### `AttachmentRequestController`

| Method | Endpoint | Description |
|---|---|---|
| POST | `/{project_id}/attachment-requests` | Create attachment request |
| GET | `/attachment-requests` | All requests (incoming + outgoing) |
| GET | `/{project_id}/attachment-requests/outgoing` | Outgoing requests |
| GET | `/{project_id}/attachment-requests/incoming` | Incoming requests |
| GET | `/{project_id}/attachment-requests/pending` | Pending incoming |
| GET | `/attachment-requests/{request_id}` | Request details |
| POST | `/attachment-requests/{request_id}/items/{item_id}/respond` | Respond to item |
| POST | `/attachment-requests/{request_id}/approve` | Approve entire request |
| POST | `/attachment-requests/{request_id}/decline` | Decline entire request |
| GET | `/attachment-requests/folders/children` | Folder children for dropdowns |
| POST | `/attachment-requests/items/{item_id}/replace-media` | Replace item media |

Sends email via `AttachmentRequestMail`.

#### `ProjectNotificationController`

**File**: `Modules\Project\ProjectManagement\Controllers\ProjectNotificationController`

| Method | Endpoint | Permission | Description |
|---|---|---|---|
| GET | `/notifications` | `PROJECT_NOTIFICATION_LIST` | List notifications (paginated, filterable) |
| POST | `/notifications` | `PROJECT_NOTIFICATION_CREATE` | Create notification + linked EmployeeTask |
| GET | `/notifications/{id}` | `PROJECT_NOTIFICATION_VIEW` | Notification details |
| PUT | `/notifications/{id}` | `PROJECT_NOTIFICATION_UPDATE` | Update notification |
| DELETE | `/notifications/{id}` | `PROJECT_NOTIFICATION_DELETE` | Delete notification |
| POST | `/notifications/export` | `PROJECT_NOTIFICATION_EXPORT` | Export notifications (xlsx/csv) |
| GET | `/notifications/employees-with-locations` | `PROJECT_NOTIFICATION_CREATE` | List project employees sorted by distance |
| POST | `/notifications/{id}/approve` | `PROJECT_NOTIFICATION_UPDATE` | Approve notification |
| POST | `/notifications/{id}/reject` | `PROJECT_NOTIFICATION_UPDATE` | Reject notification (requires `reason`) |
| GET | `/notifications/my-tasks` | `PROJECT_NOTIFICATION_LIST` | Mobile: all notifications assigned to current employee (after confirm-receive) |
| GET | `/notifications/my-inbox` | `PROJECT_NOTIFICATION_LIST` | Mobile: approved notifications waiting for confirm-receive |
| GET | `/notifications/my-inbox-counts` | `PROJECT_NOTIFICATION_LIST` | Mobile: status counts for the employee's notifications (badge) |
| GET | `/notifications/filters` | `PROJECT_NOTIFICATION_LIST` | Mobile: filter metadata (same format as employee-tasks/filters: statuses with title_ar/title_en, projects with key/title, duration in minutes) |
| GET | `/notifications/{id}/available-actions` | `PROJECT_NOTIFICATION_VIEW` | Available workflow actions |
| POST | `/notifications/{id}/confirm-receive` | `PROJECT_NOTIFICATION_UPDATE` | Mobile: confirm receive and start the linked task |
| POST | `/notifications/{id}/start` | `PROJECT_NOTIFICATION_UPDATE` | Mobile: backward-compatible alias for confirm-receive |
| POST | `/notifications/{id}/take-action` | `PROJECT_NOTIFICATION_UPDATE` | Record a generic procedure action (e.g., `UpdateProjectNotificationTask`) |
| GET | `/notifications/{id}/procedures` | `PROJECT_NOTIFICATION_VIEW` | Linked EmployeeTask procedures timeline + summary |
| POST | `/notifications/{id}/end` | `PROJECT_NOTIFICATION_UPDATE` | Mobile: end linked task |

**Route prefix**: `/api/v1/projects/notifications`

**Notes**:
- `store` uses `CreateProjectNotificationRequest` for validation; creates via `ProjectNotificationService::create()` which delegates to `EmployeeTaskRequestService`.
- `confirm-receive`/`start`/`end` use `StartTaskRequest`/`EndTaskRequest` from the EmployeeTask module and return `EmployeeTaskRequestPresenter` responses.
- `takeAction` validates `internal_procedure_setting_id` as required UUID existing in `procedure_settings`.
- The mobile inbox (`/my-inbox`) only returns notifications with `status = approved`. After `POST /notifications/{id}/confirm-receive`, the task moves to `in_progress` and appears in `/my-tasks` instead.
- `/filters` returns the same response shape as `GET /employee-tasks/filters`: `statuses` (key, title_ar, title_en, count), `projects` (key, title, count), `duration` (key, title_ar, title_en, min_minutes, max_minutes).
- Notification status is auto-synced from the linked `EmployeeTaskRequest` by `EmployeeTaskStatusSyncObserver` whenever the task status changes (e.g., `in_progress` after confirm-receive, `completed` after end). The observer maps `paused` → `in_progress` for the notification.
- The linked `EmployeeTaskRequest` exposes its taken internal procedures via `GET /employee-tasks/{employee_task_id}/procedures`. The mobile app can use the linked task ID to display the procedures (الإجراءات) timeline for the assigned task. Response includes `items` (ordered by step with `name`, `icon`, `percentage`, `form`, `taken_by`, `taken_at`) and `summary` (`total`, `last_action`, `start_date`, `progress`).
- `GET /notifications/{id}/procedures` is a convenience wrapper over the employee-task endpoint: it resolves the linked `EmployeeTaskRequest` from the notification id and returns the same `items` + `summary` shape.

---

### 2.7 Observers

#### `ProjectManagementObserver`

**Registered in**: `ProjectManagementServiceProvider::boot()`

**`creating` event**:
- Generates a unique serial number (`generateSerialNumber()`)
- Sets `created_by_user_id` from `Auth::id()`

**`created` event**:
- Creates a project folder in `ArchiveLibrary` (`createProjectFolder()`)
- Creates a default "Project Admin" role with `is_default = true` (`createProjectAdminRole()`)
  - Assigns all active `ProjectPermission` records to this role
  - Assigns the project manager (`manager_id`) and creator (`created_by_user_id`) to this role as `ProjectEmployee` records
- All operations wrapped in a DB transaction

**`updated` event**:
- If the project name changed, updates the corresponding folder name in `ArchiveLibrary`

#### `ProjectRoleObserver`

**Registered in**: `ProjectManagementServiceProvider::boot()`

**`updating` event**:
- Blocks updates to roles where `is_default = true` (the auto-created "Project Admin" role)
- Throws an exception with a descriptive message

**`deleting` event**:
- Blocks deletion of roles where `is_default = true`
- Throws an exception with a descriptive message

#### `ProjectNotificationObserver`

**Registered in**: `ProjectManagementServiceProvider::boot()`  
**Listens to**: `ProjectNotification` model events

**`creating` event**:
- Generates a unique `notification_number` (`NOTIF-{YEAR}-{00001}` format, unique per company)

#### `EmployeeTaskStatusSyncObserver`

**Registered in**: `ProjectManagementServiceProvider::boot()`  
**Listens to**: `EmployeeTaskRequest` model `updated` event

When an `EmployeeTaskRequest` with `is_project_notification = true` has its `status` changed, this observer automatically syncs the linked `ProjectNotification` status. Status mapping:

| Task Status | Notification Status |
|---|---|
| `pending` | `pending` |
| `approved` | `approved` |
| `rejected` | `rejected` |
| `in_progress` | `in_progress` |
| `paused` | `in_progress` |
| `completed` | `completed` |
| `cancelled` | `cancelled` |

This ensures that after `confirm-receive` (task → `in_progress`) or `end` (task → `completed`), the notification status is updated without any manual sync calls.

---

### 2.8 Middleware

#### `CheckProjectPermission`

**Alias**: `project.permission`  
**Registered in**: `ProjectManagementServiceProvider::boot()` as `$this->app['router']->aliasMiddleware('project.permission', CheckProjectPermission::class)`

**Usage in routes**: `->middleware('project.permission:PROJECT_EMPLOYEE_CREATE')`

**Resolution Logic**:
1. Extracts `project_id` from route parameter or request input
2. Gets authenticated user ID
3. Resolves the permission string: checks if it's a config key in `config('project-management.permissions')`; if found, uses the config value; otherwise uses the raw string
4. Retrieves the user's `ProjectEmployee` record for the project
5. Loads the employee's `ProjectRole` and its `permissions` (cached for 1 hour via `Cache::remember()`)
6. Checks if the resolved permission name exists in the user's permission set
7. Returns 403 JSON if unauthorized

**Caching**: User permissions are cached with key `project_permissions:{project_id}:{user_id}` for 60 minutes.

---

### 2.9 Presenters

#### `ProjectManagementPresenter`

**File**: `Modules\Project\ProjectManagement\Presenters\ProjectManagementPresenter`

**Detail mode** (`isListing = false`):
- All scalar fields (id, serial_number, name, type IDs, manager_id, etc.)
- Nested objects: `project_type`, `sub_project_type`, `sub_sub_project_type`, `manager`, `branch`, `project_owner`, `project_classification`, `company`, `client`, `cost_center_branch`, `management`, `currency`
- `employees` array (if relation loaded) with pivot `assigned_at`
- `is_shared` boolean and `allowed_schemas` array (for shared projects)
- `permissions` object — conditionally includes schema-based settings from `subSubProjectType`:
  - `archive_library_setting` (schema 3)
  - `employee_contract_setting` (schema 5)
  - `attachment_cycle_setting` (schema 9)
  - `roles_and_permissions_setting` (schema 10)
  - `project_sharing_setting` (schema 11)
  - Each included only if `shouldIncludeSchema()` returns true (owner sees all; shared company sees only allowed schemas)

**Listing mode** (`isListing = true`):
- Scalar fields + flattened names (`project_type_name`, `manager_name`, `client_name`, etc.)

#### `ProjectPermissionLookupPresenter`

**File**: `Modules\Project\ProjectManagement\Presenters\ProjectPermissionLookupPresenter`

- `present(Collection $permissions): array` — groups permissions by category → submodule, includes translated titles (ar/en), permission keys (reverse-mapped from config)
- `presentFlat(Collection $permissions): array` — flat list for dropdowns

**Category mapping** (with Arabic and English translations):
- `employee` → Employee Management / إدارة الموظفين
- `archive-library` → Archive Library / المكتبة الأرشيفية
- `archive-cycle` → Archive Cycle / دورة الأرشيف
- `role` → Role Management / إدارة الأدوار
- `project-share` → Project Sharing / مشاركة المشاريع
- `roles-and-permissions-settings` → Roles and Permissions Settings / إعدادات الأدوار والصلاحيات
- `project-sharing-settings` → Project Sharing Settings / إعدادات مشاركة المشاريع
- `settings`, `task`, `budget`, `expense`, `report` — additional categories

#### `ProjectEmployeePresenter`

Presents employee with: id, project_id, user (id/name/email), project_role (id/name/slug/is_default), assigned_at, assigned_by (id/name), company (id/name), created_at.

#### `AttachmentRequestPresenter`

Presents request with: all scalar fields, `type` (outgoing/incoming based on sender), nested project/sender_company/receiver_company/created_by/responded_by, items (via `AttachmentRequestItemPresenter`), attachments_preview, statistics (total/approved/declined/pending/update_requested counts), and history log.

#### `ProjectNotificationPresenter`

**File**: `Modules\Project\ProjectManagement\Presenters\ProjectNotificationPresenter`

- `toArray(): array` — Transforms a `ProjectNotification` into: all scalar fields, nested `project` (id/name/serial_number), `assigned_user` (id/name) — dashboard-selected user, `created_by` (id/name), `employee_task` (id/serial_number/status/status_label/duration_hours/user), `status_label`, `internal_procedure_setting_id` (confirm-receive procedure setting ID for the linked employee task, used by `POST /projects/notifications/{id}/confirm-receive`), `attachments` (media URLs), and timestamps.
- `static single(ProjectNotification $notification): array` — Single notification response.
- `static detail(ProjectNotification $notification): array` — Alias for `single()`.
- `toListArray(): array` — Mobile list/inbox/my-tasks shape: `id`, `notification_number`, `notification_type`, `work_type`, `severity`, `contractor_name`, `magdy_number`, `status`, `status_label`, `task_date`, `duration_hours`, `selected_distance_meters`, `internal_procedure_setting_id`, `violations_count`, `created_at`, `assigned_user`, and `employee_task` (`id`, `status`, `serial_number`, `duration_hours`, `user`).
- `static collection(array $notifications): array` — Maps a collection through `toListArray()`; includes `internal_procedure_setting_id`, `employee_task`, and `duration_hours` so mobile list/inbox views can call confirm-receive and verify the actual task assignee and duration.

#### `ProjectNotificationEmployeeLocationPresenter`

**File**: `Modules\Project\ProjectManagement\Presenters\ProjectNotificationEmployeeLocationPresenter`

- `static collection(array $employees): array` — Transforms employee location data for the "nearest employees" selection UI: id, name, email, phone, branch_name, distance_meters, project_role.

---

### 2.10 Filters

#### `ProjectManagementFilter`

Extends `SearchModelFilter`. Available filter methods:
- `name` — LIKE search
- `projectTypeId`, `subProjectTypeId`, `subSubProjectTypeId`
- `managerId`, `branchId`
- `projectOwnerType`, `projectOwnerId`
- `contractId`, `clientId`, `managementId`
- `status`

---

### 2.11 Mail & Notifications

#### `ProjectShareMail`

Sent to receiver company owner when a project is shared. Contains:
- Project name and serial number
- Sender company name
- Action URLs (accept/reject) built from receiver company domain

#### `EmployeeAssignedMail`

Sent to employees when assigned to a project. Contains:
- Project name
- Action URL built from company domain

#### `AttachmentRequestMail`

Sent to receiver company owner when an attachment request is created. Contains:
- Request name, serial number, project name
- Sender company name
- Action URL

#### `ProjectNotificationException`

**File**: `Modules\Project\ProjectManagement\Exceptions\ProjectNotificationException`

Extends `RuntimeException`. Static factory methods:

| Method | Description |
|---|---|
| `notFound(string $id)` | Notification not found |
| `cannotApprove(string $status)` | Cannot approve — status is not `pending` |
| `cannotReject(string $status)` | Cannot reject — status is not `pending` |
| `taskTypeNotFound()` | `EmployeeTaskType` with `key = 'project_notification'` missing — run seeder |
| `linkedTaskNotFound(string $id)` | Notification has no linked `EmployeeTaskRequest` |
| `procedureNotAvailable()` | Requested procedure setting is not available for this notification |

---

### 2.12 Events

#### `AttachmentRequestCreated`

Broadcast to receiver company users when a new attachment request is created. Includes pending incoming count.

#### `AttachmentRequestResponded`

Broadcast to sender company users when a request is approved or declined. Includes action type (`approved`/`declined`).

---

### 2.13 Enums

#### `ProjectPermission` (Enum)

**File**: `Modules\Project\ProjectManagement\Enums\ProjectPermission`

This is a **dynamic enum** that loads permission values from `config('project-management.permissions')`.

**Magic Method**: `__callStatic(string $name, array $arguments): string`
- Called as `ProjectPermission::PROJECT_EMPLOYEE_VIEW()` → returns `'project-management.project-management*employee.view'`
- Throws `InvalidArgumentException` if key not found in config

**Static Methods**:
- `has(string $key): bool` — checks if a permission key exists in config
- `all(): array` — returns all permissions as key → value
- `keys(): array` — returns all permission keys
- `values(): array` — returns all permission values

---

### 2.14 Commands (Artisan)

#### `UpdateProjectPermissionNamesCommand`

**Signature**: `project-permissions:update-names {--dry-run} {--key=*} {--force} {--delete-orphaned} {--create-missing}`

**Purpose**: Synchronises database permission names with config. Features:
- **Dry run**: Preview changes without applying
- **Key filter**: Update specific keys only
- **Force**: Skip confirmation prompts
- **Delete orphaned**: Remove permissions in DB but not in config
- **Create missing**: Report permissions in config but not in DB (recommends running the seeder)

#### `TestProjectShareEmailCommand`

Registered in `ProjectManagementServiceProvider`. Used for testing project share email delivery.

---

### 2.15 Seeders

#### `ProjectPermissionsSeeder`

**File**: `Modules\Project\ProjectManagement\Database\Seeders\ProjectPermissionsSeeder`

**Process**:
1. Loads permissions from `config('projectmanagement.permissions')` (note: the config key is `projectmanagement`, not `project-management`, due to module alias)
2. Parses each permission name to extract `submodule` and `action`
3. Auto-generates Arabic and English translations based on submodule/action mappings
4. Uses `ProjectPermission::updateOrCreate()` by name to be idempotent
5. Assigns newly created permissions to all existing "Project Admin" roles (`is_default = true`)

**Translation Generation**: Maps known actions (`view`, `list`, `create`, `update`, `delete`, `assign`, `export`, `activate`) and submodules to Arabic/English titles. Falls back to `ucfirst()` for unknown values.

---

### 2.16 Config

#### Module Config

**File**: `Modules\Project\ProjectManagement\Resources\config\config.php`

**Loaded as**: `config('project-management.permissions')` (module alias `projectmanagement` → config key `project-management`)

**Structure**:
```php
return [
    'name' => 'ProjectManagement',
    'permissions' => [
        'PROJECT_EMPLOYEE_VIEW' => 'project-management.project-management*employee.view',
        'PROJECT_EMPLOYEE_LIST' => 'project-management.project-management*employee.list',
        // ... more permissions
        'PROJECT_ARCHIVE_VIEW' => 'project-management.project-management*archive-library.view',
        'PROJECT_ROLE_VIEW' => 'project-management.project-management*role.view',
        'PROJECT_SHARE_VIEW' => 'project-management.project-management*project-share.view',
        'PROJECT_ARCHIVE_CYCLE_VIEW' => 'project-management.project-management*archive-cycle.view',
        'PROJECT_NOTIFICATION_VIEW'   => 'project-management.project-management*notifications.view',
        'PROJECT_NOTIFICATION_LIST'   => 'project-management.project-management*notifications.list',
        'PROJECT_NOTIFICATION_CREATE' => 'project-management.project-management*notifications.create',
        'PROJECT_NOTIFICATION_UPDATE' => 'project-management.project-management*notifications.update',
        'PROJECT_NOTIFICATION_DELETE' => 'project-management.project-management*notifications.delete',
        // Some permissions are commented out (attachment-cycle-settings, archive-library-settings)
    ]
];
```

**Active Permission Groups**:
1. **Employee Management**: `PROJECT_EMPLOYEE_{VIEW,LIST,CREATE,UPDATE,DELETE}`
2. **Archive Library**: `PROJECT_ARCHIVE_{VIEW,LIST,CREATE,UPDATE,DELETE}`
3. **Role Management**: `PROJECT_ROLE_{VIEW,LIST,CREATE,UPDATE,DELETE}`
4. **Project Sharing**: `PROJECT_SHARE_{VIEW,LIST,CREATE,UPDATE,DELETE}`
5. **Archive Cycle**: `PROJECT_ARCHIVE_CYCLE_{VIEW,LIST,CREATE,UPDATE,DELETE}`
6. **Project Notifications**: `PROJECT_NOTIFICATION_{VIEW,LIST,CREATE,UPDATE,DELETE}` + `PROJECT_NOTIFICATION_EXPORT`

---

## 3. Sub-Module: ProjectType

### 3.1 Models

#### `ProjectType` (table: `project_types`)

**File**: `Modules\Project\ProjectType\Models\ProjectType`

**Traits**: `HasFactory`, `BaseFilterable`, `AsTree`, `BelongsToTenant`

**Primary Key**: Auto-incrementing integer (`$incrementing = true`, `$keyType = 'int'`)

**Fillable Fields**:
```
name, icon, parent_id, reference_project_type_id, company_id,
path, is_created, is_have_schema, is_active
```

**Casts**: `is_created`, `is_have_schema`, `is_active` → `int`

**Relationships**:
- `company()` → `belongsTo(Company)`
- `parent()` → `belongsTo(ProjectType, 'parent_id')` (via `AsTree`)
- `schemas()` → `belongsToMany(Schema, 'project_type_schemas', 'project_type_id', 'schema_id')`
- `projectDataSetting()` → `hasOne(ProjectDataSetting, 'project_type_id')`
- `attachmentContractSetting()` → `hasOne(AttachmentContractSetting, 'project_type_id')`
- `attachmentTermsContractSetting()` → `hasOne(AttachmentTermsContractSetting, 'project_type_id')`
- `contractorContractSetting()` → `hasOne(ContractorContractSetting, 'project_type_id')`
- `employeeContractSetting()` → `hasOne(EmployeeContractSetting, 'project_type_id')`
- `departmentContractSetting()` → `hasOne(DepartmentContractSetting, 'project_type_id')`
- `attachmentCycleSetting()` → `hasOne(AttachmentCycleSetting, 'project_type_id')`
- `archiveLibrarySetting()` → `hasOne(ArchiveLibrarySetting, 'project_type_id')`
- `rolesAndPermissionsSetting()` → `hasOne(RolesAndPermissionsSetting, 'project_type_id')`
- `projectSharingSetting()` → `hasOne(ProjectSharingSetting, 'project_type_id')`
- `maintenanceEmergencySetting()` → `hasOne(MaintenanceEmergencySetting, 'project_type_id')`

**Scopes**:
- `secondLevel()` — project types whose parent has no parent (i.e., second level in tree)
- `seeded()` — `is_created = false` (system-seeded data)
- `userCreated()` — `is_created = true`
- `active()` — `is_active = true`
- `withSchema()` — `is_have_schema = true`

**Key Methods**:
- `isSeeded(): bool` — returns `!$this->is_created`
- `hasSchema(): bool` — returns `$this->is_have_schema`
- `getRelationshipToPrimaryModel(): string` — returns `"company"`

#### `Schema` (table: `project_schemas`)

**Traits**: `HasFactory`, `BaseFilterable`

**Fillable**: `name`

**Relationships**: `projectTypes()` → `belongsToMany(ProjectType, 'project_type_schemas', 'schema_id', 'project_type_id')`

#### `ProjectTypeSchema` (table: `project_type_schemas`) — Pivot

**Fillable**: `project_type_id`, `schema_id`

#### Setting Models

All setting models follow the same pattern: `hasOne` to `ProjectType`, with boolean/int fields controlling which data fields are visible/configurable.

| Model | Table | Key Fields |
|---|---|---|
| `ProjectDataSetting` | `project_data_settings` | `is_reference_number`, `is_name_project`, `is_client`, `is_responsible_engineer`, `is_number_contract`, `is_central_cost`, `is_project_value`, `is_start_date`, `is_achievement_percentage` |
| `AttachmentContractSetting` | `attachment_contract_settings` | (similar pattern) |
| `AttachmentTermsContractSetting` | `attachment_terms_contract_settings` | (similar pattern) |
| `ContractorContractSetting` | `contractor_contract_settings` | (similar pattern) |
| `EmployeeContractSetting` | `employee_contract_settings` | (similar pattern) |
| `DepartmentContractSetting` | `department_contract_settings` | (similar pattern) |
| `AttachmentCycleSetting` | `attachment_cycle_settings` | (similar pattern) |
| `ArchiveLibrarySetting` | `archive_library_setting` | `is_all_data_visible` |
| `RolesAndPermissionsSetting` | `roles_and_permissions_settings` | `is_all_data_visible` |
| `ProjectSharingSetting` | `project_sharing_settings` | `is_all_data_visible` |
| `MaintenanceEmergencySetting` | `maintenance_emergency_settings` | `is_shown` |

#### OrderPermit Models

| Model | Table | Fields |
|---|---|---|
| `OrderPermit` | `order_permit` | `project_type_id`, `code`, `description`, `type` |
| `OrderPermitDepartment` | `order_permit_department` | (department-level config) |
| `OrderPermitProcedure` | `order_permit_procedure` | (procedure-level config) |
| `OrderPermitTask` | `order_permit_tasks` | (task-level config) |
| `OrderPermitTasksSetting` | `order_permit_tasks_setting` | (task settings) |
| `ReportForm` | `order_permit_report_forms` | (report form config) |

---

### 3.2 Database Schema & Migrations

#### `project_types` Table

**Created**: `2025_02_17_120000_create_project_types_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | Auto-increment PK |
| `name` | string | |
| `icon` | string | nullable |
| `parent_id` | unsignedBigInteger FK | → `project_types.id`, cascade delete (self-referencing) |
| `reference_project_type_id` | unsignedBigInteger FK | → `project_types.id`, set null (schema inheritance reference) |
| `company_id` | UUID | nullable, indexed |
| `path` | string(500) | nullable, indexed — tree path |
| `is_created` | boolean | default true — false for seeded, true for user-created |
| `is_have_schema` | boolean | default false |
| `is_active` | boolean | default true |
| `created_at`, `updated_at` | timestamps | |

#### `project_schemas` Table

**Created**: `2025_02_17_120050_create_schemas_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | Auto-increment PK |
| `name` | string | |
| `created_at`, `updated_at` | timestamps | |

#### `project_type_schemas` Table (Pivot)

**Created**: `2025_02_17_120100_create_project_type_schemas_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | Auto-increment PK |
| `project_type_id` | unsignedBigInteger FK | → `project_types.id` |
| `schema_id` | unsignedBigInteger FK | → `project_schemas.id` |

#### Settings Tables

Each setting table follows the pattern:
- `id` (auto-increment PK)
- `project_type_id` (FK → `project_types.id`)
- Boolean/int fields for each configurable option
- `timestamps`

**Additional migrations**:
- `2025_04_18_150000_add_is_all_data_visible_to_settings_tables.php` — adds `is_all_data_visible` column to settings tables
- `2026_06_26_180000_create_maintenance_emergency_settings_table.php` — creates `maintenance_emergency_settings` table for schema 12 visibility

#### Order Permit Tables

Created in `2026_05_10_*` migrations:
- `order_permit` — links to `project_type_id`
- `order_permit_department` — links to `order_permit`
- `order_permit_procedure` — links to `order_permit`
- `order_permit_tasks` — links to `order_permit`
- `order_permit_report_forms` — links to `order_permit`
- `order_permit_tasks_setting` — links to `order_permit_tasks`

---

### 3.3 Services & Repositories

#### `ProjectTypeCRUDService`

- `create(CreateProjectTypeDTO $dto): ProjectType`
- `list(int $page, int $perPage): array`
- `get(int $id): ProjectType`
- `getDirectChildren(int $parentId): Collection`
- `getRootProjectTypes(): Collection`
- `getProjectTypeWithChildren(int $id): ProjectType`
- `getProjectTypeWithSchemas(int $id): ProjectType`
- `getSecondLevelProjectTypes(): Collection`
- `createSecondLevelProjectType(CreateSecondLevelProjectTypeDTO $dto): ProjectType` — creates with schema IDs
- `getSchemasForProjectType(int $projectTypeId): Collection`
- `getProjectTypesByFilter(array $filters): Collection`

#### Setting Services

Each setting has its own service following the pattern:
- `show(int $projectTypeId): ?Setting`
- `update(int $projectTypeId, array $data): Setting` — uses `updateOrCreate`

Services: `ProjectDataSettingService`, `AttachmentContractSettingService`, `AttachmentTermsContractSettingService`, `ContractorContractSettingService`, `EmployeeContractSettingService`, `DepartmentContractSettingService`, `AttachmentCycleSettingService`, `ArchiveLibrarySettingService`, `RolesAndPermissionsSettingService`, `ProjectSharingSettingService`, `MaintenanceEmergencySettingService`

---

### 3.4 Controllers & API Endpoints

All routes prefixed with `api/v1/project-types`, protected by `auth:api` and `InitializeTenancyByRequestData`.

#### `ProjectTypeController`

| Method | Endpoint | Permission | Description |
|---|---|---|---|
| GET | `/` | `PROJECT_TYPE_LIST` | Paginated list |
| GET | `/filter` | `PROJECT_TYPE_LIST` | Filter by second_level, parent_id, is_have_schema, is_created |
| POST | `/` | `PROJECT_TYPE_CREATE` | Create root type |
| POST | `/second-level` | `PROJECT_TYPE_CREATE` | Create second-level type with schemas |
| PUT | `/second-level/{id}` | `PROJECT_TYPE_UPDATE` | Update second-level type |
| POST | `/export` | `PROJECT_TYPE_EXPORT` | Excel export |
| GET | `/roots` | `PROJECT_TYPE_LIST` | Root project types |
| GET | `/schemas` | (none) | List all schemas |
| GET | `/{id}` | `PROJECT_TYPE_VIEW` | Single type |
| GET | `/{id}/children` | `PROJECT_TYPE_VIEW` | Direct children |
| GET | `/{id}/schemas` | `PROJECT_TYPE_VIEW` | Schemas for second-level type |
| GET | `/{id}/second-level-schemas` | `PROJECT_TYPE_VIEW` | Same as above (alias) |
| PUT | `/{id}` | `PROJECT_TYPE_UPDATE` | Update type |
| DELETE | `/{id}` | `PROJECT_TYPE_DELETE` | Delete type |

#### Setting Controllers

Each setting has `show` and `update` endpoints:
```
GET  /{projectTypeId}/{setting-name}      → PROJECT_TYPE_VIEW
PUT  /{projectTypeId}/{setting-name}      → PROJECT_TYPE_UPDATE
```

Available settings endpoints:
- `/{projectTypeId}/data-settings`
- `/{projectTypeId}/attachment-contract-settings`
- `/{projectTypeId}/attachment-terms-contract-settings`
- `/{projectTypeId}/contractor-contract-settings`
- `/{projectTypeId}/employee-contract-settings`
- `/{projectTypeId}/department-contract-settings`
- `/{projectTypeId}/attachment-cycle-settings`
- `/{projectTypeId}/archive-library-settings`
- `/{projectTypeId}/roles-and-permissions-settings`
- `/{projectTypeId}/project-sharing-settings`
- `/{projectTypeId}/maintenance-emergency-settings`

#### Order Permit Routes

Registered separately at `api/v1` prefix via `order-permit.php` route file.

---

### 3.5 Observers

#### `ProjectTypeObserver`

Registered in `ProjectTypeServiceProvider`. Manages tree path maintenance and schema inheritance behaviour.

---

### 3.6 Seeders

#### `ProjectTypeSeeder`

Seeds initial project type hierarchy per tenant:
1. **Level 1**: "المشاريع الهندسيه" (Engineering Projects) — root, `is_created = false`, `is_have_schema = false`
2. **Level 2**: "التصاميم" (Designs) — child of root, `is_created = false`, `is_have_schema = true`

Uses `firstOrCreate` to be idempotent per company.

#### `SchemaSeeder`

Seeds schema definitions:
- ID 3: "المرفقات" (Attachments)
- ID 5: "المعنيين" (Stakeholders)
- ID 6: "اوامر العمل" (Work Orders)
- ID 9: "دورة الوثائق" (Document Cycle)
- ID 10: "الصلاحيات و الادوار" (Roles & Permissions)
- ID 11: "مشاركة المشروع" (Project Sharing)
- ID 12: "الصيانة والطوارئ" (Maintenance & Emergency)

Some schemas are commented out (1, 2, 4, 7, 8). After seeding, all schemas are attached to the "التصاميم" project type for the current tenant.

---

### 3.7 Presenters

#### `ProjectTypePresenter`

Presents project type with: id, name, icon, parent_id, path, is_created, is_have_schema, is_active, schemas (if loaded).

#### `SchemaPresenter`

Presents schema with: id, name.

---

### 3.8 Maintenance & Emergency Settings (Schema 12)

Schema 12 — "الصيانة والطوارئ" (Maintenance & Emergency) — uses a dedicated project-type-level setting to control whether the tab is exposed inside a project detail response.

#### Files Created

| File | Purpose |
|---|---|
| `Database/Migrations/2026_06_26_180000_create_maintenance_emergency_settings_table.php` | Creates `maintenance_emergency_settings` table |
| `Models/MaintenanceEmergencySetting.php` | Eloquent model with `project_type_id` and `is_shown` |
| `Repositories/MaintenanceEmergencySettingRepository.php` | Find/update by `project_type_id`, plus `getOrCreate` fallback |
| `Services/MaintenanceEmergencySettingService.php` | `getOrCreateByProjectTypeId()`, `update()` |
| `DTO/UpdateMaintenanceEmergencySettingDTO.php` | `is_shown` only DTO |
| `Commands/UpdateMaintenanceEmergencySettingCommand.php` | Command wrapper |
| `Handlers/UpdateMaintenanceEmergencySettingHandler.php` | Command handler |
| `Requests/UpdateMaintenanceEmergencySettingRequest.php` | Validates `is_shown` boolean |
| `Presenters/MaintenanceEmergencySettingPresenter.php` | Presents `id`, `project_type_id`, `is_shown`, timestamps |
| `Controllers/MaintenanceEmergencySettingController.php` | `show($projectTypeId)` and `update($projectTypeId)` |

#### Database

**Table**: `maintenance_emergency_settings`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | Auto-increment PK |
| `project_type_id` | unsignedBigInteger FK | → `project_types.id`, unique, cascade delete |
| `is_shown` | boolean | default `1` — controls schema visibility |
| `created_at`, `updated_at` | timestamps | |

#### API Endpoints

Prefix `api/v1/project-types`:

| Method | Endpoint | Permission | Description |
|---|---|---|---|
| GET | `/{projectTypeId}/maintenance-emergency-settings` | `PROJECT_TYPE_VIEW` | Get or auto-create the setting |
| PUT | `/{projectTypeId}/maintenance-emergency-settings` | `PROJECT_TYPE_UPDATE` | Update `is_shown` |

#### ProjectType Relationship

```php
public function maintenanceEmergencySetting()
{
    return $this->hasOne(MaintenanceEmergencySetting::class, 'project_type_id');
}
```

#### ProjectManagementPresenter Integration

The schema visibility is applied in `Modules\Project\ProjectManagement\Presenters\ProjectManagementPresenter`:

1. `ProjectManagementRepository::getProjectManagement()` eager-loads `subSubProjectType.maintenanceEmergencySetting`.
2. The presenter builds a `$schemaMapping` array including `12 => 'maintenance_emergency_setting'`.
3. The schema is only added to the `permissions` payload when:
   - `shouldIncludeSchema(12, $allowedSchemas)` passes (owner company or explicitly shared schema), **and**
   - the relationship is loaded, **and**
   - a setting exists, **and**
   - `$setting->is_shown` is truthy.

This means the maintenance & emergency tab can be hidden per project type even when schema 12 is technically assigned to that project type.

---

## 4. Sub-Module: TermServices

### Overview

`TermServices` is a simple lookup/reference module for term service names. It is **not tenant-scoped** (no `BelongsToTenant` trait, no `company_id` column).

> **Note**: The `TermServiceSetting` module (`modules/TermServiceSetting`) is a separate tenant-scoped module that groups `TermSetting` entries into named service configurations. It is documented in [Section 5a](#5a-sub-module-termservicesetting) below.

### Model: `TermServices` (table: `term_services`)

**File**: `Modules\Project\TermServices\Models\TermServices`

**Traits**: `HasFactory`, `BaseFilterable`

**Primary Key**: Auto-incrementing integer

**Fillable**: `name`, `is_active`

**Casts**: `is_active` → `int`

**Relationships**: `termSettings()` → `belongsToMany(TermSetting, 'term_setting_term_services', 'term_services_id', 'term_setting_id')`

**Scopes**: `active()` — `is_active = true`

### Database

**Table**: `term_services`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint | Auto-increment PK |
| `name` | string | unique |
| `is_active` | boolean | default true |
| `created_at`, `updated_at` | timestamps | |

### API Endpoints

All routes prefixed with `api/v1/term-services`, protected by `auth:api` (no tenancy middleware).

| Method | Endpoint | Description |
|---|---|---|
| GET | `/` | List all term services |
| POST | `/` | Create term service |
| POST | `/export` | Excel export |
| GET | `/{id}` | Show term service |
| PUT | `/{id}` | Update term service |
| DELETE | `/{id}` | Delete term service |

---

## 5. Sub-Module: TermSetting

### Overview

`TermSetting` manages hierarchical term settings linked to project types and term services. It is tenant-scoped and uses tree hierarchy.

### Model: `TermSetting` (table: `term_settings`)

**File**: `Modules\Project\TermSetting\Models\TermSetting`

**Traits**: `HasFactory`, `BaseFilterable`, `AsTree`, `BelongsToTenant`

**Primary Key**: Auto-incrementing integer

**Fillable Fields**:
```
name, description, parent_id, project_type_id, company_id, path, is_active
```

**Casts**: `is_active` → `int`

**Relationships**:
- `company()` → `belongsTo(Company)`
- `parent()` → `belongsTo(TermSetting, 'parent_id')` (via `AsTree`)
- `projectType()` → `belongsTo(ProjectType, 'project_type_id')`
- `termServices()` → `belongsToMany(TermServices, 'term_setting_term_services', 'term_setting_id', 'term_services_id')`

**Scopes**: `active()` — `is_active = true`

### Database

#### `term_settings` Table

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | Auto-increment PK |
| `name` | string | |
| `description` | text | nullable |
| `parent_id` | unsignedBigInteger FK | → `term_settings.id`, cascade delete (self-referencing) |
| `project_type_id` | unsignedBigInteger FK | → `project_types.id`, set null |
| `company_id` | UUID | nullable, indexed |
| `path` | string(500) | nullable, indexed — tree path |
| `is_active` | boolean | default true |
| `created_at`, `updated_at` | timestamps | |

#### `term_setting_term_services` Table (Pivot)

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | Auto-increment PK |
| `term_setting_id` | unsignedBigInteger FK | → `term_settings.id`, cascade delete |
| `term_services_id` | unsignedBigInteger FK | → `term_services.id`, cascade delete |
| `created_at`, `updated_at` | timestamps | |

**Unique Constraint**: `['term_setting_id', 'term_services_id']` (named `ts_tsrv_unique`)

### API Endpoints

All routes prefixed with `api/v1/term-settings`, protected by `auth:api` and `InitializeTenancyByRequestData`.

| Method | Endpoint | Permission | Description |
|---|---|---|---|
| GET | `/` | `TERM_SETTING_LIST` | List term settings |
| POST | `/` | `TERM_SETTING_CREATE` | Create term setting |
| POST | `/export` | `TERM_SETTING_EXPORT` | Excel export |
| GET | `/tree` | `TERM_SETTING_LIST` | Tree structure |
| GET | `/{id}/children` | `TERM_SETTING_VIEW` | Direct children |
| PUT | `/{id}/services` | `TERM_SETTING_UPDATE` | Update associated term services |
| PUT | `/{id}/status` | `TERM_SETTING_UPDATE` | Update active status |
| GET | `/{id}` | `TERM_SETTING_VIEW` | Single term setting |
| PUT | `/{id}` | `TERM_SETTING_UPDATE` | Update term setting |
| DELETE | `/{id}` | `TERM_SETTING_DELETE` | Delete term setting |

### Presenters

- `TermSettingPresenter` — standard detail/listing presenter
- `TermSettingTreePresenter` — tree-structured presentation for hierarchical display

---

## 5a. Sub-Module: TermServiceSetting

### Overview

`TermServiceSetting` is a **separate module** (located at `modules/TermServiceSetting`, not under `modules/Project/`) that groups `TermSetting` entries into named service configurations. It is tenant-scoped and links to the `TermSetting` tree via a pivot table. It is used by the `ClientRequest` module (`ClientRequestServiceTerm`) to associate client request services with term settings.

### Model: `TermServiceSetting` (table: `term_service_settings`)

**File**: `Modules\TermServiceSetting\Models\TermServiceSetting`

**Traits**: `HasFactory`, `BaseFilterable`, `BelongsToTenant`

**Primary Key**: Auto-incrementing integer

**Fillable Fields**: `name`, `company_id`

**Relationships**:
- `company()` → `belongsTo(Company)`
- `termSettings()` → `belongsToMany(TermSetting, 'term_service_setting_term_setting', 'term_service_setting_id', 'term_setting_id')->withTimestamps()`

### Database

#### `term_service_settings` Table

**Created**: `2026_02_26_200330_create_term_service_settings_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | Auto-increment PK |
| `name` | string | |
| `company_id` | string | indexed — tenant company |
| `created_at`, `updated_at` | timestamps | |

#### `term_service_setting_term_setting` Table (Pivot)

**Created**: `2026_02_26_200343_create_term_service_setting_term_setting_table.php`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | Auto-increment PK |
| `term_service_setting_id` | unsignedBigInteger | indexed — FK to `term_service_settings.id` |
| `term_setting_id` | unsignedBigInteger | indexed — FK to `term_settings.id` |
| `created_at`, `updated_at` | timestamps | |

**Unique Constraint**: `['term_service_setting_id', 'term_setting_id']` (named `tss_ts_unique`)

### DTOs

#### `CreateTermServiceSettingDTO`

Constructor: `string $name, array $termSettingIds = []`  
`toArray()` returns `['name' => $this->name]`

#### `UpdateTermServiceSettingDTO`

Constructor: `int $id, string $name, array $termSettingIds = []`  
`toArray()` returns `['name' => $this->name]`

### Service: `TermServiceSettingCRUDService`

- `create(CreateTermServiceSettingDTO $dto): TermServiceSetting` — creates with term setting IDs (sync)
- `update(UpdateTermServiceSettingDTO $dto): TermServiceSetting` — updates and syncs term settings
- `list(int $page, int $perPage): array` — paginated
- `get(int $id): TermServiceSetting`
- `getAll(): Collection`

### Repository: `TermServiceSettingRepository`

Extends `BaseRepository`. Key methods:
- `getTermServiceSettingList(?int $page, ?int $perPage): Collection` — loads `termSettings.parent`
- `getAllTermServiceSettings(): Collection` — loads `termSettings` with deep nesting (`parent.parent.parent.parent`, `children.children.children.children`)
- `getTermServiceSetting(int $id): TermServiceSetting` — same deep nesting
- `createTermServiceSetting(array $data, array $termSettingIds): TermServiceSetting` — transactional, sets `company_id` from `tenant('company_id')`, syncs term settings
- `updateTermServiceSetting(int $id, array $data, array $termSettingIds): TermServiceSetting` — transactional, syncs term settings
- `deleteTermServiceSetting(int $id): bool` — **blocks deletion** if `ClientRequestServiceTerm` records reference this setting; detaches pivot before deleting

### Controller & API Endpoints

All routes prefixed with `api/v1/term-service-settings`, protected by `auth:api` and `InitializeTenancyByRequestData`.

| Method | Endpoint | Permission | Description |
|---|---|---|---|
| GET | `/` | `TERM_SERVICE_SETTING_LIST` | Paginated list |
| GET | `/all` | `TERM_SERVICE_SETTING_LIST` | All (unpaginated) with deep tree |
| POST | `/` | `TERM_SERVICE_SETTING_CREATE` | Create with term settings |
| POST | `/export` | `TERM_SERVICE_SETTING_EXPORT` | Excel export |
| GET | `/{id}` | `TERM_SERVICE_SETTING_VIEW` | Single with deep tree |
| PUT | `/{id}` | `TERM_SERVICE_SETTING_UPDATE` | Update with term settings |
| DELETE | `/{id}` | `TERM_SERVICE_SETTING_DELETE` | Delete (blocks if in use) |

### Presenter: `TermServiceSettingPresenter`

Presents `TermServiceSetting` with: id, name, created_at, updated_at.  
In detail mode, includes `children` — a tree structure built from associated `TermSetting` entries:
- Finds root terms by traversing `parent` chain
- Recursively builds tree with `children` nesting
- Each node: id, name, description, parent_id, is_active, children[]

### Config: Permissions

**File**: `Modules\TermServiceSetting\Config\permissions.php`

```php
'TERM_SERVICE_SETTING_LIST' => 'client-relations.client-setting*term-service-settings.list',
'TERM_SERVICE_SETTING_VIEW' => 'client-relations.client-setting*term-service-settings.view',
'TERM_SERVICE_SETTING_CREATE' => 'client-relations.client-setting*term-service-settings.create',
'TERM_SERVICE_SETTING_UPDATE' => 'client-relations.client-setting*term-service-settings.update',
'TERM_SERVICE_SETTING_DELETE' => 'client-relations.client-setting*term-service-settings.delete',
'TERM_SERVICE_SETTING_EXPORT' => 'client-relations.client-setting*term-service-settings.export',
```

### Cross-Module Dependencies

- **TermSetting**: `termSettings()` belongsToMany → `Modules\Project\TermSetting\Models\TermSetting`
- **ClientRequest**: Deletion guarded by `ClientRequestServiceTerm` table — cannot delete if referenced by client requests
- **Company**: `belongsTo(Company)` for tenant ownership

---

## 6. Shared: ResourceShare System

### Overview

The `Shared\ResourceShare` module provides polymorphic resource sharing infrastructure. It is used by `ProjectManagement` via the `Shareable` trait but can be extended to other models.

### Model: `ResourceShare` (table: `resource_shares`)

**File**: `Modules\Shared\ResourceShare\Models\ResourceShare`

**Traits**: `UuidTrait`

**Fillable Fields**:
```
shareable_type, shareable_id, owner_company_id, shared_with_company_id,
type_id, relation_id, role_id, status, schema_ids,
shared_by_user_id, responded_by_user_id, responded_at, notes
```

**Casts**: `schema_ids` → `array`, `responded_at` → `datetime`, all IDs → `string`

**Relationships**:
- `shareable()` → `morphTo()`
- `ownerCompany()` → `belongsTo(Company, 'owner_company_id')->withoutGlobalScopes()`
- `sharedWithCompany()` → `belongsTo(Company, 'shared_with_company_id')->withoutGlobalScopes()`
- `sharedByUser()` → `belongsTo(User, 'shared_by_user_id')->withoutGlobalScopes()`
- `respondedByUser()` → `belongsTo(User, 'responded_by_user_id')->withoutGlobalScopes()`
- `type()`, `relation()`, `role()` → `belongsTo(ProjectShareType, ...)` — metadata about the share relationship

**Status Methods**: `isPending()`, `isAccepted()`, `isRejected()`, `accept(string $userId)`, `reject(string $userId)`

### `Shareable` Trait

**File**: `app\Traits\Shareable.php`

**Global Scope**: `shareable` — automatically includes resources where:
- `company_id = tenant('id')` (owned), OR
- Has an accepted share with `shared_with_company_id = tenant('id')`

**Methods**:
- `shares(): MorphMany` — all shares
- `acceptedShares(): MorphMany` — only accepted shares
- `pendingShares(): MorphMany` — only pending
- `rejectedShares(): MorphMany` — only rejected
- `isOwnedByCurrentCompany(): bool`
- `isSharedWith(string $companyId): bool`
- `shareWith(string $companyId, ?array $schemaIds, ?string $userId, ?string $notes): ResourceShare`
- `getSharingStatus(string $companyId): ?string`
- `scopeOwnedOnly(Builder $query): Builder` — removes global scope, filters by company_id
- `scopeSharedOnly(Builder $query): Builder` — removes global scope, filters by accepted shares

### `ResourceShareService`

**File**: `Modules\Shared\ResourceShare\Services\ResourceShareService`

- `shareResource(...)` — creates a pending share, broadcasts `ResourceShared` event
- `acceptShare(string $shareId)` — accepts share, broadcasts `ResourceShareResponded`
- `rejectShare(string $shareId)` — rejects share, broadcasts `ResourceShareResponded`
- `getPendingInvitations(): Collection`
- `getAcceptedShares(): Collection`
- `getSharesForResource(string $shareableType, string $shareableId): Collection`
- `removeShare(string $shareId): bool` — owner only
- `getSharedResources(string $shareableType): Collection`
- `updateShareSchemas(string $shareId, array $schemaIds): bool` — owner only

### Events

- `ResourceShared` — broadcast to shared company's channel
- `ResourceShareResponded` — broadcast to owner company's channel with action (`accepted`/`rejected`)

---

## 7. Project Config (Global Permissions)

**File**: `Modules\Project\Config\permissions.php`

This is the **global** permissions config for the Project module (distinct from the project-specific permissions in `ProjectManagement\Resources\config\config.php`). These permissions are used with the global `RoleAndPermission` module's `Permission` enum and the `->permission()` route middleware.

```php
return [
    'permissions' => [
        // Project Management
        'PROJECT_MANAGEMENT_LIST' => 'work-panel.project-management*project-management.list',
        'PROJECT_MANAGEMENT_VIEW' => 'work-panel.project-management*project-management.view',
        'PROJECT_MANAGEMENT_CREATE' => 'work-panel.project-management*project-management.create',
        'PROJECT_MANAGEMENT_UPDATE' => 'work-panel.project-management*project-management.update',
        'PROJECT_MANAGEMENT_DELETE' => 'work-panel.project-management*project-management.delete',
        'PROJECT_MANAGEMENT_EXPORT' => 'work-panel.project-management*project-management.export',

        // Project Type
        'PROJECT_TYPE_LIST' => 'work-panel.project-settings*project-type.list',
        'PROJECT_TYPE_VIEW' => 'work-panel.project-settings*project-type.view',
        'PROJECT_TYPE_CREATE' => 'work-panel.project-settings*project-type.create',
        'PROJECT_TYPE_UPDATE' => 'work-panel.project-settings*project-type.update',
        'PROJECT_TYPE_DELETE' => 'work-panel.project-settings*project-type.delete',
        'PROJECT_TYPE_EXPORT' => 'work-panel.project-settings*project-type.export',

        // Term Setting
        'TERM_SETTING_LIST' => 'client-relations.client-setting*term-setting.list',
        'TERM_SETTING_VIEW' => 'client-relations.client-setting*term-setting.view',
        'TERM_SETTING_CREATE' => 'client-relations.client-setting*term-setting.create',
        'TERM_SETTING_UPDATE' => 'client-relations.client-setting*term-setting.update',
        'TERM_SETTING_DELETE' => 'client-relations.client-setting*term-setting.delete',
        'TERM_SETTING_EXPORT' => 'client-relations.client-setting*term-setting.export',

        // Term Service Setting (separate module, same permission namespace)
        'TERM_SERVICE_SETTING_LIST' => 'client-relations.client-setting*term-service-settings.list',
        'TERM_SERVICE_SETTING_VIEW' => 'client-relations.client-setting*term-service-settings.view',
        'TERM_SERVICE_SETTING_CREATE' => 'client-relations.client-setting*term-service-settings.create',
        'TERM_SERVICE_SETTING_UPDATE' => 'client-relations.client-setting*term-service-settings.update',
        'TERM_SERVICE_SETTING_DELETE' => 'client-relations.client-setting*term-service-settings.delete',
        'TERM_SERVICE_SETTING_EXPORT' => 'client-relations.client-setting*term-service-settings.export',
    ],
];
```

### Two Permission Systems

| Aspect | Global Permissions | Project-Specific Permissions |
|---|---|---|
| **Config** | `Modules\Project\Config\permissions.php` | `Modules\Project\ProjectManagement\Resources\config\config.php` |
| **Config Key** | Loaded via `Permission` enum | `config('project-management.permissions')` |
| **Scope** | System-wide (all projects) | Per-project (scoped to a single project) |
| **Middleware** | `->permission(Permission::PROJECT_MANAGEMENT_LIST())` | `->middleware('project.permission:PROJECT_EMPLOYEE_CREATE')` |
| **Models** | `RoleAndPermission\Models\Role`, `Permission` | `ProjectRole`, `ProjectPermission` |
| **Tables** | `roles`, `permissions`, `role_permissions` | `project_roles`, `project_permissions`, `project_role_permissions` |
| **Auto-creation** | No | Yes — "Project Admin" role auto-created on project creation |

---

## 8. Cross-Module Relationships

```
Modules\Project
├── ProjectManagement (projects, project_roles, project_permissions, project_employees, attachment_requests, project_notifications, ...)
│   ├── Uses Shareable trait → Shared\ResourceShare (resource_shares)
│   ├── References ProjectType (project_types) via project_type_id, sub_project_type_id, sub_sub_project_type_id
│   ├── References User (users) via manager_id, created_by_user_id
│   ├── References Company (companies) via company_id
│   ├── References Branch, Currency, ManagementHierarchy, Client
│   ├── References ArchiveLibrary\Folder, ArchiveLibrary\File (for attachment saving)
│   └── ProjectNotification → EmployeeTask (EmployeeTaskRequest) via employee_task_request_id
│       ├── Uses InternalProcessForm::CreateProjectNotificationTask form key (conditions limited to InsideCustomLocations)
│       ├── Mobile confirm-receive uses InternalProcessForm::ConfirmProjectNotificationPresence form key
│       ├── Procedure steps may use ActionTakerType::AssignedUser to send the task to the assigned employee
│       └── Mobile lifecycle (confirm-receive / start / end / take-action) delegates to EmployeeTask services
├── ProjectType (project_types, project_schemas, project_type_schemas, *_settings, order_permit_*)
│   ├── Self-referencing tree (parent_id)
│   ├── References Company via company_id
│   └── Has many setting models (ProjectDataSetting, ArchiveLibrarySetting, etc.)
├── TermServices (term_services)
│   └── belongsToMany TermSetting via term_setting_term_services
├── TermSetting (term_settings, term_setting_term_services)
│   ├── Self-referencing tree (parent_id)
│   ├── References ProjectType via project_type_id
│   ├── References Company via company_id
│   └── belongsToMany TermServices via term_setting_term_services
├── TermServiceSetting (term_service_settings, term_service_setting_term_setting) [separate module]
│   ├── References Company via company_id
│   ├── belongsToMany TermSetting via term_service_setting_term_setting
│   └── Referenced by ClientRequest (ClientRequestServiceTerm) — blocks deletion
└── Config\permissions.php (global permission mappings)
```

---

## 9. Architecture Patterns & Conventions

### Layered Architecture

```
HTTP Request
    ↓
FormRequest (validation)
    ↓
DTO / Command (data transfer)
    ↓
Controller (orchestration)
    ↓
Service (business logic)
    ↓
Repository (data access)
    ↓
Model (Eloquent)
    ↓
Presenter (response formatting)
    ↓
JSON Response (via Json::item / Json::items)
```

### Key Conventions

- **UUID vs Integer PKs**: `ProjectManagement` models use UUID (`UuidTrait`); `ProjectType`, `TermServices`, `TermSetting` use auto-incrementing integers.
- **Tenant Isolation**: `BelongsToTenant` trait + `InitializeTenancyByRequestData` middleware. Some models use `withoutGlobalScopes()` for cross-tenant access (e.g., shared project relationships).
- **Observers for Side Effects**: Project creation triggers folder creation, admin role creation, and employee assignment — all in the observer.
- **Config-Driven Permissions**: Project-specific permissions are defined in config, seeded into the database, and resolved by the middleware from config keys. This allows changing permission names without touching route definitions.
- **Schema-Based Feature Toggles**: `ProjectType` settings control which features are available for projects of that type. When a project is shared, `schema_ids` on the `ResourceShare` controls which features the receiver can access.
- **Presenter Pattern**: All API responses go through presenters that transform model data into arrays, with support for listing vs detail modes.
- **Filter Pattern**: `BaseFilterable` trait + custom filter classes extending `SearchModelFilter` for query parameter-based filtering.

---

## 10. Installation & Setup

### Migrations

```bash
php artisan migrate
```

Key migration files are split between:
- `modules/Project/ProjectManagement/Database/Migrations/` — projects table, serial number, indexes
- `modules/Project/ProjectType/Database/Migrations/` — project_types, schemas, settings, order permits
- `modules/Project/TermServices/Database/Migrations/` — term_services
- `modules/Project/TermSetting/Database/Migrations/` — term_settings, pivot
- `modules/TermServiceSetting/Database/Migrations/` — term_service_settings, term_service_setting_term_setting pivot
- `database/migrations/` — project_roles, project_permissions, project_role_permissions, project_employees, resource_shares, attachment_requests, attachment_request_items, attachment_request_history

### Seeders

```bash
# Seed project types (per tenant)
php artisan db:seed --class=Modules\\Project\\ProjectType\\Database\\Seeders\\ProjectTypeSeeder

# Seed schemas (per tenant)
php artisan db:seed --class=Modules\\Project\\ProjectType\\Database\\Seeders\\SchemaSeeder

# Seed project permissions (global, runs for current tenant)
php artisan db:seed --class=Modules\\Project\\ProjectManagement\\Database\\Seeders\\ProjectPermissionsSeeder
```

### Artisan Commands

```bash
# Update/sync project permission names from config
php artisan project-permissions:update-names

# Dry run to preview changes
php artisan project-permissions:update-names --dry-run

# Force update + delete orphaned + create missing
php artisan project-permissions:update-names --force --delete-orphaned --create-missing
```

---

## 11. Troubleshooting

### Common Issues

**Issue**: "Permission key not found in project-management config"  
**Cause**: A permission key used in code is not defined in `config('project-management.permissions')`.  
**Fix**: Add the missing key to `Modules\Project\ProjectManagement/Resources/config/config.php` and run the seeder.

**Issue**: "Default Project Admin role cannot be updated/deleted"  
**Cause**: Attempting to modify the auto-created admin role (`is_default = true`).  
**Fix**: This is by design. Create a new custom role instead.

**Issue**: Shared projects not appearing in list  
**Cause**: The `Shareable` global scope requires `tenant('id')` to be set.  
**Fix**: Ensure `InitializeTenancyByRequestData` middleware is applied and tenant is properly initialized.

**Issue**: Project-specific permissions not working  
**Cause**: Middleware alias `project.permission` not registered, or permission not seeded.  
**Fix**: Ensure `ProjectManagementServiceProvider` is registered. Run `ProjectPermissionsSeeder`. Clear config cache with `php artisan config:clear`.

**Issue**: Attachment request media not saving to receiver's folder  
**Cause**: Cross-tenant media replication requires tenant context switching.  
**Fix**: Verify that `tenancy()->initialize()` calls succeed and the receiver tenant exists.

**Issue**: Config key mismatch (`projectmanagement` vs `project-management`)  
**Cause**: The module alias is `projectmanagement` (no hyphen), but the config is loaded as `project-management`.  
**Fix**: The seeder uses `config('projectmanagement.permissions')` while the middleware and enum use `config('project-management.permissions')`. Ensure both resolve correctly. Run `php artisan config:clear` after changes.

---

## 12. Frontend / UI Mapping

This section maps the Project module screens to the backend models, APIs, and permissions described earlier. The screenshots below were captured from the Constrix web application (Arabic UI).

---

### 12.1 Project Type Settings Screen

**Screen**: `المشاريع الهندسية` (Engineering Projects) — Project Type configuration page.

**Visible Tabs (top)**:
- **المرفقات** (Attachments)
- **أصحاب المصلحة** (Stakeholders)
- **دورة الوثائق** (Document Cycle)

**Visible Tabs (additional row)**:
- **المعنيين** (Concerned Parties)
- **الأدوار والصلاحيات** (Roles & Permissions)
- **الجهات المشاركة** (Participating Parties)
- **الكادر** (Cadre / Staff)

**Backend Mapping**:
- Each tab maps to one or more `ProjectType` setting models:
  - **المرفقات** → `AttachmentContractSetting`, `AttachmentTermsContractSetting`, `AttachmentCycleSetting`
  - **أصحاب المصلحة** → `ProjectDataSetting` (client/stakeholder visibility flags)
  - **دورة الوثائق** → `ArchiveLibrarySetting`, `AttachmentCycleSetting`
  - **المعنيين** → `EmployeeContractSetting`, `DepartmentContractSetting`
  - **الأدوار والصلاحيات** → `RolesAndPermissionsSetting`
  - **الجهات المشاركة** → `ProjectSharingSetting`
  - **الكادر** → `ContractorContractSetting`, `DepartmentContractSetting`
- The toggle switches visible in each tab correspond to boolean fields in the setting tables (e.g., `is_all_data_visible`, `is_reference_number`, `is_client`, etc.).
- The **إظهار جميع مرفقات المشروع** toggle maps to an `is_all_data_visible` flag.
- APIs used: `GET /api/v1/project-types/{id}/{setting-name}`, `PUT /api/v1/project-types/{id}/{setting-name}`.
- Permission: `PROJECT_TYPE_VIEW` / `PROJECT_TYPE_UPDATE`.

---

### 12.2 Project Dashboard / List Screen

**Screen**: Projects list with statistics cards and data table.

**Statistics Cards**:
- **إجمالي المشاريع** (Total Projects) — count of all projects
- **إجمالي القيمة** (Total Value) — sum of `project_value`
- **المشاريع النشطة** (Active Projects) — count where `status = 1`
- **المشاريع غير النشطة** (Inactive Projects) — count where `status != 1`

**Backend Mapping**:
- `ProjectManagementDashboardWidgetsService::getWidgetsData()` returns these four widgets with current/previous period comparison and percentage trend.
- Data source: `projects` table, filtered by `company_id`.

**Filters**:
- **التصنيف والحالة** (Classification & Status) dropdowns
- **بحث** (Search) text field
- **تصنيف المشروع** (Project Classification)
- **حالة المشروع** (Project Status)

**Backend Mapping**:
- `ProjectManagementFilter` supports `name`, `projectTypeId`, `subProjectTypeId`, `subProjectTypeId`, `managerId`, `branchId`, `projectOwnerType`, `projectOwnerId`, `contractId`, `clientId`, `managementId`, `status`.
- Listing API: `GET /api/v1/projects` with query params.

**Table Columns**:
- الرقم (Serial), اسم المشروع, اسم العميل, نوع المشروع, تصنيف المشروع, الفرع, التصنيف الفرعي, مدير المشروع, المسؤول, رقم العقد, بداية المشروع, نهاية التقدم, الإنجاز, حالة المشروع, عرض, الإجراءات

**Backend Mapping**:
- `ProjectManagementPresenter` in listing mode returns flattened fields such as `project_type_name`, `manager_name`, `client_name`, `branch_name`, `status_label`.
- `serial_number` comes from `ProjectManagementObserver::creating()`.
- Actions: view, edit, delete (controlled by `PROJECT_MANAGEMENT_VIEW`, `PROJECT_MANAGEMENT_UPDATE`, `PROJECT_MANAGEMENT_DELETE`).

---

### 12.3 Add Project Modal

**Screen**: `إضافة مشروع` (Add Project) side modal.

**Visible Fields**:
- **نوع المشروع** (Project Type) — dropdown
- **النوع الفرعي للمشروع** (Sub Project Type) — dropdown
- **النوع الفرعي الثانوي للمشروع** (Sub-Sub Project Type) — dropdown
- **اسم المشروع** (Project Name)
- **الفرع** (Branch)
- **الإدارة** (Management)
- **مدير الإدارة** (Management Manager)
- **مدير المشروع** (Project Manager)
- **مالك المشروع** (Project Owner) — radio: `فرد` (Individual) / `جهة` (Entity)
- **الارتباط التعاقدي** (Contractual Relationship)
- **وسوم المشروع** (Project Tags)

**Backend Mapping**:
- All fields map to `CreateProjectManagementDTO` constructor parameters.
- Project type dropdowns load from `ProjectTypeController::roots`, `children`, and `second-level` endpoints.
- Branch, management, and manager dropdowns load from their respective modules (`Branch`, `ManagementHierarchy`, `User`).
- Project owner polymorphic fields (`project_owner_type`, `project_owner_id`) are set based on the `فرد` / `جهة` selection.
- API: `POST /api/v1/projects` with `PROJECT_MANAGEMENT_CREATE` permission.
- Observer `ProjectManagementObserver::created()` auto-generates serial number, creates ArchiveLibrary folder, and creates the default Project Admin role.

---

### 12.4 Project Details — Employees (المعنيين) Tab

**Screen**: Inside a project, the **المعنيين** tab shows assigned employees and stakeholders.

**Statistics Cards**:
- **بانتظار الرد** (Pending Response)
- **مقبول** (Accepted)
- **مرفوض** (Rejected)
- **الإجمالي** (Total)

**Backend Mapping**:
- These counts come from `ProjectEmployee` and `ProjectShare` / `ResourceShare` status aggregations.
- `ProjectEmployeeController::getProjectEmployees()` returns the list.
- `ResourceShareService::getPendingInvitations()`, `getAcceptedShares()`, etc. provide share counts.

**Buttons**:
- **أضافة صاحب مصلحة** (Add Stakeholder) — `POST /api/v1/projects/{project_id}/employees`
- **تصدير** (Export) — `POST /api/v1/projects/{project_id}/employees/export`
- **البحث في المشاركات** (Search)

**Table Columns**:
- اسم الشركة, النوع, العلاقة, الدور, البريد الإلكتروني, رقم الجوال, ممثل الشركة, تاريخ الإرسال, حالة الطلب, الإجراءات

**Backend Mapping**:
- Each row represents a `ProjectEmployee` record joined with `User` and `ProjectRole`.
- `ProjectEmployeePresenter` formats: user (id/name/email), project_role (id/name/slug/is_default), assigned_at, assigned_by.
- For external/shared companies, `ResourceShare` records are also displayed with status (`pending`, `accepted`, `rejected`).
- Status badge colors: pending = yellow, accepted = green, rejected = red.
- Permission: `PROJECT_EMPLOYEE_LIST`, `PROJECT_EMPLOYEE_CREATE`, `PROJECT_EMPLOYEE_DELETE`, `PROJECT_EMPLOYEE_UPDATE`.

---

### 12.5 Project Details — Shared Companies (الجهات المشاركة) Tab

**Screen**: Project sharing management.

**Statistics Cards**: Same as employees tab — Pending, Accepted, Rejected, Total.

**Buttons**:
- **دعوة شركة** (Invite Company) — opens share modal
- **ترتيب** (Sort)
- **تصدير** (Export)

**Table Columns**:
- اسم الشركة, النوع, العلاقة, الدور, البريد الإلكتروني, رقم الجوال, ممثل الشركة, تاريخ الإرسال, حالة الطلب, الإجراءات

**Backend Mapping**:
- Data source: `ResourceShare` polymorphic records where `shareable_type = ProjectManagement` and `shareable_id = project_id`.
- APIs: `POST /api/v1/projects/{project_id}/share`, `GET /api/v1/projects/{project_id}/shared-companies`, `POST /api/v1/projects/shares/{share_id}/accept`, `POST /api/v1/projects/shares/{share_id}/reject`, `DELETE /api/v1/projects/shares/{share_id}`.
- `schema_ids` array controls which ProjectType feature tabs the shared company can see (e.g., attachments, roles, document cycle).
- Email notification: `ProjectShareMail` sent to receiver company owner.
- Permission: `PROJECT_SHARE_CREATE`, `PROJECT_SHARE_UPDATE`, `PROJECT_SHARE_DELETE`.

---

### 12.6 Project Details — Document Cycle (دورة الوثائق) Tab

**Screen**: Attachment requests / document cycle list.

**Filters**:
- **تاريخ النهاية** (End Date)
- **النوع** (Type)
- **الجهة** (Party)
- **التجاهة** (Direction)

**Buttons**:
- **اضافة ملف** (Add File) — creates new attachment request
- **تصدير** (Export)

**Table Columns**:
- النوع, رقم التسلسل, المرسل, الجهة, اسم المستند, حجم الملف, عدد المستندات, اخر نشاط, الحالة, الإجراءات

**Backend Mapping**:
- Data source: `AttachmentRequest` and `AttachmentRequestItem` models.
- APIs: `POST /api/v1/projects/{project_id}/attachment-requests`, `GET /api/v1/projects/attachment-requests`, `GET /api/v1/projects/{project_id}/attachment-requests/outgoing`, `GET /api/v1/projects/{project_id}/attachment-requests/incoming`, `GET /api/v1/projects/{project_id}/attachment-requests/pending`.
- Status values: `pending`, `semi-approved`, `approved`, `declined`, `update_requested`.
- Items are stored via Spatie Media Library in the `attachments` collection.
- `AttachmentRequestPresenter` includes statistics: total/approved/declined/pending/update_requested counts and history log.
- Email notification: `AttachmentRequestMail` sent to receiver company owner.
- Permission: `PROJECT_ARCHIVE_CREATE`, `PROJECT_ARCHIVE_VIEW`, `PROJECT_ARCHIVE_UPDATE`, `PROJECT_ARCHIVE_DELETE`.

---

### 12.7 UI-to-API Quick Reference

| UI Screen | Primary API Endpoint | Permission |
|---|---|---|
| Project Type Settings tabs | `GET/PUT /api/v1/project-types/{id}/{setting}` | `PROJECT_TYPE_VIEW` / `PROJECT_TYPE_UPDATE` |
| Project dashboard cards | `GET /api/v1/projects/widgets` | `PROJECT_MANAGEMENT_LIST` |
| Project list table | `GET /api/v1/projects` | `PROJECT_MANAGEMENT_LIST` |
| Add project modal | `POST /api/v1/projects` | `PROJECT_MANAGEMENT_CREATE` |
| Project employees tab | `GET /api/v1/projects/{id}/employees` | `PROJECT_EMPLOYEE_LIST` |
| Project shared companies tab | `GET /api/v1/projects/{id}/shared-companies` | `PROJECT_SHARE_LIST` |
| Document cycle tab | `GET /api/v1/projects/attachment-requests` | `PROJECT_ARCHIVE_LIST` |
| Project notifications list | `GET /api/v1/projects/notifications` | `PROJECT_NOTIFICATION_LIST` |
| Create project notification | `POST /api/v1/projects/notifications` | `PROJECT_NOTIFICATION_CREATE` |
| Nearest employees selector | `GET /api/v1/projects/notifications/employees-with-locations` | `PROJECT_NOTIFICATION_CREATE` |
| Mobile: my notifications | `GET /api/v1/projects/notifications/my-tasks` | `PROJECT_NOTIFICATION_LIST` |
| Mobile: inbox (confirm-receive) | `GET /api/v1/projects/notifications/my-inbox` | `PROJECT_NOTIFICATION_LIST` |
| Mobile: inbox counts | `GET /api/v1/projects/notifications/my-inbox-counts` | `PROJECT_NOTIFICATION_LIST` |
| Mobile: filter metadata | `GET /api/v1/projects/notifications/filters` | `PROJECT_NOTIFICATION_LIST` |
| Mobile: confirm-receive task | `POST /api/v1/projects/notifications/{id}/confirm-receive` | `PROJECT_NOTIFICATION_UPDATE` |
| Mobile: start notification task (legacy) | `POST /api/v1/projects/notifications/{id}/start` | `PROJECT_NOTIFICATION_UPDATE` |
| Mobile: available actions | `GET /api/v1/projects/notifications/{id}/available-actions` | `PROJECT_NOTIFICATION_VIEW` |
| Mobile: take procedure action (update) | `POST /api/v1/projects/notifications/{id}/take-action` | `PROJECT_NOTIFICATION_UPDATE` |
| Mobile: end notification task | `POST /api/v1/projects/notifications/{id}/end` | `PROJECT_NOTIFICATION_UPDATE` |

---

### 12.8 UI Notes for Developers

- **RTL Layout**: All UI labels are Arabic; the API returns translated titles via `ProjectPermissionLookupPresenter` (ar/en fallback).
- **Schema-Driven Tabs**: Project detail tabs are enabled/disabled based on `ProjectType` schema settings and the `allowed_schemas` array on shared projects.
- **Status Badges**: Use the `status` field directly from presenters; no extra mapping is required.
- **Polymorphic Owner**: The Add Project modal shows `فرد`/`جهة` radio; store as `project_owner_type` + `project_owner_id`.
- **Sharing Visibility**: The `Shareable` global scope ensures shared projects appear in the project list without separate "shared with me" API calls, although a dedicated endpoint exists.
