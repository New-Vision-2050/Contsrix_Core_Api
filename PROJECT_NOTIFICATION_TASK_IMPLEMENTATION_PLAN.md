# Project Notification / Task Feature — Implementation Plan

> **Version**: 1.3 (verified line-by-line against actual source files; breaking errors corrected)  
> **Scope**: Backend architecture, frontend integration, and data model design for a new **الصيانة والطوارئ** (Maintenance & Emergencies) tab inside Project Details, whose first sub-view is **الإشعارات** (Notifications). Captures all design-session decisions.  
> **Related Modules**: `ProjectManagement`, `ProjectType`, `EmployeeTask` (in-app: **مهام العمل المسندة** / Assigned Work Tasks), `Attendance`, `ProcedureSetting`, `Process`, `User`.

> ### Changelog v1.2 → v1.3 (verified against source)
> - **A1**: `GeoDistance` exposes only `metres()` — replaced all `haversineMeters()` / `haversineKm()` references.
> - **A2**: `EmployeeTaskRequestService::create()` hardcodes `CreateTask` in **three** places (`previewResponsibles`, `checkCreateTaskConditions`, `markCreateTaskProceduresTaken`). The `$formKey` parameter must be threaded into all three.
> - **A3**: `ProcedureSetting` has **no `code` and no `apply_on`** columns — parent is identified by `type` + `name` + `parent_id IS NULL`; child by `form` + `parent_id`.
> - **A4**: Corrected the enum path; only `modules/Shared/InternalProcessType/Enums/InternalProcessForm.php` exists.
> - **C.1 (confirmed by user)**: This is a **project-details schema tab** (same mechanism as المرفقات / أصحاب المصلحة / دورة الوثائق). The new top-level tab is **الصيانة والطوارئ**; **الإشعارات** is its first sub-view. The linked runtime task flows through the existing **مهام العمل المسندة** (EmployeeTask) module.
> - **D**: No `EmployeeTaskStatusChanged` event exists → status sync uses an Eloquent `updated` observer. `ExtendTask` does **not** exist in the enum. Added `severity` field (UI badge `منخفض`/`متوسط`). Corrected the §4.4 validation overstatement.

---

## 1. Executive Summary

We are adding a new **Project Notifications** (إشعارات المشروع) capability inside the project-details screen. The feature is a dashboard-driven variant of the existing **EmployeeTask** module:

- A project manager opens a project, navigates to the new **Notifications** tab, and clicks **Add Notification** (إضافة إشعار).
- A 5-step wizard collects notification metadata, contractor details, task location, and selects the assigned employee.
- The employee is selected from a live map that shows employees currently assigned to the project, their live GPS location from the Attendance tracking system, and the distance from each employee to the notification location.
- On submission, the system creates:
  1. A `ProjectNotification` record (dashboard-specific metadata and wizard fields).
  2. A linked `EmployeeTaskRequest` of type `project_notification` (so the notification flows through the existing EmployeeTask workflow engine).
- The notification uses a dedicated `ProcedureSetting` for project-notification tasks, enabling configurable approval/assignment steps.

This plan covers the backend model, migrations, APIs, services, permissions, procedure integration, and the frontend wizard/map design.

### 1.1 Session Design Notes (from chat discussion)

The following decisions were made during the design session and are reflected in this plan:

- **New tab in project details (confirmed by user — C.1)**: This is the **same schema-driven tab mechanism** used by the existing project-detail tabs (المرفقات / أصحاب المصلحة / دورة الوثائق). The new top-level tab is **الصيانة والطوارئ** (Maintenance & Emergencies); **الإشعارات** (Notifications) is its first sub-view (the tab also hosts المخالفات / التقارير / المؤشرات in the full design). The `SchemaSeeder` must be updated with a new schema `12 => 'الصيانة والطوارئ'` and attached (synced) to the relevant project types — currently `SchemaSeeder` only syncs to the `التصاميم` project type, so extend it to the project types that need maintenance (verified: `modules/Project/ProjectType/Database/Seeders/SchemaSeeder.php`).
- **Runtime task = existing EmployeeTask module (مهام العمل المسندة)**: The notification's linked task is a normal `EmployeeTaskRequest`, which is surfaced to the assigned engineer in the app under **مهام العمل المسندة** (Assigned Work Tasks). No separate mobile/task screen is needed — the engineer receives it in the existing EmployeeTask inbox.
- **Wizard form**: Creation is performed through a 5-step side-modal wizard (Notification Info → Contractor Info → Location → Assign to Employee → Confirm & Send).
- **Employee location source**: The wizard pulls live employee locations from the Attendance tracking system (`attendances.location_tracking` JSON). It uses `LocationTrackingService` / `Attendance` records to get the latest GPS point per project-assigned employee.
- **Distance calculation**: Haversine distance is calculated between the notification location and each employee's latest location using `Modules\EmployeeTask\Support\GeoDistance`.
- **Employee task type**: A project notification is a sub-type of `EmployeeTaskRequest`. A new `EmployeeTaskType` key `project_notification` is added, a new `EmployeeTaskItem` key `project_notification` is added (required for `item_type` validation), and the linked task carries `is_project_notification = true` and `task_source = 'dashboard'`.
- **Dashboard assignment**: Notifications are created by a dashboard user (`created_by_user_id`) and assigned to an employee (`assigned_user_id`). The linked task's `user_id` is the assigned employee.
- **Dedicated procedure**: A new `InternalProcessForm` case `CreateProjectNotificationTask` (`createProjectNotificationTask`) is added. A dedicated parent `ProcedureSetting` with code `project_notification_task` is seeded per tenant. The procedure defines who approves/assigns the notification and supports start/end/extend forms (matching the UI buttons `تأكيد استلام`, `تأكيد التواجد`, `تحديث`, `إنهاء المهمة`).
- **Condition evaluator reuse**: The notification create form reuses the centralized `EmployeeTaskFormConditionService` / `ConditionEvaluationService` infrastructure. A new `checkCreateProjectNotificationConditions()` method is added; no new evaluators are needed for v1.
- **Frontend handoff**: A separate frontend guide is provided at `@C:\projects\constrix-microservices\constrix_api\PROJECT_NOTIFICATION_TASK_FRONTEND_GUIDE.md` with components, API endpoints, state shape, translations, and testing checklist.

---

## 2. Terminology & Context

| Term | Meaning |
|------|---------|
| **Project Notification** | A dashboard-created issue/task tied to a project: a notification number, work description, contractor info, location, and an assigned engineer. |
| **Employee Task** | Existing `EmployeeTaskRequest` entity. A project notification is a sub-type of employee task. |
| **Schema** | `ProjectType` schemas (`project_schemas` table) define which tabs appear inside a project detail page. We add a new schema for the Notifications tab. |
| **Procedure Setting** | Configurable approval/assignment workflow from `ProcedureSetting` module. We add a dedicated procedure for project-notification tasks. |
| **Live Location** | Latest GPS point stored in `attendances.location_tracking` JSON, updated by the mobile attendance tracking flow. |
| **Distance** | Haversine distance between the notification location and the employee's latest GPS point. |

---

## 3. Functional Requirements

### 3.1 Project Detail Tab
- Add a new top-level tab **الصيانة والطوارئ** (Maintenance & Emergencies) to the project detail page; its first sub-view is **الإشعارات** (Notifications). (Sub-views المخالفات / التقارير / المؤشرات are out of scope for v1 — see §3.7.)
- Tab visibility is controlled by the `ProjectType` schema system, exactly like existing tabs (Attachments, Stakeholders, Document Cycle, etc.).

### 3.2 Notifications List
- List notifications for the current project.
- Columns: Notification Number, Notification Type, Work Type, Contractor, Assigned Engineer, Location, Status, Distance, Action.
- Filters: Status, Type, Work Type, Date Range, Employee.
- Export to Excel.

### 3.3 Add Notification Wizard (5 Steps)
1. **بيانات الإشعار** (Notification Info): type, severity (`منخفض`/`متوسط`/`عالي`), notification number, Magdy/feeder number, work description.
2. **بيانات المقاول** (Contractor Info): contractor name, contractor number, contractor technical number, contractor category, contractor notes, mobile number.
3. **الموقع** (Location): pick a point on map, enter coordinates, radius, Google Maps link, repair point.
4. **الإسناد لموظف** (Assign to Employee): show employees assigned to the project on a map, show their live location, distance from notification location, and status. Select one employee.
5. **تأكيد الإرسال** (Confirm & Send): summary of all steps, confirm, send notification, create employee task.

### 3.4 Employee Location & Distance
- Pull latest location for each project-assigned employee from Attendance tracking.
- Calculate distance from notification location to each employee's latest location.
- Show distance in km/meters and status colors (available, busy, offline).
- Fallback to branch/contractor default location if no live GPS available.

### 3.5 Workflow / Procedure
- Project notifications use a dedicated `ProcedureSetting` parent of type `employee_task` with name **مهام الصيانة والطوارئ**.
- The parent is seeded per tenant by `ProjectNotificationProcedureSeeder` and has its own workflow that mirrors the branch associations of the default employee-task workflow.
- Four internal procedure forms are created under this parent, matching the UI buttons in the image:
  - **تأكيد استلام** → `ConfirmProjectNotificationPresence` (confirm-receive; moves task from approved to in_progress)
  - **تحديث** → `UpdateProjectNotificationTask`
  - **إنهاء المهمة** → `EndProjectNotificationTask`
- The create form (`CreateProjectNotificationTask`) is also under this parent.
- Each form can have its own approval/assignment steps, conditions, and action takers.
- If auto-approved, the linked `EmployeeTaskRequest` becomes `approved` and the employee receives an in-app notification + push.
- If manual approval is required, the task stays `pending` until the responsible approver acts.

### 3.5a Confirm-Receive Workflow (mirrors Start Task)
- When the assigned employee calls `POST /projects/notifications/{id}/confirm-receive`, the linked task is a project notification (`is_project_notification = true`), so `EmployeeTaskStartRequestService` resolves the dedicated `ConfirmProjectNotificationPresence` internal procedure under the project-notification parent.
- If the procedure has steps, a pending `EmployeeTaskStartRequest` is created and the task remains `approved`.
- If no procedure / no steps are configured, the task starts immediately (`status = in_progress`).
- The confirm-receive request appears in the admin inbox (`GET /admin/employee-tasks/inbox`) with `type = start_request`.
- A single unified endpoint approves/rejects by ID:
  - `POST /admin/employee-tasks/{startRequestId}/approve`
  - `POST /admin/employee-tasks/{startRequestId}/reject`
- On final approval, the task is marked `in_progress` and a session is created.
- `InternalProcedureSettingsSeeder` continues to auto-create the generic `startTask` internal procedure under the default employee-task parent for regular tasks, while `ProjectNotificationProcedureSeeder` manages the project-notification-specific forms. The `StartProjectNotificationTask` form has been removed.

### 3.6 Notifications & Events
- Real-time update via Reverb websocket (reusing existing `EmployeeTaskNotification` / `InboxCountsUpdated` events).
- Email/SMS notification to assigned employee when approved.
- Inbox badge update for the employee.

### 3.7 Scope Boundary (v1) — Maintenance & Emergencies Tab

The full `الصيانة والطوارئ` tab in the design contains four sub-views: **الإشعارات** (Notifications), **المخالفات** (Violations), **التقارير** (Reports), **المؤشرات** (Indicators).

- **In scope for v1**: الإشعارات only (this plan).
- **Out of scope for v1 (flagged, not silently assumed)**:
  - **المخالفات / Violations** — the notifications list shows a `عدد المخالفات` (violations count) column; this implies a future `project_notification_violations` entity. For v1, render the column as `0` / placeholder and do **not** model it. The list presenter should expose `violations_count` as a hard-coded `0` until the violations entity exists.
  - **التقارير / Reports** and **المؤشرات / Indicators** — future analytics sub-views; no backend work in v1.

> This boundary must be confirmed with the product owner before build. If violations are required in v1, the data model, migrations, and effort estimate grow significantly.

---

## 4. Domain Model Design

### 4.1 Core Entities

```
┌─────────────────────────────┐
│   ProjectManagement         │
│   (existing project)        │
└──────────────┬──────────────┘
               │ 1 : N
               ▼
┌─────────────────────────────┐       ┌─────────────────────────────┐
│   ProjectNotification       │◄──────┤ ProjectNotificationAttachment │
│   (dashboard metadata)        │ 1 : N │ (media via Spatie)          │
└──────────────┬──────────────┘       └─────────────────────────────┘
               │ 1 : 1
               │ (optional, one linked task per notification)
               ▼
┌─────────────────────────────┐
│   EmployeeTaskRequest       │
│   type = project_notification│
│   user_id = assigned employee│
└──────────────┬──────────────┘
               │
               │ uses existing workflow
               ▼
┌─────────────────────────────┐
│   ProcedureSetting / Process │
│   (approval workflow)         │
└───────────────────────────────┘
```

### 4.2 `project_notifications` Table (New)

| Column | Type | Notes |
|--------|------|-------|
| `id` | UUID PK | |
| `company_id` | string | Tenant ID |
| `project_id` | UUID FK → `projects` | |
| `employee_task_request_id` | UUID FK → `employee_task_requests` nullable | Linked task after creation |
| `notification_number` | string | `NTF-YYYY-00001`, unique per company |
| `notification_type` | string | e.g., `صيانة`, `طوارئ`, `إصلاح عطل`, `فحص` |
| `severity` | string | `منخفض` / `متوسط` / `عالي` — the priority badge shown in the list (was missing in v1.2) |
| `work_type` | string | Type of work to be done |
| `magdy_number` | string | `رقم المغذي` — electrical **feeder** number (e.g., `FDR-102`). Consider aliasing as `feeder_number`; `magdy` is a transliteration of `مغذي` (feeder). |
| `work_description` | text | وصف العمل |
| `contractor_name` | string | |
| `contractor_number` | string | |
| `contractor_technical_number` | string | |
| `contractor_category` | string | فني المقاول |
| `contractor_notes` | text | |
| `contractor_mobile` | string | |
| `task_latitude` | decimal(10,8) | Notification location |
| `task_longitude` | decimal(11,8) | Notification location |
| `location_radius` | int | Allowed radius in meters |
| `location_link` | string | Google Maps share link |
| `repair_point` | string | نقطة الإصلاح |
| `assigned_user_id` | UUID FK → `users` | Selected engineer |
| `selected_distance_meters` | int | Distance at assignment time |
| `status` | string | `pending`, `approved`, `rejected`, `in_progress`, `completed`, `cancelled` |
| `created_by_user_id` | UUID FK → `users` | Dashboard creator |
| `approved_by` | UUID FK → `users` nullable | |
| `approved_at` | datetime nullable | |
| `rejected_by` | UUID FK → `users` nullable | |
| `rejected_at` | datetime nullable | |
| `rejection_reason` | text nullable | |
| `task_date` | date | Date the task should be executed |
| `duration_hours` | float | Estimated duration |
| `notes` | text nullable | |
| `soft deletes` | | |
| `timestamps` | | |

**Indexes**:
- `(company_id, project_id)`
- `(company_id, status)`
- `(employee_task_request_id)`
- `(assigned_user_id)`
- `(notification_number)` unique per company

### 4.3 `employee_task_requests` Extensions (New Migration)

Add nullable columns to the existing EmployeeTask table so it can carry project-notification data without breaking existing flows:

| Column | Type | Notes |
|--------|------|-------|
| `project_notification_id` | UUID FK → `project_notifications` nullable | Back-link to the dashboard record |
| `is_project_notification` | boolean default `false` | Quick filter flag |
| `sender_user_id` | UUID FK → `users` nullable | Dashboard user who created it |
| `task_source` | string default `mobile` | `mobile` or `dashboard` |

> **Why**: The existing `EmployeeTaskRequest` already has `project_id`, `task_latitude`, `task_longitude`, `radius_meters`, `duration_hours`, `task_date`, `status`, `title`, `description`, `notes`, `approval_responsible_id`, `assignment_responsible_id`, and `procedure_setting_id`. We only need to add the back-link and source flag.

### 4.4 `employee_task_types` and `employee_task_items` Seeder Addition

Add a new type row:

| `id` | `key` | `name` |
|------|-------|--------|
| generated UUID | `project_notification` | `إشعار مشروع` |

Update `EmployeeTaskTypeSeeder` (or create one) to ensure this key exists.

**Also add an `EmployeeTaskItem` row** — the `CreateEmployeeTaskRequest` validation enforces `item_type` to `exists:employee_task_items,key`. The plan passes `item_type = 'project_notification'`, so a matching `EmployeeTaskItem` must be seeded:

```php
EmployeeTaskItem::firstOrCreate(
    ['key' => 'project_notification'],
    ['id' => (string) Str::uuid(), 'key' => 'project_notification', 'name' => 'إشعار مشروع']
);
```

> **Why (corrected)**: `CreateEmployeeTaskRequest::rules()` has `'item_type' => ['nullable', 'string', 'max:255', 'exists:employee_task_items,key']` (verified). **Important nuance**: the project-notification flow builds `CreateEmployeeTaskRequestDTO` directly inside `ProjectNotificationService` and calls `EmployeeTaskRequestService::create()` — it does **not** pass through the `CreateEmployeeTaskRequest` form request, so this `exists` rule does **not** run on the dashboard path. Seeding the `employee_task_items` row is therefore good hygiene (and required if the same `item_type` is ever submitted through the EmployeeTask HTTP endpoint), but the earlier claim that "validation will fail" without it is inaccurate for this flow. Also note `CreateEmployeeTaskRequestDTO::__construct` makes `itemType` and `itemId` **required non-nullable** params (verified) — always pass both.

### 4.5 `project_schemas` Seeder Addition

Add to `SchemaSeeder` (`$schemas` array):

```php
12 => 'الصيانة والطوارئ',
```

The seeder currently builds `$schemaIds` from the array and `sync()`s them onto the `التصاميم` project type only. Adding `12` includes it automatically in that sync. To show the tab on other project types (e.g., `الإشراف` / supervision projects seen in the screenshots), extend the seeder to also sync schema `12` onto those types, or attach it via the project-type schema admin UI.

### 4.6 Field Mapping from Wizard to DB

| Wizard Step | Wizard Field | DB Column |
|-------------|--------------|-----------|
| 1 | نوع الإشعار | `notification_type` |
| 1 | الأهمية / مستوى الجهد | `severity` (`منخفض`/`متوسط`/`عالي`) |
| 1 | رقم الإشعار | `notification_number` (auto-generated) |
| 1 | رقم المغذي | `magdy_number` |
| 1 | وصف العمل | `work_description` |
| 2 | اسم المقاول | `contractor_name` |
| 2 | رقم المقاول | `contractor_number` |
| 2 | رقم فني المقاول | `contractor_technical_number` |
| 2 | فني المقاول | `contractor_category` |
| 2 | ملاحظات المقاول | `contractor_notes` |
| 2 | رقم الجوال | `contractor_mobile` |
| 3 | خط العرض / نقطة على الخريطة | `task_latitude` |
| 3 | خط الطول | `task_longitude` |
| 3 | نقطة الإصلاح | `repair_point` |
| 3 | نطاق الإصلاح | `location_radius` |
| 3 | رابط الموقع | `location_link` |
| 4 | المهندس المكلف | `assigned_user_id` |
| 4 | المسافة المحسوبة | `selected_distance_meters` |
| 5 | تأكيد الإرسال | triggers creation |

---

## 5. Backend Implementation Plan

### 5.1 Phase 1 — Schema & Reference Data

#### 5.1.1 Update `SchemaSeeder`

File: `modules/Project/ProjectType/Database/Seeders/SchemaSeeder.php`

- Add `12 => 'الصيانة والطوارئ'` to the `$schemas` array.
- The seeder already `sync()`s all listed schema IDs onto the `التصاميم` project type — extend it to also sync onto the supervision/maintenance project types that need this tab.
- Ensure the seeder is idempotent (already uses `firstOrCreate` + `sync`).

#### 5.1.2 Add Project Notification Schema to Allowed Schemas

When sharing a project (`ResourceShare`), the `allowed_schemas` array controls which tabs the shared company can see. Update the sharing logic to include schema `12` optionally, or default to include it if the sender has permission.

#### 5.1.3 Add EmployeeTask Type

Create or update a seeder in `modules/EmployeeTask/Database/Seeders/`:

```php
EmployeeTaskType::firstOrCreate(
    ['key' => 'project_notification'],
    ['id' => (string) Str::uuid(), 'key' => 'project_notification', 'name' => 'إشعار مشروع']
);
```

#### 5.1.4 Add Procedure Setting for Project Notification Tasks

Create a new seeder in `ProcedureSetting` module:

- File: `modules/ProcedureSetting/Database/Seeders/ProjectNotificationProcedureSeeder.php`
- Parent `ProcedureSetting` of type `employee_task` with `name = 'مهام الصيانة والطوارئ'`.
- Child `ProcedureSetting` rows for forms:
  - `CreateProjectNotificationTask`
  - `ConfirmProjectNotificationPresence` (label: تأكيد استلام)
  - `UpdateProjectNotificationTask` (label: تحديث)
  - `EndProjectNotificationTask` (label: إنهاء المهمة)
- Configure responsibles and approvers per branch/management/department.
- The parent workflow mirrors the branch associations of the default employee-task workflow so the same branches see the project-notification procedure.
- `InternalProcedureSettingsSeeder` is updated to skip forms whose value contains `ProjectNotification`; those are managed exclusively by `ProjectNotificationProcedureSeeder`.

> **Decision**: A dedicated `CreateProjectNotificationTask` form already exists in `InternalProcessForm`. Three additional project-notification forms (`ConfirmProjectNotificationPresence`, `UpdateProjectNotificationTask`, `EndProjectNotificationTask`) are used with Arabic labels matching the image buttons. `StartProjectNotificationTask` has been removed; confirm-receive is handled by `ConfirmProjectNotificationPresence`. The dedicated parent is resolved by storing the task's `procedure_setting_id` at creation time, so all subsequent lifecycle actions (confirm-receive/end) use the correct internal procedures.

### 5.2 Phase 2 — Database Migrations

#### 5.2.1 Create `project_notifications` table

File: `modules/Project/ProjectManagement/Database/Migrations/2026_06_27_000001_create_project_notifications_table.php`

Use the columns defined in Section 4.2. Include foreign keys with `onDelete('cascade')` where appropriate and `onDelete('set null')` for user references.

#### 5.2.2 Extend `employee_task_requests`

File: `modules/EmployeeTask/Database/Migrations/2026_06_27_000002_add_project_notification_fields_to_employee_task_requests.php`

Add:
- `project_notification_id` (UUID, indexed, nullable, FK → `project_notifications.id` onDelete set null)
- `is_project_notification` (boolean default false, indexed)
- `sender_user_id` (UUID, indexed, nullable, FK → `users.id` onDelete set null)
- `task_source` (string default 'mobile', indexed)

#### 5.2.3 Add Project Notification Permission Keys (Dual System)

The project has **two permission systems** that must both be updated:

**1. Global permissions** (`modules/Project/Config/permissions.php`) — used by the `Permission` enum with `->permission()` on routes:

```php
// Project Notifications (global route-level access)
'PROJECT_NOTIFICATION_LIST'   => 'work-panel.project-management*project-notification.list',
'PROJECT_NOTIFICATION_VIEW'   => 'work-panel.project-management*project-notification.view',
'PROJECT_NOTIFICATION_CREATE' => 'work-panel.project-management*project-notification.create',
'PROJECT_NOTIFICATION_UPDATE' => 'work-panel.project-management*project-notification.update',
'PROJECT_NOTIFICATION_DELETE' => 'work-panel.project-management*project-notification.delete',
'PROJECT_NOTIFICATION_EXPORT' => 'work-panel.project-management*project-notification.export',
```

**2. Project-specific permissions** (`modules/Project/ProjectManagement/Resources/config/config.php`) — used by the `ProjectPermission` enum with `CheckProjectPermission` middleware for project-role-scoped access:

```php
// Project Notifications (project-role-scoped)
'PROJECT_NOTIFICATION_VIEW'   => 'project-management.project-management*notifications.view',
'PROJECT_NOTIFICATION_LIST'   => 'project-management.project-management*notifications.list',
'PROJECT_NOTIFICATION_CREATE' => 'project-management.project-management*notifications.create',
'PROJECT_NOTIFICATION_UPDATE' => 'project-management.project-management*notifications.update',
'PROJECT_NOTIFICATION_DELETE' => 'project-management.project-management*notifications.delete',
```

Update `ProjectPermissionsSeeder` so the project-specific permissions are seeded and auto-assigned to the "Project Admin" role.

### 5.3 Phase 3 — Models & Relationships

#### 5.3.1 Create `ProjectNotification` Model

File: `modules/Project/ProjectManagement/Models/ProjectNotification.php`

```php
class ProjectNotification extends Model implements HasMedia
{
    use UuidTrait;
    use BaseFilterable;
    use CustomBelongsToTenant;
    use InteractsWithMedia;

    protected $table = 'project_notifications';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'company_id', 'project_id', 'employee_task_request_id', 'notification_number',
        'notification_type', 'severity', 'work_type', 'magdy_number', 'work_description',
        'contractor_name', 'contractor_number', 'contractor_technical_number',
        'contractor_category', 'contractor_notes', 'contractor_mobile',
        'task_latitude', 'task_longitude', 'location_radius', 'location_link', 'repair_point',
        'assigned_user_id', 'selected_distance_meters', 'status',
        'created_by_user_id', 'approved_by', 'approved_at', 'rejected_by', 'rejected_at',
        'rejection_reason', 'task_date', 'duration_hours', 'notes',
    ];

    protected $casts = [
        'task_latitude'  => 'float',
        'task_longitude' => 'float',
        'location_radius' => 'integer',
        'selected_distance_meters' => 'integer',
        'duration_hours' => 'float',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    public function project(): BelongsTo { ... }
    public function employeeTask(): BelongsTo { ... }
    public function assignedUser(): BelongsTo { ... }
    public function creator(): BelongsTo { ... }
    public function approver(): BelongsTo { ... }
    public function rejecter(): BelongsTo { ... }
    public function attachments(): MorphMany (Spatie) { ... }
}
```

> **Traits note**: `CustomBelongsToTenant` is used (same as `EmployeeTaskRequest`) for multi-tenant scoping via `company_id`. `InteractsWithMedia` enables Spatie media attachments. The `getTenantIdColumn()` method specifies `company_id` as the tenant column.

#### 5.3.2 Update `ProjectManagement` Model

Add relationship:

```php
public function notifications(): HasMany
{
    return $this->hasMany(ProjectNotification::class, 'project_id')->withoutGlobalScopes();
}
```

#### 5.3.3 Update `EmployeeTaskRequest` Model

Add relationships:

```php
public function projectNotification(): BelongsTo { ... }
public function sender(): BelongsTo { ... }
```

### 5.4 Phase 4 — DTOs & Form Requests

#### 5.4.1 Create DTOs

- `CreateProjectNotificationDTO` — carries all wizard fields and the final employee selection.
- `UpdateProjectNotificationDTO` — for editing before approval.
- `FilterProjectNotificationDTO` — for list filters.
- `ProjectNotificationLocationDTO` — location-only payload for step 3 preview.

#### 5.4.2 Create Form Requests

- `CreateProjectNotificationRequest` — validates all 5 wizard steps combined (or one request per step, see frontend plan). Combines:
  - step 1: notification info
  - step 2: contractor info
  - step 3: location
  - step 4: employee selection
- `UpdateProjectNotificationRequest`
- `FilterProjectNotificationsRequest`
- `GetProjectNotificationEmployeesRequest` — validates `project_id` and returns employees with live locations.

### 5.5 Phase 5 — Services

#### 5.5.1 Create `ProjectNotificationRepository`

File: `modules/Project/ProjectManagement/Repositories/ProjectNotificationRepository.php`

Following the project's `Service → Repository → Model` architecture:

```php
class ProjectNotificationRepository
{
    public function create(array $data): ProjectNotification;
    public function paginated(array $filters): LengthAwarePaginator;
    public function get(string $id): ProjectNotification;
    public function update(string $id, array $data): bool;
    public function delete(string $id): bool;
    public function generateNotificationNumber(string $companyId): string;
}
```

#### 5.5.2 Create `ProjectNotificationService`

File: `modules/Project/ProjectManagement/Services/ProjectNotificationService.php`

Responsibilities:
- `create(CreateProjectNotificationDTO $dto): ProjectNotification`
  - Generate `notification_number` (format `NTF-{YEAR}-{00001}`).
  - Create `ProjectNotification` row in `pending` status.
  - Resolve the correct `ProcedureSetting` for project notification tasks.
  - Create a linked `EmployeeTaskRequest` of type `project_notification`.
  - Trigger the procedure workflow.
  - Dispatch notification events.
- `list(FilterProjectNotificationDTO $dto): LengthAwarePaginator|Collection`
- `get(string $id): ProjectNotification`
- `update(string $id, UpdateProjectNotificationDTO $dto): ProjectNotification`
- `delete(string $id): bool`
- `export(FilterProjectNotificationDTO $dto): BinaryFileResponse`
- `approve(string $id, string $userId): ProjectNotification`
- `reject(string $id, string $userId, string $reason): ProjectNotification`

#### 5.5.3 Create `ProjectNotificationLocationService`

Responsibilities:
- `getProjectEmployeesWithLocations(string $projectId, ?array $filters): array`
  - Load `ProjectEmployee` records for the project.
  - For each user, fetch the latest live location via Attendance location tracking.
  - Calculate distance from the notification location using `Modules\EmployeeTask\Support\GeoDistance`.
  - Return enriched employee objects with: user info, latest location, distance in meters, status (available / busy / offline / no-location).
- `calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float`
  - Reuse `Modules\EmployeeTask\Support\GeoDistance::metres()` — **this is the only method on the class and it already returns meters**. (Verified: `modules/EmployeeTask/Support/GeoDistance.php` exposes a single static `metres(float $lat1, float $lon1, float $lat2, float $lon2): float`.)

#### 5.5.4 Integration with `EmployeeTaskRequestService`

When `ProjectNotificationService::create` runs, it should delegate the linked task creation to `EmployeeTaskRequestService::create` with a specially built `CreateEmployeeTaskRequestDTO`.

> **DTO compatibility**: The existing `CreateEmployeeTaskRequestDTO` already accepts `itemType` and `itemId` as constructor parameters — no DTO changes needed. However, the `CreateEmployeeTaskRequest` validation requires `item_type` to `exists:employee_task_items,key`, so a matching `EmployeeTaskItem` row must be seeded (see §4.4).

> **Service change required (verified scope)**: `EmployeeTaskRequestService::create()` hardcodes `InternalProcessForm::CreateTask` in **three** distinct places, not one:
> 1. `previewResponsibles(...)` — auto-approve detection.
> 2. `$this->conditionService->checkCreateTaskConditions(...)` — condition evaluation (the condition service itself resolves the child setting by the `CreateTask` form internally).
> 3. `markCreateTaskProceduresTaken($task, $userId)` — which queries `ProcedureSetting::where('form', InternalProcessForm::CreateTask->value)` to fire `WorkflowProcedureTaken`.
>
> Add an optional `?string $formKey = null` parameter (defaulting to `CreateTask`) and thread it through **all three** call sites. If you only patch `previewResponsibles`, the auto-approve path will mark the **wrong** form's procedure as taken and the project-notification procedure will never be recorded. (Verified against `modules/EmployeeTask/Services/EmployeeTaskRequestService.php`, `create()` lines ~43-124 and `markCreateTaskProceduresTaken()` lines ~132-160.)

```php
$taskDto = new CreateEmployeeTaskRequestDTO(
    userId: $dto->assignedUserId,
    title: $dto->notificationNumber,
    employee_task_type_id: $projectNotificationTypeId,
    itemType: 'project_notification',
    itemId: $notification->id,
    durationHours: $dto->durationHours,
    taskDate: $dto->taskDate,
    taskLatitude: $dto->taskLatitude,
    taskLongitude: $dto->taskLongitude,
    currentLatitude: null,
    currentLongitude: null,
    description: $dto->workDescription,
    projectId: $dto->projectId,
    approvalResponsibleId: $dto->approvalResponsibleId,
    assignmentResponsibleId: $dto->assignmentResponsibleId,
    notes: $dto->notes,
    files: $dto->files,
);

$task = $this->employeeTaskRequestService->create(
    $taskDto,
    InternalProcessForm::CreateProjectNotificationTask->value,
);
$notification->update(['employee_task_request_id' => $task->id]);
$task->update([
    'project_notification_id' => $notification->id,
    'is_project_notification' => true,
    'sender_user_id' => $dto->createdByUserId,
    'task_source' => 'dashboard',
]);
```

> **Procedure parent snapshot**: `EmployeeTaskRequestService::create()` now resolves the parent `ProcedureSetting` and stores its `id` in the linked task's `procedure_setting_id` column. This ensures all subsequent lifecycle actions (`start`, `end`, `available-actions`) consistently use the same parent procedure instead of re-resolving by branch.
>
> **Form key**: The call must pass `InternalProcessForm::CreateProjectNotificationTask->value` so the create, condition-check, and taken-marking paths all target the correct internal procedure.
>
> **Note**: The `EmployeeTaskRequestService` currently uses `title` as the task title and `serial_number` as internal task serial. For project notifications, the notification number is the business identifier, but the task can still use its own serial.

#### 5.5.5 Serial Number Generation

Create a repository method to generate `NTF-{YEAR}-{SEQUENCE}` unique per `company_id`. Use a dedicated counter table with `DB::transaction` + `selectForUpdate` for robust concurrency handling:

```php
public function generateNotificationNumber(string $companyId): string
{
    return DB::transaction(function () use ($companyId) {
        $year = now()->format('Y');
        $counter = DB::table('project_notification_counters')
            ->where('company_id', $companyId)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($counter) {
            DB::table('project_notification_counters')
                ->where('id', $counter->id)
                ->increment('sequence');
            $sequence = $counter->sequence + 1;
        } else {
            $id = (string) Str::uuid();
            DB::table('project_notification_counters')->insert([
                'id' => $id,
                'company_id' => $companyId,
                'year' => $year,
                'sequence' => 1,
            ]);
            $sequence = 1;
        }

        return "NTF-{$year}-" . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    });
}
```

> **Why a counter table?** Using `MAX(notification_number)` with `lockForUpdate` is prone to race conditions under high concurrency because the `MAX` query reads a computed value, not a stored row. A dedicated counter table with `SELECT … FOR UPDATE` on a specific row is the safest approach.
>
> **Migration**: Add `2026_06_27_000003_create_project_notification_counters_table.php` with columns: `id` (UUID PK), `company_id` (UUID), `year` (int), `sequence` (bigint), unique on `(company_id, year)`.

#### 5.5.6 Create `ProjectNotificationObserver`

File: `modules/Project/ProjectManagement/Observers/ProjectNotificationObserver.php`

Following the project pattern (e.g., `ProjectManagementObserver` auto-generates serial numbers on `creating`):

```php
class ProjectNotificationObserver
{
    public function creating(ProjectNotification $notification): void
    {
        if (empty($notification->notification_number)) {
            $notification->notification_number = 
                app(ProjectNotificationRepository::class)
                    ->generateNotificationNumber($notification->company_id);
        }
    }
}
```

Register in `ProjectManagementServiceProvider::boot()`:
```php
ProjectNotification::observe(ProjectNotificationObserver::class);
```

#### 5.5.7 Create `ProjectNotificationFilter`

File: `modules/Project/ProjectManagement/Filters/ProjectNotificationFilter.php`

Extends `SearchModelFilter` with filter methods:
- `status($value)` — filter by status
- `notificationType($value)` — filter by notification type
- `workType($value)` — filter by work type
- `contractorName($value)` — search contractor name (LIKE)
- `assignedUserId($value)` — filter by notification's dashboard-assigned user
- `taskUserId($value)` — filter by linked `EmployeeTaskRequest.user_id`
- `workflowInboxForUser($value)` — filter by linked `project_notification_task` process that has an `in_progress` process with a `pending` step assigned to the user
- `dateRange($from, $to)` — filter by `task_date` range
- `projectId($value)` — filter by project

### 5.6 Phase 6 — Controllers & Routes

#### 5.6.1 Create `ProjectNotificationController`

File: `modules/Project/ProjectManagement/Controllers/ProjectNotificationController.php`

Methods:
- `index(FilterProjectNotificationsRequest $request)` — list
- `store(CreateProjectNotificationRequest $request)` — create
- `show(Request $request)` — details
- `update(UpdateProjectNotificationRequest $request)` — edit
- `destroy(Request $request)` — delete
- `export(FilterProjectNotificationsRequest $request)` — Excel export
- `employeesWithLocations(GetProjectNotificationEmployeesRequest $request)` — step 4 data
- `approve(Request $request)` — approve
- `reject(Request $request)` — reject

#### 5.6.2 Add Routes

File: `modules/Project/ProjectManagement/Resources/routes/api.php`

Add under the existing project route group:

```php
Route::prefix('notifications')->group(function () {
    // Static routes MUST come before /{id} to avoid route conflicts
    Route::get('/employees-with-locations', [ProjectNotificationController::class, 'employeesWithLocations'])
        ->permission(Permission::PROJECT_NOTIFICATION_CREATE());
    Route::post('/export', [ProjectNotificationController::class, 'export'])
        ->permission(Permission::PROJECT_NOTIFICATION_EXPORT());

    // CRUD routes
    Route::get('/', [ProjectNotificationController::class, 'index'])
        ->permission(Permission::PROJECT_NOTIFICATION_LIST());
    Route::post('/', [ProjectNotificationController::class, 'store'])
        ->permission(Permission::PROJECT_NOTIFICATION_CREATE());
    Route::get('/{id}', [ProjectNotificationController::class, 'show'])
        ->permission(Permission::PROJECT_NOTIFICATION_VIEW());
    Route::put('/{id}', [ProjectNotificationController::class, 'update'])
        ->permission(Permission::PROJECT_NOTIFICATION_UPDATE());
    Route::delete('/{id}', [ProjectNotificationController::class, 'destroy'])
        ->permission(Permission::PROJECT_NOTIFICATION_DELETE());

    // Action routes
    Route::post('/{id}/approve', [ProjectNotificationController::class, 'approve'])
        ->permission(Permission::PROJECT_NOTIFICATION_UPDATE());
    Route::post('/{id}/reject', [ProjectNotificationController::class, 'reject'])
        ->permission(Permission::PROJECT_NOTIFICATION_UPDATE());
});
```

> **Route ordering**: Static routes (`employees-with-locations`, `export`) are placed **before** `/{id}` to prevent Laravel from interpreting `employees-with-locations` as a notification ID.
>
> **Permission enum**: The `Permission` enum uses `__callStatic` to load from `modules/Project/Config/permissions.php`. The new `PROJECT_NOTIFICATION_*` keys added in §5.2.3 will resolve correctly.
>
> **Project-specific permissions**: For project-role-scoped access (e.g., only project admins can create notifications within a specific project), add the `CheckProjectPermission` middleware to routes that act on a specific project:
> ```php
> Route::middleware('project.permission:PROJECT_NOTIFICATION_CREATE')->post('/', ...);
> ```
> This checks the user's `ProjectRole` permissions within the project identified by `project_id` in the request.

### 5.7 Phase 7 — Location & Distance Logic

#### 5.7.1 Reuse Attendance Location Tracking

The existing `LocationTrackingService::getTodaysActiveAttendance(array $filters)` already filters by `project_id` and returns active attendances with `user` and latest `location_tracking` points. We can reuse it, but we need the **latest location per user**, not just active attendances.

Create a new service method in `ProjectNotificationLocationService`:

```php
public function getLatestLocationsForProjectEmployees(string $projectId, ?string $companyId): Collection
{
    // 1. Get user IDs assigned to the project
    $userIds = ProjectEmployee::withoutGlobalScopes()
        ->where('project_id', $projectId)
        ->when($companyId, fn($q) => $q->where('company_id', $companyId))
        ->pluck('user_id')
        ->filter()
        ->unique()
        ->values();

    if ($userIds->isEmpty()) {
        return collect();
    }

    // 2. Batch-query today's attendances for ALL project employees at once (avoids N+1)
    $attendances = Attendance::whereIn('user_id', $userIds)
        ->whereBetween('clock_in_time', [now()->startOfDay(), now()->endOfDay()])
        ->where('is_absent', false)
        ->where('is_holiday', false)
        ->orderByDesc('clock_in_time')
        ->get()
        ->groupBy('user_id');

    // 3. Extract latest location per user from the collection
    $locations = collect();
    foreach ($userIds as $userId) {
        $userAttendances = $attendances->get($userId);
        $attendance = $userAttendances?->first(); // first() = latest due to orderByDesc

        $latestPoint = null;
        if ($attendance && !empty($attendance->location_tracking)) {
            $tracking = collect($attendance->location_tracking)
                ->sortByDesc(fn($p) => strtotime($p['timestamp'] ?? 'now'))
                ->first();
            if ($tracking) {
                $latestPoint = $tracking;
            }
        }

        // Fallback to clock-in location if no tracking points yet
        if (!$latestPoint && $attendance && !empty($attendance->clock_in_location)) {
            $latestPoint = array_merge($attendance->clock_in_location, [
                'timestamp' => $attendance->clock_in_time,
                'type' => 'clock_in',
                'location_source' => 'clock_in',
            ]);
        }

        $locations->push([
            'user_id' => $userId,
            'attendance_id' => $attendance?->id,
            'attendance_status' => $attendance?->status ?? 'offline',
            'latest_location' => $latestPoint,
            'last_update' => $latestPoint['timestamp'] ?? null,
        ]);
    }

    return $locations;
}
```

> **Performance**: The batch query uses a single `whereIn` to fetch all attendances at once, then groups by `user_id` in PHP. This avoids N+1 queries when a project has many employees.

#### 5.7.2 Distance Calculation

Use `Modules\EmployeeTask\Support\GeoDistance` (verified signature):

```php
use Modules\EmployeeTask\Support\GeoDistance;

$distanceMeters = GeoDistance::metres(
    $notificationLat, $notificationLng,
    $employeeLat, $employeeLng
);
```

> **Verified**: The class has exactly one method, `metres()`, which returns meters (Haversine, earth radius 6371 km × 1000). Do **not** invent `haversineMeters()`/`haversineKm()` — they do not exist.

#### 5.7.3 Employee Status for Assignment

Define a status derivation based on attendance state and location freshness:

| Status | Rule | UI Color |
|--------|------|----------|
| `available` | Active attendance + recent GPS (< 15 min) | Green |
| `busy` | Active attendance + currently on another task | Orange |
| `offline` | No active attendance today | Gray |
| `no_location` | Active attendance but no GPS for > 15 min | Red/Pink |
| `available_far` | Active + GPS but outside preferred radius | Yellow |

For the "busy" detection, query `EmployeeTaskRequest` for tasks with status `in_progress` or `approved` for the same user and overlapping `task_date`.

### 5.8 Phase 8 — Procedure & Workflow Integration

This feature reuses the existing `ProcedureSetting` / `Process` workflow engine. A project notification is a dashboard-created `EmployeeTaskRequest`, so its workflow category is `ProcedureSettingType::EmployeeTask`. We add a dedicated form value so the procedure settings UI can distinguish project-notification tasks from regular employee tasks.

#### 5.8.1 Add a New `InternalProcessForm` Case

File: `modules/Shared/InternalProcessType/Enums/InternalProcessForm.php`

Add a new case and include it in `applicableTypes()` and `conditions()`:

```php
case CreateProjectNotificationTask = 'createProjectNotificationTask';
```

Update `applicableTypes()`:

```php
self::CreateProjectNotificationTask => ['employee_task'],
```

Update `conditions()` (or `conditions()` if it exists as a method) so the create form has the same configurable conditions as `CreateTask`:

```php
self::CreateProjectNotificationTask => [
    InternalProcessCondition::AllowDuringShift,
    InternalProcessCondition::AllowOutsideShift,
    InternalProcessCondition::AllowOnHolidays,
    InternalProcessCondition::InsideCustomLocations,
    InternalProcessCondition::MaxTaskDuration,
    InternalProcessCondition::MaxScheduledDateOffset,
],
```

> **Naming convention**: `InternalProcedureSettingsSeeder` auto-seeds any form whose value starts with `create` or `end`. By naming the case `createProjectNotificationTask`, the seeder will automatically create the internal procedure setting row for this form under the `employee_task` parent.

#### 5.8.2 Create a Project-Notification Procedure Setting Seeder

File: `modules/ProcedureSetting/Database/Seeders/ProjectNotificationProcedureSeeder.php` (new)

This seeder creates a parent `ProcedureSetting` specifically for maintenance/notification tasks. It runs per tenant and is idempotent.

> **CRITICAL (verified)**: `ProcedureSetting` has **no `code` column and no `apply_on` column**. The real fillable is: `name`, `type`, `execute_type`, `icon`, `percentage`, `deadline_days`, `deadline_hours`, `escalation_management_hierarchy_id`, `company_id`, `work_flow_id`, `sort_order`, `parent_id`, `form`, `conditions`, `appears_before_id`, `appears_after_id`, `is_active`. Identify the **parent** by `type` + `name` + `parent_id IS NULL`; identify the **child** by `form` + `parent_id`. The `conditions` / `appears_before_id` / `appears_after_id` columns are array-cast.

```php
DB::transaction(function () {
    $companyId = tenant('id');
    if (!$companyId) return;

    // Parent is identified by type + name + null parent (NO `code` column exists).
    $parent = ProcedureSetting::firstOrCreate(
        [
            'company_id'  => $companyId,
            'type'        => ProcedureSettingType::EmployeeTask->value,
            'name'        => 'مهام الصيانة والطوارئ', // matches the in-app procedure name
            'parent_id'   => null,
        ],
        [
            'execute_type' => 'sequence', // confirm allowed values against existing employee_task parent
            'is_active'    => true,
        ]
    );

    // The child for the create form is auto-seeded by InternalProcedureSettingsSeeder
    // (because the form value starts with `create`). Ensure it is linked & active.
    $createForm = ProcedureSetting::where('company_id', $companyId)
        ->where('parent_id', $parent->id)
        ->where('form', InternalProcessForm::CreateProjectNotificationTask->value)
        ->first();

    if ($createForm) {
        $createForm->update(['is_active' => true]);
    }

    // Optional: explicitly seed an approval step. Verify ProcedureSettingStep's real
    // column names against modules/ProcedureSetting/Models/ProcedureSettingStep.php
    // and the ActionTakerType / ActionTakerManagementHierarchyType enums before use.
    // ProcedureSettingStep::firstOrCreate([... 'procedure_setting_id' => $createForm?->id ?? $parent->id ...]);
});
```

> **Important**: The child setting must have `parent_id` pointing to the parent `ProcedureSetting` and `form` equal to the new enum value. The UI image (الإجراءات والإعدادات) shows buttons `تأكيد استلام`, `تأكيد التواجد`, `تحديث`, `إنهاء المهمة`. **Verified caveat**: the `InternalProcessForm` enum currently has only `StartTask` (`startTask`) and `EndTask` (`endTask`) for the employee-task type — there is **no `ExtendTask` / `extend_task` case**. If `تحديث` (update/extend) and `تأكيد التواجد` (confirm presence) need their own procedure forms, you must add new enum cases for them too (and add them to `applicableTypes()` / `conditions()`); they cannot be assumed to exist.
>
> **Step model verification required**: Before writing `ProcedureSettingStep` rows, open `modules/ProcedureSetting/Models/ProcedureSettingStep.php` and the enums `ActionTakerType` / `ActionTakerManagementHierarchyType` to confirm the exact field names (`action_taker_type`, `is_approve`, `notify_by_email`, etc. are illustrative, not verified).

#### 5.8.3 Update `InternalProcedureSettingsSeeder` (if needed)

The auto-seeder already handles `create*` and `end*` forms. Since `CreateProjectNotificationTask` starts with `create`, it will automatically create a child `ProcedureSetting` row under the `employee_task` parent. However, the auto-seeder attaches it to the default `employee_task` parent. To attach it to the new **مهام الصيانة والطوارئ** parent, either:

- Update the auto-seeder to choose the parent by `type` + `name` (NOT `code` — that column does not exist) when `form` matches a specific list, or
- Run the dedicated `ProjectNotificationProcedureSeeder` **after** the auto-seeder to re-parent the child row (set its `parent_id` to the new parent's id) and add steps.

> **Simpler alternative worth considering**: do **not** create a separate parent at all. Reuse the existing single `employee_task` parent and let `CreateProjectNotificationTask` be just another child form under it (exactly like `CreateTask`/`StartTask`/`EndTask`). This avoids all re-parenting logic and matches how the workflow engine already resolves the parent via `resolveParentSetting(type, company, branch)`. Choose the dedicated parent only if the business genuinely needs separate per-branch responsibles for maintenance tasks vs. regular tasks.

#### 5.8.4 Add a `WorkflowNotifier` for Project Notifications (Optional)

For real-time notifications to follow the centralized pattern, create:

File: `modules/Project/ProjectManagement/Notifications/ProjectNotificationWorkflowNotifier.php`

Implement a `WorkflowNotifier` interface (or extend the existing notifier pattern) and register it in `WorkflowNotifierRegistry` for `processable_type = project_notification` if you choose to create a `Process` with `processable_type = project_notification`.

However, since we reuse `EmployeeTaskRequest` as the runtime entity, the existing `EmployeeTaskWorkflowNotifier` will already handle real-time inbox updates if the `Process` records use `processable_type = employee_task` and `processable_id = task_id`. This is the recommended approach.

#### 5.8.5 Workflow Resolution in `ProjectNotificationService::create`

> **Critical**: The linked `EmployeeTaskRequest` MUST be created via `EmployeeTaskRequestService::create()`, NOT via direct repository calls. The service handles condition checks, workflow engine start, `markCreateTaskProceduresTaken` event dispatch, notification broadcasting, file uploads, and stale rejection job dispatch. Bypassing it risks divergence and broken workflow integration.

**Prerequisite**: Add an optional `$formKey` parameter to `EmployeeTaskRequestService::create()` and thread it through **all three** hardcoded sites (verified):

```php
// modules/EmployeeTask/Services/EmployeeTaskRequestService.php
public function create(CreateEmployeeTaskRequestDTO $dto, ?string $formKey = null): EmployeeTaskRequest
{
    $procedureType = ProcedureSettingType::EmployeeTask->value;
    $formKey = $formKey ?? InternalProcessForm::CreateTask->value;
    $context = $dto->projectId ? ['project_id' => $dto->projectId] : [];
    // ...

    // (1) condition check — pass the form key through to the condition service
    $this->conditionService->checkCreateTaskConditions(
        $dto->userId, $companyId, $branchId,
        $dto->durationHours, $dto->taskDate,
        $dto->taskLatitude, $dto->taskLongitude,
        $dto->currentLatitude, $dto->currentLongitude,
        $formKey, // NEW optional arg on EmployeeTaskFormConditionService (defaults to CreateTask)
    );

    // (2) auto-approve preview — use $formKey instead of CreateTask
    $preview = $this->engine->previewResponsibles(
        $procedureType, $formKey, $companyId, $branchId, $dto->userId, $context,
    );

    // ... on the auto-approve branch:
    // (3) mark procedures taken for the SAME form key
    $this->markCreateTaskProceduresTaken($task, $dto->userId, $formKey);
}

// And update the helper signature + query:
private function markCreateTaskProceduresTaken(
    EmployeeTaskRequest $task,
    string $userId,
    string $formKey = 'createTask', // InternalProcessForm::CreateTask->value
): void {
    // ...
    $settings = ProcedureSetting::query()
        ->where('parent_id', $parentSetting->id)
        ->where('form', $formKey) // was hardcoded InternalProcessForm::CreateTask->value
        ->where('is_active', true)
        ->pluck('id');
    // ...
}
```

> **`EmployeeTaskFormConditionService::checkCreateTaskConditions()` change**: add a trailing `?string $formKey = null` arg defaulting to `CreateTask`, OR add the dedicated `checkCreateProjectNotificationConditions()` method (see §5.8.9) and call that instead. Either approach is acceptable; the dedicated method is cleaner and is the recommended path.

Then in `ProjectNotificationService::create`:

```php
public function create(CreateProjectNotificationDTO $dto): ProjectNotification
{
    $companyId = (string) tenant('id');
    $creator = User::find($dto->createdByUserId);
    $branchId = (string) $creator?->userProfessionalData?->branch_id;

    // 1. Preview responsibles to determine if auto-approved.
    $preview = $this->workflowEngine->previewResponsibles(
        ProcedureSettingType::EmployeeTask->value,
        InternalProcessForm::CreateProjectNotificationTask->value,
        $companyId,
        $branchId,
        $dto->createdByUserId,
        ['project_id' => $dto->projectId]
    );

    // 2. Create the ProjectNotification row.
    $notification = $this->repository->create([
        ...$dto->toArray(),
        'company_id' => $companyId,
        'status' => $preview['auto_approve'] ? 'approved' : 'pending',
    ]);

    // 3. Build the linked EmployeeTask DTO.
    //    The DTO already accepts itemType and itemId as constructor params.
    $taskDto = new CreateEmployeeTaskRequestDTO(
        userId: $dto->assignedUserId,
        title: $notification->notification_number,
        employee_task_type_id: $this->resolveProjectNotificationTypeId(),
        itemType: 'project_notification',
        itemId: $notification->id,
        durationHours: $dto->durationHours,
        taskDate: $dto->taskDate,
        taskLatitude: $dto->taskLatitude,
        taskLongitude: $dto->taskLongitude,
        currentLatitude: null,
        currentLongitude: null,
        description: $dto->workDescription,
        projectId: $dto->projectId,
        notes: $dto->notes,
        files: $dto->files,
    );

    // 4. Delegate to EmployeeTaskRequestService::create() with the dedicated form key.
    //    This handles: condition checks, workflow start, procedure-taken events,
    //    notification broadcasting, file uploads, and stale rejection job.
    $task = $this->employeeTaskRequestService->create(
        $taskDto,
        InternalProcessForm::CreateProjectNotificationTask->value
    );

    // 5. Link the task back to the notification and set dashboard-specific fields.
    $task->update([
        'project_notification_id' => $notification->id,
        'is_project_notification' => true,
        'sender_user_id' => $dto->createdByUserId,
        'task_source' => 'dashboard',
    ]);

    $notification->update(['employee_task_request_id' => $task->id]);

    // 6. Sync notification status from the task.
    $this->syncNotificationStatusFromTask($notification, $task);

    return $notification;
}
```

> **Why not manually create the task?** The previous version of this plan called `$this->employeeTaskRepository->create()` directly, which bypasses:
> - `checkCreateTaskConditions()` — condition evaluation
> - `previewResponsibles()` — auto-approve detection
> - `markCreateTaskProceduresTaken()` — `WorkflowProcedureTaken` event dispatch
> - `createProcessesForTask()` — Process + ProcessStep creation for manual approval
> - `dispatchStaleRejectionJob()` — auto-rejection of stale tasks
> - File upload handling
>
> Delegating to `EmployeeTaskRequestService::create()` with an optional `$formKey` parameter is the minimal upstream change that preserves all existing behavior while supporting the dedicated project-notification form.

#### 5.8.6 Approve/Reject Flow

When an approver acts on the workflow step:

- For **Process-based** workflows: call `ProcessWorkflowService::approveStep($processStepId)` or `rejectStep($processStepId)`.
- The `ProcessWorkflowService` fires `WorkflowStepActivated` / `WorkflowStepCompleted` events.
- The `SendWorkflowStepNotification` listener dispatches to `EmployeeTaskWorkflowNotifier` for `employee_task` processable type.
- After the final step is approved, call `ProjectNotificationService::approve()` to set `notification.status = approved` and `employee_task.status = approved`.
- On rejection, set both to `rejected` and store `rejection_reason`.

#### 5.8.6a Status Sync Mechanism (Task → Notification)

The `EmployeeTaskRequest` is the **single source of truth** for workflow state. The `ProjectNotification.status` is a denormalized mirror for dashboard convenience. To keep them in sync:

> **Verified correction**: there is currently **no `EmployeeTaskStatusChanged` event** in the EmployeeTask module. The **primary, recommended** mechanism is an Eloquent `updated` observer on `EmployeeTaskRequest` that fires only when `status` changed (`$task->wasChanged('status')`). The "listener on an event" approach below is a fallback **only if** you also introduce such an event. Register the observer in a service provider you already boot (e.g. `EmployeeTaskServiceProvider` or `ProjectManagementServiceProvider`).

```php
// modules/Project/ProjectManagement/Observers/EmployeeTaskStatusSyncObserver.php
public function updated(EmployeeTaskRequest $task): void
{
    if (! $task->wasChanged('status')) return;
    if (! $task->is_project_notification || ! $task->project_notification_id) return;

    $notification = ProjectNotification::find($task->project_notification_id);
    if (! $notification) return;

    $newStatus = self::STATUS_MAP[$task->status] ?? null;
    if ($newStatus && $notification->status !== $newStatus) {
        $notification->update(['status' => $newStatus]);
    }
}
```

**(Fallback) event-listener variant** on `EmployeeTaskStatusChanged` (only if such an event is added later):

```php
// modules/Project/ProjectManagement/Listeners/SyncNotificationStatusFromTask.php
class SyncNotificationStatusFromTask
{
    public function handle($event): void
    {
        $task = $event->task; // or EmployeeTaskRequest instance
        if (!$task->is_project_notification || !$task->project_notification_id) {
            return;
        }
        $notification = ProjectNotification::find($task->project_notification_id);
        if (!$notification) return;

        $statusMap = [
            EmployeeTaskStatus::Pending->value    => 'pending',
            EmployeeTaskStatus::Approved->value   => 'approved',
            EmployeeTaskStatus::Rejected->value   => 'rejected',
            EmployeeTaskStatus::InProgress->value => 'in_progress',
            EmployeeTaskStatus::Completed->value  => 'completed',
            EmployeeTaskStatus::Cancelled->value  => 'cancelled',
        ];

        $newStatus = $statusMap[$task->status] ?? null;
        if ($newStatus && $notification->status !== $newStatus) {
            $notification->update(['status' => $newStatus]);
        }
    }
}
```

**Register the observer (primary)** in a booted service provider:
```php
EmployeeTaskRequest::observe(EmployeeTaskStatusSyncObserver::class);
```

> Only use `Event::listen(EmployeeTaskStatusChanged::class, SyncNotificationStatusFromTask::class)` if you first add that event to the EmployeeTask module — it does not exist today.

#### 5.8.7 Link Procedure to EmployeeTaskRequest

Ensure the linked task stores:
- `procedure_setting_id` = parent `ProcedureSetting` ID for project notification tasks.
- `current_procedure_step_id` = first active `ProcedureSettingStep` ID if manual approval is required.
- `current_procedure_step_id` = null if auto-approved.

#### 5.8.8 Files to Modify for Procedure Integration

- `modules/Shared/InternalProcessType/Enums/InternalProcessForm.php` — add `CreateProjectNotificationTask`.
- `modules/Shared/InternalProcessType/Enums/InternalProcessCondition.php` — ensure `CreateProjectNotificationTask` uses the same conditions as `CreateTask` (if you want them configurable).
- `modules/ProcedureSetting/Database/Seeders/ProjectNotificationProcedureSeeder.php` — new seeder for parent + steps.
- `modules/ProcedureSetting/Database/Seeders/DatabaseSeeder.php` or `ProcedureSettingDatabaseSeeder.php` — call the new seeder.
- `modules/Project/ProjectManagement/Services/ProjectNotificationService.php` — integrate workflow resolution and start.
- `modules/Project/ProjectManagement/Controllers/ProjectNotificationController.php` — add `approve` / `reject` methods that delegate to the workflow engine.
- `modules/EmployeeTask/Services/EmployeeTaskRequestService.php` — add optional `?string $formKey = null` parameter to `create()` method.
- `modules/Project/ProjectManagement/Listeners/SyncNotificationStatusFromTask.php` — new listener for status sync.
- `modules/Project/ProjectManagement/Providers/ProjectManagementServiceProvider.php` — register the status sync listener.
- `config/octane.php` — add `ProjectNotificationService` and `ProjectNotificationLocationService` to flush list as precaution (both should be stateless).

### 5.8.9 Condition Evaluator Integration

The project-notification create form can reuse the existing centralized condition evaluation system. Since the form is a new `InternalProcessForm` case, the conditions defined for that case will be stored in `procedure_settings.conditions` and enforced before the workflow starts.

#### How to apply existing conditions

The `EmployeeTaskFormConditionService` already resolves the child procedure setting by form key and evaluates all conditions using `ConditionEvaluationService::evaluateAndThrow()`. We can extend it to support the new form by adding a new public method:

```php
public function checkCreateProjectNotificationConditions(
    string $userId,
    string $companyId,
    ?string $branchId,
    float $durationHours,
    string $taskDate,
    float $taskLatitude,
    float $taskLongitude,
    ?float $currentLatitude = null,
    ?float $currentLongitude = null,
): void {
    $setting = $this->procedureWorkflowService->resolveInternalProcedureSettingByForm(
        ProcedureSettingType::EmployeeTask->value,
        InternalProcessForm::CreateProjectNotificationTask->value,
        $companyId,
        $branchId
    );

    $map = $this->resolveConditionMap($setting);

    $ctx = new ConditionContext(
        userId: $userId,
        companyId: $companyId,
        branchId: $branchId,
        currentLatitude: $currentLatitude,
        currentLongitude: $currentLongitude,
        taskLatitude: $taskLatitude,
        taskLongitude: $taskLongitude,
        durationHours: $durationHours,
        taskDate: $taskDate,
    );

    $this->evaluationService->evaluateAndThrow($this->registry, $map, $ctx, $this->resolver);
}
```

> **Avoid double-evaluation**: `EmployeeTaskRequestService::create()` already runs the condition check internally (now form-key-aware after the §5.8.5 change). Pick **one** place to evaluate conditions:
> - **Recommended**: let `EmployeeTaskRequestService::create()` do it (pass the `CreateProjectNotificationTask` form key), and do **not** call the condition service again from `ProjectNotificationService`.
> - **Alternative**: if you prefer to fail fast before creating the `ProjectNotification` row, call `checkCreateProjectNotificationConditions()` in `ProjectNotificationService::create()` and make the inner service call skip its own check (e.g., pass a flag). Do not run both.

Example of the standalone condition method (use only if you choose the "fail fast" path):

```php
$this->employeeTaskFormConditionService->checkCreateProjectNotificationConditions(
    userId: $dto->assignedUserId,
    companyId: $companyId,
    branchId: $branchId,
    durationHours: $dto->durationHours,
    taskDate: $dto->taskDate,
    taskLatitude: $dto->taskLatitude,
    taskLongitude: $dto->taskLongitude,
    currentLatitude: null,
    currentLongitude: null,
);
```

> **No new evaluators needed** for v1 because we reuse the same conditions as `CreateTask`. If the product later requires notification-specific rules (e.g., `MaxDistanceToTask`), add a new `InternalProcessCondition` case and a new evaluator class following the guide in `docs/CONDITION_EVALUATOR_IMPLEMENTATION_GUIDE.md`.

#### Adding a notification-specific condition (future)

If a condition like `MaxDistanceToTask` is needed:

1. Add `case MaxDistanceToTask = 'max_distance_to_task';` to `InternalProcessCondition`.
2. Add `settingsSchema()` entry for the condition.
3. Register it in `InternalProcessForm::CreateProjectNotificationTask` conditions list.
4. Create `modules/Project/ProjectManagement/Conditions/MaxDistanceToTaskEvaluator.php` implementing `ConditionEvaluator`.
5. Register it in a new `ProjectNotificationConditionEvaluatorRegistry` or extend `EmployeeTaskConditionEvaluatorRegistry` if the condition is shared.
6. Map the exception in a new `ProjectNotificationExceptionResolver` (or extend the existing one).

#### Files to modify for conditions

- `modules/EmployeeTask/Services/EmployeeTaskFormConditionService.php` — add `checkCreateProjectNotificationConditions()`.
- `modules/Project/ProjectManagement/Services/ProjectNotificationService.php` — call the condition check before creating the linked task.
- `modules/Project/ProjectManagement/Exceptions/ProjectNotificationException.php` — new dedicated exception class for module-specific errors (preferred over reusing `EmployeeTaskException` for clearer error source identification).

### 5.9 Phase 9 — Notifications & Events

#### 5.9.1 Reuse Existing EmployeeTask Events

When the linked task is created/approved, dispatch:
- `EmployeeTaskNotification` — to the assigned user's websocket channel.
- `InboxCountsUpdated` — to update the employee's inbox badge.
- Optionally `WorkflowProcedureTaken` — to trigger the next procedure step.

#### 5.9.2 Email Notification

Create a new Mailable: `ProjectNotificationAssignedMail` (or reuse `EmployeeAssignedMail` if the shape is similar).

Send to the assigned employee when the notification/task is approved.

### 5.10 Phase 10 — Presenters

#### 5.10.1 Create `ProjectNotificationPresenter`

Formats:
- Detail view (for step 5 summary and show endpoint):
  - notification metadata
  - contractor info
  - location (lat/lng, radius, link, map URL)
  - assigned employee (user info + distance)
  - linked task status and workflow state
- List view (for the tab table):
  - notification_number, notification_type, work_type, contractor_name, assigned_user_name, status badge, distance, created_at
- Wizard step 4 view:
  - employee list with location, distance, status color

#### 5.10.2 Create `ProjectNotificationEmployeeLocationPresenter`

Formats the employee location + distance response for the map/list in step 4.

---

## 6. Frontend Implementation Plan

> **Detailed frontend-only guide (components, APIs, state, translations, testing)** is in `@C:\projects\constrix-microservices\constrix_api\PROJECT_NOTIFICATION_TASK_FRONTEND_GUIDE.md`. The section below is the architectural summary.

### 6.1 Phase 1 — New Tab & Route

#### 6.1.1 Add Schema-Driven Tab

The project detail page renders tabs based on the `ProjectType` schemas returned by `GET /api/v1/project-types/{id}/schemas`. Add the new schema `12` (الصيانة والطوارئ) to the schema seeder so the backend returns it.

In the frontend project detail component:
- Add a new tab route/component mapped to schema key `12` or schema name `الصيانة والطوارئ`. Inside it, render the **الإشعارات** sub-view (v1); leave placeholders for المخالفات / التقارير / المؤشرات.
- The tab label should be `الصيانة والطوارئ` (or translatable).
- Check `PROJECT_NOTIFICATION_LIST` permission before showing the Notifications sub-view (in addition to checking if schema `12` is present).

#### 6.1.2 Add Route/View for Notifications

Create a new view component:
- `ProjectNotificationsView.vue` (or React equivalent)
- Mounted when the `الإشعارات` tab is active.
- Loads notifications via `GET /api/v1/projects/notifications?project_id={id}`.

### 6.2 Phase 2 — Notifications List UI

#### 6.2.1 List Layout

Match the existing project detail table style (RTL, dark theme, action buttons on the right, status badges).

Columns:
- رقم الإشعار (Notification Number)
- نوع الإشعار (Notification Type)
- نوع العمل (Work Type)
- المقاول (Contractor Name)
- المهندس المكلف (Assigned Engineer)
- الموقع (Location — clickable to open map)
- جهة الإسناد (Assignment Party — branch/management)
- رقم المغذي (Magdy Number)
- المسافة (Distance)
- حالة الإشعار (Status badge)
- الإجراءات (Actions: view, edit if pending, delete, approve/reject if permission)

#### 6.2.2 Actions Bar

- **إضافة إشعار** (Add Notification) — opens the wizard side-modal.
- **تصدير** (Export) — calls `POST /api/v1/projects/notifications/export`.
- **بحث** (Search) — filters by notification number, contractor, or engineer.
- **الأعمدة** (Columns) — column visibility toggle.
- **الخريطة** (Map) — toggle to show notifications on a project map (optional v2).
- **Filters**: status, type, date range, employee.

### 6.3 Phase 3 — Wizard Form

Create a reusable side-modal wizard component: `CreateProjectNotificationWizard.vue`.

#### 6.3.1 Stepper Header

Show 5 steps with icons and Arabic labels:
1. بيانات الإشعار
2. بيانات المقاول
3. الموقع
4. الإسناد لموظف
5. تأكيد الإرسال

Allow navigation back and forth. Validate each step before enabling **Next**.

#### 6.3.2 Step 1 — Notification Info

Fields:
- **نوع الإشعار** (Notification Type) — dropdown with predefined types: `صيانة`, `طوارئ`, `إصلاح عطل`, `فحص`, `تركيب`, `إزالة`. Store in config/translations.
- **رقم الإشعار** (Notification Number) — auto-generated, read-only or editable with uniqueness check.
- **رقم المغذي** (Magdy Number) — text input.
- **وصف العمل** (Work Description) — textarea, required.

#### 6.3.3 Step 2 — Contractor Info

Fields:
- **اسم المقاول** (Contractor Name) — text, required.
- **رقم المقاول** (Contractor Number) — text.
- **رقم فني المقاول** (Contractor Technical Number) — text.
- **فني المقاول** (Contractor Category) — dropdown/select.
- **ملاحظات المقاول** (Contractor Notes) — textarea.
- **رقم الجوال** (Mobile Number) — text with validation.

#### 6.3.4 Step 3 — Location

Layout: split screen — map on left/top, fields on right/bottom.

Map features:
- Show project default location if available (center map).
- Allow clicking on the map to drop a pin.
- Show pin with radius circle (use `location_radius` value).
- Drag pin to adjust.

Fields:
- **نقطة على الخريطة** / **نقطة الإصلاح** (Repair Point) — name of the location.
- **خط العرض** (Latitude) — decimal, synced with map pin.
- **خط الطول** (Longitude) — decimal, synced with map pin.
- **نطاق الإصلاح** (Repair Radius) — integer meters, controls circle radius.
- **رابط الموقع** (Location Link) — auto-generated or paste Google Maps link.

Actions:
- **نقطة على الخريطة** (Drop Pin) — centers map on current pin.
- **إحداثيات** (Coordinates) — button to fill lat/lng from current pin.
- **رابط من جوال** (Link from Mobile) — parse a Google Maps URL into lat/lng.
- Show success message: **تم تحديد الموقع بنجاح** (Location confirmed successfully).

#### 6.3.5 Step 4 — Assign to Employee

Layout: split screen — map on left, employee list on right.

Map features:
- Show notification location pin (center).
- Show radius circle.
- Show employee markers with different colors based on status (available=green, busy=orange, offline=gray, no_location=red).
- Cluster markers if many employees.
- Clicking a marker selects the employee.
- Show polyline from employee to notification location (optional).

Employee list features:
- Table/list columns: إسناد (radio/select), اسم المهندس, الحالة, المسافة, الموقع الحالي.
- Status badge with color dot.
- Distance in km or meters (e.g., `1.2 كم`, `350 م`).
- Show latest activity time (e.g., `منذ 5 دقائق`).
- Search/filter by name or status.
- Branch and status dropdowns at top.
- **البحث عن مهندس** (Search Engineer) text field.

Selection:
- Radio button or single-select. The selected employee is highlighted in both map and list.
- On selection, store `assigned_user_id` and `selected_distance_meters`.

Data loading:
- Call `GET /api/v1/projects/notifications/employees-with-locations?project_id={id}&latitude={lat}&longitude={lng}`.
- Poll every 30-60 seconds to refresh locations (optional, can be manual refresh).

#### 6.3.6 Step 5 — Confirm & Send

Show summary cards for each step:
- **بيانات الإشعار** — notification number, type, Magdy number, work description.
- **بيانات المقاول** — contractor name, number, technical number, category, mobile.
- **الموقع** — mini map thumbnail, coordinates, radius, link.
- **الإسناد** — selected engineer name, status, distance.

Checkboxes:
- **تمت مراجعة البيانات والتأكد من صحتها** (Data reviewed and confirmed).
- **جاهز للإرسال** (Ready to send).

Buttons:
- **إرسال الإشعار** (Send Notification) — primary pink button, submits the full payload.
- **السابق** (Previous) — back to step 4.
- **إلغاء** (Cancel) — close wizard.

After submission:
- Show success toast.
- Close wizard.
- Refresh notifications list.
- Optionally open the newly created notification detail.

### 6.4 Phase 4 — Map Component Design

#### 6.4.1 Map Library

Use the same map library already used in the Attendance/EmployeeTask modules (likely Google Maps or Leaflet). Ensure consistency.

#### 6.4.2 Component Responsibilities

Create reusable components:
- `ProjectNotificationMap.vue` — shows pin + radius circle + employee markers.
- `EmployeeLocationMarker.vue` — marker with status color and popup.
- `ProjectNotificationRadiusCircle.vue` — radius circle around notification pin.

#### 6.4.3 Coordinate Sync

- Two-way binding between map pin and latitude/longitude input fields.
- When user types lat/lng, update pin.
- When user drags pin, update lat/lng.
- When location link is parsed, update pin and lat/lng.

### 6.5 Phase 5 — Status & Badges

Status badge mapping for the list and wizard:

| Status | Arabic Label | Color |
|--------|--------------|-------|
| `pending` | بانتظار الرد | Yellow |
| `approved` | مقبول | Green |
| `rejected` | مرفوض | Red |
| `in_progress` | قيد التنفيذ | Blue |
| `completed` | مكتمل | Teal |
| `cancelled` | ملغي | Gray |

For the wizard employee status:
- `available` — متاح (green)
- `busy` — مشغول (orange)
- `offline` — غير متصل (gray)
- `no_location` — لا يوجد موقع (red/pink)

### 6.6 Phase 6 — Permissions & Error Handling

- Check `PROJECT_NOTIFICATION_CREATE` before showing the **Add Notification** button.
- Check `PROJECT_NOTIFICATION_UPDATE` before showing edit/approve/reject actions.
- Check `PROJECT_NOTIFICATION_DELETE` before showing delete action.
- For project-role-scoped access, also check `ProjectPermission::PROJECT_NOTIFICATION_CREATE` etc. via `GET /api/v1/projects/{project_id}/my-permissions/flat`.
- Show permission-denied messages or hide actions based on the user's project-specific permissions.
- Handle validation errors per step (highlight fields, show Arabic messages).
- Handle network errors gracefully (retry button, offline indicator).

---

## 7. API Contract

### 7.1 Create Project Notification

```http
POST /api/v1/projects/notifications
```

Payload:

```json
{
  "project_id": "uuid",
  "notification_type": "صيانة",
  "severity": "متوسط",
  "magdy_number": "MG-45-02",
  "work_type": "كهرباء",
  "work_description": "انقطاع التيار الكهربائي في الكابل الأرضي",
  "contractor_name": "شركة الكهرباء المتقدمة",
  "contractor_number": "C-2024-0456",
  "contractor_technical_number": "FT-7789",
  "contractor_category": "محمد السبيعي",
  "contractor_notes": "",
  "contractor_mobile": "05xxxxxxxx",
  "task_latitude": 24.7136,
  "task_longitude": 46.6753,
  "location_radius": 250,
  "location_link": "https://maps.google.com/?q=24.7136,46.6753",
  "repair_point": "حي الروضة",
  "assigned_user_id": "uuid",
  "selected_distance_meters": 3200,
  "task_date": "2026-06-27",
  "duration_hours": 4.0,
  "notes": "",
  "approval_responsible_id": "uuid",
  "assignment_responsible_id": "uuid"
}
```

Response: `201 Created` with the created notification detail.

### 7.2 List Project Notifications

```http
GET /api/v1/projects/notifications?project_id={uuid}&status=&type=&page=1&per_page=10
```

Response: `Json::items()` pagination with notification list rows.

### 7.3 Get Employees with Locations

```http
GET /api/v1/projects/notifications/employees-with-locations?project_id={uuid}&latitude=24.7136&longitude=46.6753&radius=50000
```

Response:

```json
{
  "data": [
    {
      "user_id": "uuid",
      "name": "أحمد العتيبي",
      "status": "available",
      "status_label": "متاح",
      "distance_meters": 3200,
      "distance_label": "3.2 كم",
      "last_update": "2026-06-27 09:45:00",
      "location": {
        "latitude": 24.7400,
        "longitude": 46.6800,
        "accuracy": 5,
        "source": "GPS"
      },
      "attendance": {
        "id": "uuid",
        "status": "active",
        "clock_in_time": "08:00:00"
      }
    }
  ]
}
```

### 7.4 Approve/Reject Notification

```http
POST /api/v1/projects/notifications/{id}/approve
POST /api/v1/projects/notifications/{id}/reject
```

Reject payload:

```json
{
  "reason": "الموظف غير متاح"
}
```

---

## 8. Data Flow Diagrams

### 8.1 Create Notification Flow

```
┌─────────────┐     ┌──────────────────────┐     ┌─────────────────────┐
│ Frontend    │────►│ ProjectNotification  │────►│ Generate NTF number │
│ Wizard      │     │ Controller           │     │ (unique per company)│
└─────────────┘     └──────────────────────┘     └─────────────────────┘
                              │                            │
                              ▼                            ▼
                    ┌─────────────────────┐      ┌────────────────────┐
                    │ CreateProjectNotif. │      │ Create EmployeeTask│
                    │ Service             │────►│ Request (type=     │
                    │                     │      │ project_notification)│
                    └─────────────────────┘      └────────────────────┘
                              │                            │
                              ▼                            ▼
                    ┌─────────────────────┐      ┌────────────────────┐
                    │ Save ProjectNotif.  │      │ Trigger Procedure  │
                    │ row                 │      │ Workflow           │
                    └─────────────────────┘      └────────────────────┘
                              │                            │
                              ▼                            ▼
                    ┌─────────────────────┐      ┌────────────────────┐
                    │ Link task_id to notif.│      │ Fire events:       │
                    │                       │      │ EmployeeTaskNotification
                    └─────────────────────┘      │ InboxCountsUpdated │
                                                   └────────────────────┘
```

### 8.2 Employee Location Flow

```
┌─────────────────────┐     ┌─────────────────────────────┐     ┌─────────────────────┐
│ Wizard Step 4       │────►│ GET /notifications/employees│────►│ Load ProjectEmployee│
│ (frontend)          │     │ -with-locations             │     │ records for project │
└─────────────────────┘     └─────────────────────────────┘     └─────────────────────┘
                                          │                            │
                                          ▼                            ▼
                                ┌─────────────────────┐      ┌────────────────────┐
                                │ For each user_id    │────►│ Get latest Attendance│
                                │                     │      │ row today            │
                                └─────────────────────┘      └────────────────────┘
                                          │                            │
                                          ▼                            ▼
                                ┌─────────────────────┐      ┌────────────────────┐
                                │ Extract latest    │      │ Calculate Haversine│
                                │ location point    │────►│ distance to task   │
                                └─────────────────────┘      └────────────────────┘
```

---

## 9. Implementation Phases & Estimates

| Phase | Scope | Estimated Effort |
|-------|-------|------------------|
| 1 | Schema seeder, EmployeeTask type + item, ProcedureSetting config | 1 day |
| 2 | Migrations (notifications + counters), permissions (dual system), config | 0.5 day |
| 3 | Models, relationships, DTOs, requests, filter, observer | 1.5 days |
| 4 | Repository, ProjectNotificationService, serial numbers, linked task creation via EmployeeTaskRequestService | 1.5 days |
| 5 | Location service, batch distance calculation, employee status | 1 day |
| 6 | Controllers, routes, presenters, exception | 1 day |
| 7 | Procedure workflow integration, status sync listener, EmployeeTaskRequestService formKey param | 1.5 days |
| 8 | Events, emails, websocket, Octane config | 0.5 day |
| 9 | Frontend tab, list, filters, export | 1.5 days |
| 10 | Frontend wizard (5 steps), map, employee selection | 3 days |
| 11 | Postman collection, integration, testing, bug fixes | 2.5 days |
| **Total** | | **~16.5 days** (1 developer) |

---

## 10. Risks & Considerations

| Risk | Impact | Mitigation |
|------|--------|------------|
| **Attendance location data missing** | Distance cannot be calculated | Fallback to clock-in location; show "no location" status; allow manual assignment. |
| **EmployeeTask single-user assumption** | Currently one user per task | Create one linked `EmployeeTaskRequest` per assigned user if multiple assignment is needed. |
| **ProcedureSetting form enum change** | Requires enum update and seeder | Add dedicated form value; seed parent/child procedure settings per tenant. |
| **Concurrent notification number generation** | Duplicate numbers | Use `DB::transaction` + `selectForUpdate` or an atomic counter table. |
| **Map library inconsistency** | Different map libraries in modules | Reuse the same library used by EmployeeTask/Attendance. |
| **ProjectType schema sync on existing projects** | Old projects won't show the tab | Run `SchemaSeeder` after deployment; attach new schema to existing project types. |
| **Real-time location staleness** | Employees may appear offline | Implement polling and last-update threshold; show timestamp. |
| **Permission key conflicts** | New keys may overlap | Use dual system: global `PROJECT_NOTIFICATION_*` in `modules/Project/Config/permissions.php` + project-specific in `modules/Project/ProjectManagement/Resources/config/config.php`. Follow existing naming pattern. |
| **EmployeeTask workflow status mismatch** | Notification status and task status may diverge | Listen to task events and sync notification status; single source of truth is the linked task for workflow state. |
| **Mobile map performance** | Many employees + tracking points | Limit tracking points to last 4 (as LiveTrackingPresenter does) and use marker clustering. |

---

## 11. Testing Strategy

### 11.1 Backend Tests

- **Unit tests**:
  - Serial number generation (uniqueness, format, concurrency via counter table).
  - Haversine distance calculation.
  - Employee status derivation (available, busy, offline, no_location).
  - ProjectNotificationObserver `creating` event.
  - Status sync listener maps all EmployeeTaskStatus values correctly.
- **Feature tests**:
  - Create project notification with all wizard fields.
  - Create notification with auto-approved procedure.
  - Create notification with manual procedure and approval flow.
  - List/filter notifications (test all filter fields).
  - Get employees with locations endpoint (verify batch query, no N+1).
  - Approve/reject notification.
  - Export notifications.
  - `EmployeeTaskItem` validation — creating notification with `item_type = 'project_notification'` fails if item row doesn't exist.
- **Integration tests**:
  - Attendance location tracking integration.
  - EmployeeTask workflow integration (verify `EmployeeTaskRequestService::create()` is called with correct form key).
  - Email/websocket event dispatch.
  - Status sync: task status change propagates to notification status.
  - Multi-tenant isolation: notifications from company A not visible to company B.

### 11.2 Frontend Tests

- **Component tests**:
  - Wizard step navigation and validation.
  - Map pin drag updates lat/lng inputs.
  - Employee selection updates state.
- **E2E tests**:
  - Full wizard flow from tab open to notification submission.
  - Notification list filters and pagination.
  - Approve/reject actions.

### 11.3 Manual QA Checklist

- [ ] New tab appears only when schema is attached.
- [ ] Tab hidden when permission is missing.
- [ ] Wizard validates each step before next.
- [ ] Map pin and coordinate fields are synchronized.
- [ ] Employee distance is correctly calculated and sorted.
- [ ] Selected employee appears in confirmation step.
- [ ] Notification number is unique per company.
- [ ] Linked EmployeeTaskRequest is created with correct type.
- [ ] Procedure workflow triggers for manual approval.
- [ ] Assigned employee receives notification when approved.
- [ ] Export produces correct Excel file.

---

## 12. Files to Create / Modify

### 12.1 New Files

#### Backend
- `modules/Project/ProjectManagement/Database/Migrations/2026_06_27_000001_create_project_notifications_table.php`
- `modules/Project/ProjectManagement/Database/Migrations/2026_06_27_000003_create_project_notification_counters_table.php`
- `modules/Project/ProjectManagement/Models/ProjectNotification.php`
- `modules/Project/ProjectManagement/Repositories/ProjectNotificationRepository.php`
- `modules/Project/ProjectManagement/Filters/ProjectNotificationFilter.php`
- `modules/Project/ProjectManagement/Observers/ProjectNotificationObserver.php`
- `modules/Project/ProjectManagement/DTO/CreateProjectNotificationDTO.php`
- `modules/Project/ProjectManagement/DTO/UpdateProjectNotificationDTO.php`
- `modules/Project/ProjectManagement/DTO/FilterProjectNotificationDTO.php`
- `modules/Project/ProjectManagement/Requests/CreateProjectNotificationRequest.php`
- `modules/Project/ProjectManagement/Requests/UpdateProjectNotificationRequest.php`
- `modules/Project/ProjectManagement/Requests/FilterProjectNotificationsRequest.php`
- `modules/Project/ProjectManagement/Requests/GetProjectNotificationEmployeesRequest.php`
- `modules/Project/ProjectManagement/Services/ProjectNotificationService.php`
- `modules/Project/ProjectManagement/Services/ProjectNotificationLocationService.php`
- `modules/Project/ProjectManagement/Controllers/ProjectNotificationController.php`
- `modules/Project/ProjectManagement/Presenters/ProjectNotificationPresenter.php`
- `modules/Project/ProjectManagement/Presenters/ProjectNotificationEmployeeLocationPresenter.php`
- `modules/Project/ProjectManagement/Exports/ProjectNotificationExport.php`
- `modules/Project/ProjectManagement/Exceptions/ProjectNotificationException.php`
- `modules/Project/ProjectManagement/Listeners/SyncNotificationStatusFromTask.php`
- `modules/Project/ProjectManagement/Mail/ProjectNotificationAssignedMail.php` (or similar)
- `ProjectNotification_API.postman_collection.json` (project root — Postman collection for API testing)

#### Frontend
- `resources/js/Pages/Project/Notifications/ProjectNotificationsView.vue` (or equivalent)
- `resources/js/Pages/Project/Notifications/CreateProjectNotificationWizard.vue`
- `resources/js/Pages/Project/Notifications/ProjectNotificationMap.vue`
- `resources/js/Pages/Project/Notifications/EmployeeLocationMarker.vue`
- `resources/js/Pages/Project/Notifications/ProjectNotificationRadiusCircle.vue`
- `resources/js/Pages/Project/Notifications/ProjectNotificationStatusBadge.vue`
- `resources/js/Pages/Project/Notifications/useProjectNotificationWizard.js` (or composable)

### 12.2 Files to Modify

#### Backend
- `modules/Project/ProjectType/Database/Seeders/SchemaSeeder.php` — add schema `12`.
- `modules/EmployeeTask/Database/Seeders/EmployeeTaskTypeSeeder.php` (or create one) — add `project_notification` type.
- `modules/EmployeeTask/Database/Seeders/EmployeeTaskItemSeeder.php` (or create one) — add `project_notification` item (required for `item_type` validation).
- `modules/EmployeeTask/Database/Migrations/2026_06_27_000002_add_project_notification_fields_to_employee_task_requests.php` — extend task table.
- `modules/EmployeeTask/Models/EmployeeTaskRequest.php` — add relationships (`projectNotification`, `sender`).
- `modules/EmployeeTask/Services/EmployeeTaskRequestService.php` — add optional `?string $formKey = null` parameter to `create()` method.
- `modules/Project/Config/permissions.php` — add global `PROJECT_NOTIFICATION_*` permission keys.
- `modules/Project/ProjectManagement/Resources/config/config.php` — add project-specific `PROJECT_NOTIFICATION_*` permission keys.
- `modules/Project/ProjectManagement/Database/Seeders/ProjectPermissionsSeeder.php` — seed new permissions.
- `modules/Project/ProjectManagement/Models/ProjectManagement.php` — add `notifications()` relationship.
- `modules/Project/ProjectManagement/Resources/routes/api.php` — add notification routes.
- `modules/Project/ProjectManagement/Providers/ProjectManagementServiceProvider.php` — register observer + status sync listener.
- `modules/Shared/InternalProcessType/Enums/InternalProcessForm.php` — add `CreateProjectNotificationTask` case + arms in `conditions()` and `applicableTypes()`. (Verified path. `modules/ProcedureSetting/Enums/InternalProcessForm.php` does **not** exist.)
- `modules/ProcedureSetting/Database/Seeders/ProcedureSettingSeeder.php` (or create new seeder) — seed maintenance/notification procedure.
- `config/octane.php` — add `ProjectNotificationService` and `ProjectNotificationLocationService` to flush list.

#### Frontend
- Project detail page component — add new tab for schema `12`.
- `resources/js/lang/ar.js` / `en.js` — add Arabic/English translations for notification labels, statuses, wizard steps.
- Existing EmployeeTask map components — reuse if compatible, otherwise adapt.

---

## 13. Open Questions for the User

**Resolved:**
- ✅ **C.1 — Tab placement & task surface**: Confirmed it's a project-details schema tab (**الصيانة والطوارئ**, schema `12`), notifications is its first sub-view, and the linked task is surfaced via the existing EmployeeTask module (**مهام العمل المسندة**). No dedicated mobile view needed.

**Still open:**
1. **Multiple employees per notification?** The wizard screenshot shows single-selection radio buttons. Should the backend support multiple employees, or enforce exactly one?
2. **Notification number format?** Proposed `NTF-{YEAR}-{00001}`. Confirm or provide the exact format.
3. **Notification type options?** Provide the full list (e.g., `صيانة`, `طوارئ`, `إصلاح عطل`, `فحص`, ...) and the **severity** values (`منخفض`/`متوسط`/`عالي`?).
4. **Contractor category values?** Are these predefined or free text?
5. **Procedure workflow detail?** Who approves by default — manager, project manager, or branch supervisor? And: do we need a **dedicated** parent procedure (`مهام الصيانة والطوارئ`) or reuse the existing `employee_task` parent (see §5.8.3)?
6. **Extra procedure forms?** The procedure UI shows `تأكيد التواجد` (confirm presence) and `تحديث` (update/extend) buttons. The enum has **no** `ExtendTask`/confirm-presence cases today — should we add new `InternalProcessForm` cases for these, or are `StartTask`/`EndTask` sufficient for v1?
7. **Violations in v1?** Is المخالفات required now, or placeholder-only (see §3.7)?
8. **Location refresh interval?** Wizard poll cadence for live locations. (Default: 30 seconds.)
9. **Distance threshold?** Warn if the selected employee is beyond a configured radius? (Default: warn, don't block.)
10. **Files/attachments?** The wizard shows no upload fields. Support attachments in v1?

---

## 14. Summary of Next Steps

1. **Confirm scope** — answer the open questions above, especially single vs. multiple employees and notification number format.
2. **Create migrations** — run the database changes first.
3. **Update seeders** — schema, EmployeeTask type, permissions, procedure setting.
4. **Implement backend model & services** — `ProjectNotification` model, service, location service.
5. **Implement APIs** — controller, routes, presenters.
6. **Implement frontend tab & wizard** — start with the list and wizard shell, then add map and location logic.
7. **Integrate procedure workflow** — create dedicated procedure setting and link to EmployeeTask.
8. **Test end-to-end** — create notification from dashboard, verify linked task, approve, check employee inbox.
