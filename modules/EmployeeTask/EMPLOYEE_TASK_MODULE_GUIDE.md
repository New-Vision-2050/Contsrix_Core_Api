# EmployeeTask Module — Comprehensive Guide

> **Module alias:** `employeetask`  
> **Namespace:** `Modules\EmployeeTask`  
> **Description:** Employee Work Task Request system — parallel to Attendance, never writes to attendance tables.  
> **API prefix:** `api/v1/employee-tasks` (employee) / `api/v1/admin/employee-tasks` (admin)  
> **Middleware:** `api`, `auth:api`, `InitializeTenancyByRequestData`

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Module Structure](#2-module-structure)
3. [Database Schema & Migrations](#3-database-schema--migrations)
4. [Models](#4-models)
5. [Enums](#5-enums)
6. [DTOs](#6-dtos)
7. [Services](#7-services)
8. [Conditions System](#8-conditions-system)
9. [Events & Broadcasting](#9-events--broadcasting)
10. [Jobs](#10-jobs)
11. [Repositories](#11-repositories)
12. [Presenters](#12-presenters)
13. [Controllers & Routes](#13-controllers--routes)
14. [Exceptions](#14-exceptions)
15. [Support Utilities](#15-support-utilities)
16. [Form Requests](#16-form-requests)
17. [Workflow Integration](#17-workflow-integration)
18. [Lifecycle State Machine](#18-lifecycle-state-machine)
19. [API Reference](#19-api-reference)

---

## 1. Architecture Overview

The EmployeeTask module is a **multi-tenant, workflow-driven employee task management system**. It runs parallel to the Attendance module but never writes to attendance tables. Key architectural principles:

- **Procedure Workflow Integration**: All major actions (create, extend, end, approve) flow through a configurable multi-step approval workflow powered by `ProcedureSetting` and `Process` modules.
- **Condition Evaluation**: Pre-action validation using a registry-driven condition evaluator system (shift checks, holidays, location, duration, date constraints).
- **Location-Based Services**: GPS tracking with radius checks, polygon-based custom locations, and auto-close on out-of-location.
- **Session Tracking**: Tasks track active/paused sessions with start/end times, durations, and GPS coordinates.
- **Real-Time Notifications**: Broadcasting via Reverb websockets for task notifications and inbox count updates.
- **Multi-Tenancy**: Every model carries `company_id`; jobs initialize tenancy before execution.

### Dependency Graph

```
EmployeeTask
├── ProcedureSetting (workflow engine, conditions, procedure taken events)
├── Process (process/step tracking for approval workflows)
├── Attendance (constraint service for shift/holiday/location lookups)
├── User (user model, professional data, branch timezone)
├── ProjectManagement (optional project association)
├── Shared\Media (Spatie media library for file attachments)
└── BasePackage\Shared (JSON response presenter)
```

---

## 2. Module Structure

```
modules/EmployeeTask/
├── Conditions/
│   ├── AllowDuringShiftEvaluator.php
│   ├── AllowOnHolidaysEvaluator.php
│   ├── AllowOutsideShiftEvaluator.php
│   ├── InsideCustomLocationsEvaluator.php
│   ├── MaxTaskDurationEvaluator.php
│   ├── MaxScheduledDateOffsetEvaluator.php
│   ├── ConditionEvaluator.php          (deprecated → extends ProcedureSetting)
│   ├── ConditionContext.php            (deprecated → extends ProcedureSetting)
│   ├── ConditionResult.php             (deprecated → extends ProcedureSetting)
│   ├── EmployeeTaskExceptionResolver.php
│   └── ResolvesUserAttendance.php      (trait)
├── Controllers/
│   ├── EmployeeTaskController.php       (employee-facing)
│   ├── AdminEmployeeTaskController.php  (admin-facing)
│   └── EmployeeTaskTypeController.php
├── Database/
│   └── Migrations/
│       ├── 2026_05_20_000001_create_employee_task_requests_table.php
│       ├── 2026_05_20_000002_create_employee_task_sessions_table.php
│       ├── 2026_05_20_000003_create_employee_task_extension_requests_table.php
│       ├── 2026_05_20_000004_add_current_procedure_step_to_employee_task_requests_table.php
│       ├── 2026_05_20_000005_add_workflow_to_employee_task_extension_requests_table.php
│       ├── 2026_05_21_000005_make_serial_number_unique_per_company_on_employee_task_requests.php
│       ├── 2026_05_21_000010_create_employee_task_approval_requests_table.php
│       ├── 2026_05_21_000011_add_location_confirmed_at_to_employee_task_requests.php
│       ├── 2026_06_14_000001_add_internal_process_type_id_to_employee_task_requests.php
│       ├── 2026_06_14_000002_add_procedure_setting_id_to_extension_and_approval_requests.php
│       ├── 2026_06_15_000002_remove_internal_process_type_id_and_table.php
│       ├── 2026_06_16_000000_create_employee_task_types_table.php
│       ├── 2026_06_16_000001_add_employee_task_type_id_to_employee_task_requests_table.php
│       ├── 2026_06_16_000002_create_employee_task_items_table.php
│       ├── 2026_06_18_000001_create_employee_task_end_requests_table.php
│       ├── 2026_06_19_000001_add_taken_internal_procedure_ids_to_employee_task_requests.php
│       └── 2026_06_19_000003_drop_taken_internal_procedure_ids_from_employee_task_requests.php
├── DTO/
│   ├── CreateEmployeeTaskRequestDTO.php
│   ├── StartTaskDTO.php
│   ├── EndTaskDTO.php
│   └── CreateExtensionRequestDTO.php
├── Enums/
│   ├── EmployeeTaskStatus.php
│   └── EmployeeTaskExtensionStatus.php
├── Events/
│   ├── EmployeeTaskNotification.php
│   └── InboxCountsUpdated.php
├── Exceptions/
│   └── EmployeeTaskException.php
├── Jobs/
│   ├── AutoCloseTaskAtDurationExpiryJob.php
│   ├── AutoCloseTaskIfOutOfLocationJob.php
│   └── AutoRejectStaleTaskJob.php
├── Models/
│   ├── EmployeeTaskRequest.php
│   ├── EmployeeTaskSession.php
│   ├── EmployeeTaskExtensionRequest.php
│   ├── EmployeeTaskApprovalRequest.php
│   ├── EmployeeTaskEndRequest.php
│   ├── EmployeeTaskType.php
│   └── EmployeeTaskItem.php
├── Presenters/
│   ├── EmployeeTaskRequestPresenter.php
│   ├── EmployeeTaskSessionPresenter.php
│   ├── EmployeeTaskExtensionPresenter.php
│   ├── EmployeeTaskApprovalPresenter.php
│   ├── InboxItemPresenter.php
│   └── TaskProcedurePresenter.php
├── Providers/
│   ├── EmployeeTaskServiceProvider.php
│   └── EmployeeTaskRouteServiceProvider.php
├── Repositories/
│   ├── EmployeeTaskRepository.php
│   └── EmployeeTaskSessionRepository.php
├── Requests/
│   ├── CreateEmployeeTaskRequest.php
│   ├── StartTaskRequest.php
│   ├── EndTaskRequest.php
│   ├── LocationPingRequest.php
│   ├── CreateExtensionRequest.php
│   ├── ApproveExtensionRequest.php
│   ├── RejectExtensionRequest.php
│   ├── RejectTaskRequest.php
│   └── AdminCancelTaskRequest.php
├── Routes/
│   └── employee_tasks.php
├── Services/
│   ├── EmployeeTaskRequestService.php
│   ├── EmployeeTaskLifecycleService.php
│   ├── EmployeeTaskLocationService.php
│   ├── EmployeeTaskAutoCloseService.php
│   ├── EmployeeTaskApprovalService.php
│   ├── EmployeeTaskExtensionService.php
│   ├── EmployeeTaskExtensionWorkflowService.php
│   ├── EmployeeTaskEndRequestService.php
│   ├── EmployeeTaskFormConditionService.php
│   ├── EmployeeTaskAvailableActionsService.php
│   ├── EmployeeTaskProceduresService.php
│   ├── EmployeeTaskReportService.php
│   ├── EmployeeTaskWorkflowNotifier.php
│   ├── EmployeeTaskItemService.php
│   └── EmployeeTaskTypeCRUDService.php
├── Support/
│   ├── GeoDistance.php
│   └── GeoPolygon.php
└── module.json
```

---

## 3. Database Schema & Migrations

### `employee_task_requests` (main table)

| Column | Type | Notes |
|--------|------|-------|
| `id` | UUID (PK) | |
| `company_id` | string | Tenant ID |
| `user_id` | UUID (FK→users) | Employee who owns the task |
| `serial_number` | string | Format: `TASK-{YEAR}-{00001}`, unique per company |
| `title` | string | |
| `description` | text, nullable | |
| `project_id` | UUID, nullable | Optional project association |
| `item_type` | string, nullable | Polymorphic item key (references `employee_task_items.key`) |
| `item_id` | UUID, nullable | Polymorphic item ID |
| `employee_task_type_id` | UUID, nullable | FK→`employee_task_types` |
| `approval_responsible_id` | UUID, nullable | User responsible for approval |
| `assignment_responsible_id` | UUID, nullable | User responsible for assignment |
| `duration_hours` | float | Task duration in hours |
| `original_duration_hours` | float, nullable | Duration before extensions |
| `task_date` | date | Scheduled date |
| `task_latitude` | float | Task location latitude |
| `task_longitude` | float | Task location longitude |
| `radius_meters` | int, nullable | Allowed radius around task location |
| `procedure_setting_id` | UUID, nullable | Associated procedure setting |
| `current_procedure_step_id` | UUID, nullable | Current workflow step |
| `status` | string | Enum: `pending`, `approved`, `rejected`, `in_progress`, `paused`, `completed`, `cancelled` |
| `time_from` | datetime, nullable | When task was started |
| `time_to` | datetime, nullable | When task was ended |
| `total_task_hours` | float, nullable | Calculated total work hours |
| `total_pause_minutes` | int, nullable | Total pause time in minutes |
| `shift_end_method` | string, nullable | How the task ended (`manual`, `auto_duration`, `auto_location`) |
| `start_location` | json, nullable | `{latitude, longitude}` |
| `end_location` | json, nullable | `{latitude, longitude}` |
| `timezone` | string, nullable | Task timezone |
| `notes` | text, nullable | |
| `approved_by` | UUID, nullable | |
| `approved_at` | datetime, nullable | |
| `rejected_by` | UUID, nullable | |
| `rejected_at` | datetime, nullable | |
| `rejection_reason` | text, nullable | |
| `cancelled_by` | UUID, nullable | |
| `cancelled_at` | datetime, nullable | |
| `cancellation_reason` | text, nullable | |
| `last_extension_status` | string, nullable | Badge value for last extension |
| `location_confirmed_at` | datetime, nullable | First time GPS confirmed in location |

### `employee_task_sessions`

| Column | Type | Notes |
|--------|------|-------|
| `id` | UUID (PK) | |
| `employee_task_request_id` | UUID (FK) | |
| `company_id` | string | |
| `start_time` | datetime | |
| `end_time` | datetime, nullable | Null = active session |
| `duration_minutes` | int, nullable | |
| `source` | string | `manual` or system-generated |
| `start_latitude` | float, nullable | |
| `start_longitude` | float, nullable | |
| `end_latitude` | float, nullable | |
| `end_longitude` | float, nullable | |
| `notes` | text, nullable | |

### `employee_task_extension_requests`

| Column | Type | Notes |
|--------|------|-------|
| `id` | UUID (PK) | |
| `employee_task_request_id` | UUID (FK) | |
| `company_id` | string | |
| `procedure_setting_id` | UUID, nullable | |
| `requested_by` | UUID (FK→users) | |
| `additional_hours` | float | |
| `reason` | text, nullable | |
| `status` | string | `pending`, `approved`, `rejected` |
| `reviewed_by` | UUID, nullable | |
| `reviewed_at` | datetime, nullable | |
| `review_notes` | text, nullable | |
| `current_procedure_step_id` | UUID, nullable | |

### `employee_task_approval_requests`

| Column | Type | Notes |
|--------|------|-------|
| `id` | UUID (PK) | |
| `employee_task_request_id` | UUID (FK) | |
| `company_id` | string | |
| `procedure_setting_id` | UUID, nullable | |
| `requested_by` | UUID (FK→users) | |
| `notes` | text, nullable | |
| `status` | string | `pending`, `approved`, `rejected` |
| `reviewed_by` | UUID, nullable | |
| `reviewed_at` | datetime, nullable | |
| `review_notes` | text, nullable | |
| `current_procedure_step_id` | UUID, nullable | |
| `attachment_path` | string, nullable | Legacy field; media managed via Spatie |

### `employee_task_end_requests`

| Column | Type | Notes |
|--------|------|-------|
| `id` | UUID (PK) | |
| `employee_task_request_id` | UUID (FK) | |
| `company_id` | string | |
| `procedure_setting_id` | UUID, nullable | |
| `requested_by` | UUID (FK→users) | |
| `latitude` | float | |
| `longitude` | float | |
| `notes` | text, nullable | |
| `status` | string | `pending`, `approved`, `rejected` |
| `reviewed_by` | UUID, nullable | |
| `reviewed_at` | datetime, nullable | |
| `review_notes` | text, nullable | |
| `current_procedure_step_id` | UUID, nullable | |

### `employee_task_types`

| Column | Type | Notes |
|--------|------|-------|
| `id` | UUID (PK, string) | |
| `key` | string | |
| `name` | string | |

### `employee_task_items`

| Column | Type | Notes |
|--------|------|-------|
| `id` | UUID (PK, string) | |
| `key` | string | |
| `name` | string | |
| `model_class` | string, nullable | Polymorphic mapping class |

---

## 4. Models

### `EmployeeTaskRequest`

**File:** `Models/EmployeeTaskRequest.php`

The central model. Uses UUIDs, multi-tenant (`UsesTenantConnection`), filterable (`Filterable` trait), and implements `HasMedia` for attachments.

**Relationships:**
- `user()` → `BelongsTo User`
- `project()` → `BelongsTo ProjectManagement`
- `taskType()` → `BelongsTo EmployeeTaskType`
- `currentProcedureStep()` → `BelongsTo ProcedureSettingStep`
- `sessions()` → `HasMany EmployeeTaskSession`
- `extensionRequests()` → `HasMany EmployeeTaskExtensionRequest`
- `approvalRequests()` → `HasMany EmployeeTaskApprovalRequest`
- `endRequests()` → `HasMany EmployeeTaskEndRequest`
- `processes()` → `MorphMany Process` (via `processable_type` = `employee_task`)

**Status-checking methods:**
- `isActive()` — true if InProgress or Paused
- `hasActiveSession()` — true if a session with null `end_time` exists
- `hasPendingExtension()` — true if a pending extension request exists
- `hasPendingApprovalRequest()` — true if a pending approval request exists
- `hasPendingEndRequest()` — true if a pending end request exists

**Model callbacks:**
- `onAllProcessesCompleted()` — called when all workflow processes complete (sets status to Approved)
- `onProcessFailed()` — called when a workflow process fails (sets status to Rejected)

### `EmployeeTaskSession`

Tracks individual work sessions within a task. `isActive()` returns true when `end_time` is null.

### `EmployeeTaskExtensionRequest`

Represents a request to extend a task's duration. Goes through its own workflow.

### `EmployeeTaskApprovalRequest`

Represents a "send for final approval" request. Implements `HasMedia` for file attachments.

### `EmployeeTaskEndRequest`

Represents a request to end a task through a procedure workflow. Includes location data.

### `EmployeeTaskType` & `EmployeeTaskItem`

Reference tables for task categorization. Both use string UUIDs as primary keys.

---

## 5. Enums

### `EmployeeTaskStatus`

```php
enum EmployeeTaskStatus: string
{
    case Pending    = 'pending';
    case Approved   = 'approved';
    case Rejected   = 'rejected';
    case InProgress = 'in_progress';
    case Paused     = 'paused';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';
}
```

**Utility methods:**
- `label(string $locale): string` — localized label
- `activeStatuses(): array` — returns `[InProgress, Paused]`
- `terminalStatuses(): array` — returns `[Completed, Cancelled, Rejected]`

### `EmployeeTaskExtensionStatus`

```php
enum EmployeeTaskExtensionStatus: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
```

- `label(string $locale): string` — localized label
- `badgeValues(): array` — for UI badges

---

## 6. DTOs

### `CreateEmployeeTaskRequestDTO`

Carries all data needed to create a task: `userId`, `title`, `employee_task_type_id`, `itemType`, `itemId`, `durationHours`, `taskDate`, `taskLatitude`, `taskLongitude`, `currentLatitude`, `currentLongitude`, `description`, `projectId`, `approvalResponsibleId`, `assignmentResponsibleId`, `notes`, `files`.

Includes `toArray()` that maps to database columns with `status => 'pending'`.

### `StartTaskDTO`

`latitude`, `longitude`, `internalProcedureSettingId` (nullable).

### `EndTaskDTO`

`latitude`, `longitude`, `notes` (nullable), `internalProcedureSettingId` (nullable).

### `CreateExtensionRequestDTO`

`taskId`, `requestedBy`, `additionalHours`, `reason` (nullable), `internalProcedureSettingId` (nullable). Includes `toArray()`.

---

## 7. Services

### `EmployeeTaskRequestService`

**The main orchestrator for task CRUD and approval workflow.**

Key methods:
- `create(CreateEmployeeTaskRequestDTO)` — Creates a task. Evaluates create conditions, previews responsibles via `WorkflowEngine`. If auto-approve (no workflow configured), sets status to Approved and marks createTask procedures as taken. Otherwise, creates a Process for the approval workflow.
- `approve(string $id, string $adminId)` — Approves the current pending workflow step for a task.
- `reject(string $id, string $adminId, string $reason)` — Rejects the current pending workflow step.
- `list(string $userId, array $filters)` — Paginated list for an employee.
- `adminList(array $filters)` — Paginated list for admin (all users).
- `inbox(string $adminId, array $filters)` — Pending tasks where admin is an action-taker.
- `inboxAll(string $adminId, array $filters)` — Non-paginated version for combined inbox.
- `inboxAllApprovals(...)` / `inboxAllEndRequests(...)` — Approval/end request inbox collections.
- `get(string $id)` — Single task with relations.
- `cancelByEmployee(string $id, string $userId)` — Employee cancels their own pending task.
- `cancelByAdmin(string $id, string $adminId, ?string $reason)` — Admin cancels a task (approved/in-progress/paused).
- `getFilterMetadata(string $userId, array $filters)` — Status counts, project counts, duration range for filter UI.
- `getInboxCountsForAdmin(string $adminId, array $filters)` — Counts for inbox badges.
- `broadcastInboxCounts(array $userIds, array $filters)` — Dispatches `InboxCountsUpdated` event to each user.

### `EmployeeTaskLifecycleService`

**Manages the start → pause → resume → end lifecycle.**

Key methods:
- `start(string $taskId, StartTaskDTO, User)` — Requires Approved status. Evaluates start conditions, snapshots radius from attendance constraints, sets status to InProgress, creates first session, dispatches `AutoCloseTaskAtDurationExpiryJob` with delay = duration + max overtime.
- `pause(string $taskId, string $userId)` — Requires InProgress. Closes active session, calculates duration, sets status to Paused.
- `resume(string $taskId, float $lat, float $lng)` — Requires Paused. Creates new session, sets status to InProgress.
- `end(string $taskId, EndTaskDTO)` — Requires InProgress or Paused. Checks for pending end requests. If a procedure setting is configured for end-task, delegates to `EmployeeTaskEndRequestService` to create an end request. Otherwise, performs immediate end via `performEnd()`.
- `performEnd(EmployeeTaskRequest, EndTaskDTO)` — Closes active session, calculates `total_task_hours` and `total_pause_minutes`, sets status to Completed. Also called when an end request is auto-approved.

**Timezone resolution:** Uses the user's branch country timezone, falling back to `getTimeZoneBranchByRequest()`, then `config('app.timezone')`, then `Asia/Riyadh`.

### `EmployeeTaskLocationService`

**GPS location management.**

Key methods:
- `snapshotRadiusFromConstraint(User)` — Reads the user's attendance constraint radius and stores it on the task.
- `isWithinTaskRadius(EmployeeTaskRequest, float $lat, float $lng)` — Checks if a point is within the task's radius using `GeoDistance::metres()`.
- `processLocationPing(EmployeeTaskRequest, float $lat, float $lng, ?string $timestamp, int $thresholdMinutes)` — Processes a GPS ping. If out of radius, dispatches `AutoCloseTaskIfOutOfLocationJob` with a delay equal to the threshold.
- `outOfRadiusThresholdMinutes(User)` — Reads the threshold from the user's attendance constraint (default: 30 minutes).

### `EmployeeTaskAutoCloseService`

**Single atomic writer for all auto-close paths.**

- `closeIfExpired(string $taskId, string $closeAtIso, string $reason)` — Uses DB transactions with `lockForUpdate()` on the task row. Re-reads status, verifies the task is still active, closes the active session, calculates totals, and sets status to **Rejected** (not Completed) with `shift_end_method` = `auto_duration` or `auto_location`. Also sets `rejected_at` and `rejection_reason`. This ensures tasks that exceeded their duration without manual end are auto-rejected and excluded from reports.

### `EmployeeTaskApprovalService`

**Manages the "send for final approval" flow.**

Key methods:
- `create(string $taskId, string $userId, ?string $notes, $file, ?string $internalProcedureSettingId)` — Creates an approval request. If no workflow is configured, auto-approves and completes the task. Handles file uploads via `FileUploadService`.
- `approve(string $approvalId, string $adminId, ?string $notes)` — Approves the current workflow step. On final approval, completes the task.
- `reject(string $approvalId, string $adminId, string $reason)` — Rejects the approval request.

### `EmployeeTaskExtensionService`

**Manages extension requests.**

Key methods:
- `requestExtension(CreateExtensionRequestDTO)` — Creates an extension request. If no workflow, auto-approves and updates task duration. Dispatches notifications.
- `listForTask(string $taskId)` — Lists all extension requests for a task.
- `listInboxForAdmin(string $adminId, array $filters, int $perPage)` — Paginated pending extensions for admin.
- `listInboxAllForAdmin(string $adminId, array $filters)` — Non-paginated version for combined inbox.

### `EmployeeTaskExtensionWorkflowService`

**Workflow-based approval/rejection of extension requests.**

- `approve(string $extensionId, string $adminId, ?string $notes)` — Advances the workflow step. On final approval, updates `duration_hours`, `original_duration_hours`, reschedules the auto-close job, and updates `last_extension_status`.
- `reject(string $extensionId, string $adminId, string $reason)` — Rejects immediately, terminates the workflow.

### `EmployeeTaskEndRequestService`

**Manages the "end task with procedure" flow.**

Key methods:
- `resolveEndTaskProcedure(EmployeeTaskRequest, ?string $settingId)` — Finds the applicable procedure setting for ending a task.
- `create(EmployeeTaskRequest, EndTaskDTO, ProcedureSetting)` — Creates a pending end request if a workflow is configured. If no workflow, auto-approves and calls `performEnd()`.
- `approve(string $endRequestId, string $adminId, ?string $notes)` — Advances workflow. On final approval, calls `EmployeeTaskLifecycleService::performEnd()`.
- `reject(string $endRequestId, string $adminId, string $reason)` — Rejects the end request.

### `EmployeeTaskFormConditionService`

**Evaluates conditions before task actions using a registry-driven dispatch pattern.**

Key methods:
- `checkCreateTaskConditions(...)` — Evaluates conditions for the `createTask` form.
- `checkStartTaskConditions(...)` — Evaluates conditions for the `startTask` form.
- `checkEndTaskConditions(...)` — Evaluates conditions for the `endTask` form.
- `getPreConditionResults(...)` — Returns condition results for mobile UI hints (before form submission).
- `getInFormConditionsPreview(...)` — Returns in-form condition previews for mobile app.

Uses `ConditionEvaluationService` from the `ProcedureSetting` module. Throws `EmployeeTaskException` via `EmployeeTaskExceptionResolver` on failure.

### `EmployeeTaskAvailableActionsService`

Thin wrapper around `InternalProcedureAvailableActionsService`. Determines which actions are available for a task based on its context and procedure settings.

### `EmployeeTaskProceduresService`

Retrieves all "taken" procedures for a task. Returns items + summary (total count, last action, start date, average progress %).

### `EmployeeTaskReportService`

Generates intra-day reports combining attendance and task data for a specific user and date. Returns attendance sessions, task sessions, active tasks, and summary statistics.

### `EmployeeTaskWorkflowNotifier`

Implements the `WorkflowNotifier` contract. Dispatches `EmployeeTaskNotification` events when workflow steps are activated. Also provides inbox counts for a user.

### `EmployeeTaskItemService` & `EmployeeTaskTypeCRUDService`

Simple CRUD services for task items and task types.

---

## 8. Conditions System

The conditions system validates whether an action is allowed before it's performed. It integrates with the central `ProcedureSetting` condition framework.

### Condition Evaluators

| Evaluator | Condition Key | Description |
|-----------|---------------|-------------|
| `AllowDuringShiftEvaluator` | `AllowDuringShift` | Checks if action is allowed during the employee's work shift or a specific time window. Supports `shift` mode (actual work periods) and `specific_time` mode (fixed time range). |
| `AllowOnHolidaysEvaluator` | `AllowOnHolidays` | Checks if the current day is a holiday. If `is_active` is false and it's a holiday, the action is blocked. |
| `AllowOutsideShiftEvaluator` | `AllowOutsideShift` | Controls whether an employee must be inside the designated work area. `is_active=true` allows tasks outside the work area. `is_active=false` requires the employee's current GPS (`current_latitude`/`current_longitude`) to be within branch location radius. If current GPS is missing, the check fails. |
| `InsideCustomLocationsEvaluator` | `InsideCustomLocations` | Checks if a task location falls within configured custom polygonal areas using `GeoPolygon`. |
| `MaxTaskDurationEvaluator` | `MaxTaskDuration` | Enforces a maximum task duration (default: 8 hours). |
| `MaxScheduledDateOffsetEvaluator` | `MaxScheduledDateOffset` | Restricts task scheduling based on `max_task_date` (max days from today) or `end_contract` (employee's contract end date). |

### Form-Specific Condition Skips

- **`CreateProjectNotificationTask` — real-time conditions**: Dashboard-created project notifications are submitted by an admin on behalf of the employee, so the employee's real-time context (current shift, current GPS, today's holiday status) is unavailable at creation time. `EmployeeTaskFormConditionService::checkCreateTaskConditions()` removes these conditions from the map only for this form:
  - `AllowDuringShift` — checks `Carbon::now()` vs employee shift
  - `AllowOutsideShift` — checks current GPS vs work-area radius (skipped only when GPS is missing)
  - `AllowOnHolidays` — checks whether today is a holiday

  Task-data validations remain enforced: `InsideCustomLocations`, `MaxTaskDuration`, `MaxScheduledDateOffset`. Normal employee task creation (`CreateTask` form) is unaffected — all conditions are evaluated.

### Supporting Classes

- **`ResolvesUserAttendance` (trait)** — Shared logic for loading a user with branch timezone and checking if the current time falls within any work period.
- **`EmployeeTaskExceptionResolver`** — Maps `ConditionResult::$exception` keys to `EmployeeTaskException` factory methods. This is the bridge between the central `ConditionEvaluationService` and the module's exception hierarchy.

### Deprecated Stubs

`ConditionEvaluator`, `ConditionContext`, and `ConditionResult` are deprecated stubs that now extend their `ProcedureSetting` counterparts. They exist for backward compatibility.

---

## 9. Events & Broadcasting

### `EmployeeTaskNotification`

**Implements `ShouldBroadcast`.** Broadcasts to private user channels.

Fired when a workflow step is activated for a task. Payload includes:
- `task_id`, `serial_number`, `title`, `status`, `task_date`, `description`, `notes`
- `requested_by` (user details)
- `current_step` (step ID, name, order, is_approve flag, action takers)

### `InboxCountsUpdated`

**Implements `ShouldBroadcast`.** Broadcasts to a private user channel.

Fired to update inbox badges. Payload: `userId`, `pendingTasks`, `pendingExtensions`, `pendingApprovals`, `total`.

---

## 10. Jobs

### `AutoCloseTaskAtDurationExpiryJob`

Dispatched when a task starts, with a delay equal to `duration_hours + max_over_time_hours`. When executed:
1. Initializes tenancy for the task's company
2. Finds the task
3. Parses `closeAtIso` timestamp
4. Calls `EmployeeTaskAutoCloseService::closeIfExpired()` with reason `'auto_duration'`

### `AutoCloseTaskIfOutOfLocationJob`

Dispatched when a GPS ping detects the employee is out of the task's radius, with a delay equal to the out-of-radius threshold. When executed:
1. Initializes tenancy
2. Finds the task
3. Parses `closeAtIso` (the moment the threshold was breached)
4. Calls `EmployeeTaskAutoCloseService::closeIfExpired()` with reason `'auto_location'`

Both jobs have retry logic and ensure tenancy is properly managed.

### `AutoRejectStaleTaskJob`

**Added:** 2026-06-25

Dispatched at task creation time (`EmployeeTaskRequestService::create()`), with a delay calculated to fire at **midnight (end of `task_date`)** in the employee's branch timezone. When executed:
1. Initializes tenancy for the task's company
2. Finds the task and re-reads its status with `lockForUpdate()`
3. If status is `pending`, `approved`, or `paused` → sets status to **Rejected** with appropriate `rejection_reason`
4. If status is `in_progress` → **no-op** (handled by `AutoCloseTaskAtDurationExpiryJob`)
5. If status is already terminal (`completed`, `rejected`, `cancelled`) → **no-op**

**Rejection reasons by status:**
- `pending` → "Task auto-rejected: task date passed while still pending approval."
- `approved` → "Task auto-rejected: task date passed without the employee starting the task."
- `paused` → "Task auto-rejected: task was paused and never resumed before task date passed."

**Timezone resolution:** Uses the task creator's branch country timezone (same chain as `EmployeeTaskLifecycleService::resolveTimezone`), falling back to `getTimeZoneBranchByRequest()`, then `config('app.timezone')`.

**Race condition protection:** Uses `lockForUpdate()` inside a DB transaction. Re-reads status after acquiring the lock — if the task transitioned to `in_progress` between the initial read and the lock (e.g., user started at 11:59 PM), the rejection is skipped.

**Example scenario:**
- Task created for 25/6 at 2 PM → job dispatched with delay = 10 hours (to midnight 25/6 23:59:59)
- 25/6 at 11 PM → admin approves, user starts at 11:30 PM → task becomes `in_progress`
- 26/6 at 12 AM → job fires → reads status = `in_progress` → **skips** (no-op)
- 26/6 at 3:30 AM → `AutoCloseTaskAtDurationExpiryJob` fires → auto-rejects (duration expired)

If user never starts:
- 26/6 at 12 AM → job fires → status still `approved` → **auto-rejected**

---

## 11. Repositories

### `EmployeeTaskRepository`

Data access layer for `EmployeeTaskRequest`. Key methods:

- `create(array $data)` — Create a task
- `findById(string $id)` / `findByIdWithRelations(string $id)` — Find with optional relations
- `paginateForEmployee(string $userId, array $filters, int $perPage, ?string $sort)` — Employee's tasks with sorting
- `paginateForAdmin(array $filters, int $perPage)` — All tasks for admin
- `paginateInboxForAdmin(string $adminId, array $filters, int $perPage)` — Pending tasks where admin is an action-taker (checks `assigned_user_id` or `authorized_user_ids` JSON column)
- `allInboxForAdmin(string $adminId, array $filters)` — Non-paginated version
- `paginateExtensionInboxForAdmin(...)` / `allExtensionInboxForAdmin(...)` — Pending extension requests for admin
- `allApprovalInboxForAdmin(...)` — Pending approval requests for admin
- `allEndRequestInboxForAdmin(...)` — Pending end requests for admin
- `update(EmployeeTaskRequest, array $data)` — Update and return fresh
- `getFilterMetadata(string $userId, array $filters)` — Status counts, project counts, duration range
- `generateSerialNumber()` — Generates `TASK-{YEAR}-{00001}` with per-company sequence

**Sorting:** Supports `created_at`, `task_date`, `duration_hours`, `title`, `status` with `_asc`/`_desc` suffixes.

### `EmployeeTaskSessionRepository`

- `create(array $data)` — Create a session
- `closeSession(EmployeeTaskSession, array $data)` — Close a session
- `findActiveByTask(string $taskId)` — Find the active (null end_time) session
- `sumCompletedMinutes(string $taskId)` — Sum duration_minutes of completed sessions

---

## 12. Presenters

### `EmployeeTaskRequestPresenter`

Transforms an `EmployeeTaskRequest` into an API response array. Includes:
- All task fields with formatted dates and hours
- `status_label` (localized)
- `last_extension_status_label` (localized)
- Nested `task_location`, `start_location`, `end_location`
- Related `user`, `task_type`, `current_step` (with action takers), `attachments`, `sessions`
- `liveStatus()` method — returns real-time progress data: `elapsed_seconds`, `remaining_seconds`, `progress_percentage`, `time_consumption_percentage`, `can_request_extension`

### `EmployeeTaskSessionPresenter`

Transforms session data: `id`, `start_time`, `end_time`, `duration_minutes`, `source`, `start_location`, `end_location`, `notes`.

### `EmployeeTaskExtensionPresenter`

Transforms extension requests with status labels (Arabic), requested_by/reviewed_by user details, and task summary.

### `EmployeeTaskApprovalPresenter`

Transforms approval requests with notes, attachments (via `MediaPresenter`), task details, and current step with action takers.

### `InboxItemPresenter`

**Normalizes all inbox item types into a unified shape** so the frontend never branches:

```json
{
  "id": "...",
  "type": "task_request|extension_request|task_approval|end_request",
  "type_label": "...",
  "task": { "id", "serial_number", "title", "task_date", "status", "status_label" },
  "employee": { "id", "name" },
  "status": "...",
  "current_step": { "id", "name", "step_order", "is_approve", "action_takers[]" },
  "summary": { /* type-specific fields */ },
  "created_at": "..."
}
```

Has static factory methods: `fromTaskRequest()`, `fromExtensionRequest()`, `fromApprovalRequest()`, `fromEndRequest()`.

### `TaskProcedurePresenter`

Transforms `InternalProcedureTaken` records into a timeline: `id`, `step_number`, `name`, `icon`, `percentage`, `form`, `taken_by`, `taken_at`.

---

## 13. Controllers & Routes

### Routes

**File:** `Routes/employee_tasks.php`

#### Employee Routes (`api/v1/employee-tasks`)

| Method | Path | Controller Method | Description |
|--------|------|-------------------|-------------|
| GET | `/` | `index` | List employee's tasks (paginated, filterable, sortable) |
| GET | `/types` | `EmployeeTaskTypeController@index` | List task types |
| GET | `/items` | `EmployeeTaskTypeController@getItems` | List task items |
| GET | `/filters` | `filters` | Filter metadata (status counts, projects, duration range) |
| GET | `/pre-conditions` | `preConditions` | Pre-form condition check results |
| GET | `/in-form-conditions` | `inFormConditions` | In-form condition previews |
| POST | `/` | `store` | Create a new task |
| GET | `/{id}` | `show` | Get a single task |
| DELETE | `/{id}` | `destroy` | Cancel a task (employee) |
| POST | `/{id}/start` | `start` | Start an approved task |
| POST | `/{id}/pause` | `pause` | Pause an in-progress task |
| POST | `/{id}/resume` | `resume` | Resume a paused task |
| POST | `/{id}/end` | `end` | End a task |
| GET | `/{id}/status` | `liveStatus` | Live status with progress + 3-step pipeline |
| POST | `/{id}/location-ping` | `locationPing` | Process GPS ping |
| GET | `/{id}/check-location` | `checkLocation` | Check if location is within radius |
| GET | `/{id}/sessions` | `sessions` | List task sessions |
| POST | `/{id}/request-approval` | `requestApproval` | Submit for final approval |
| POST | `/{id}/extension-requests` | `storeExtension` | Request a duration extension |
| GET | `/{id}/extension-requests` | `listExtensions` | List extension requests |
| GET | `/{id}/available-actions` | `availableActions` | List available actions |
| GET | `/{id}/procedures` | `procedures` | List taken procedures + summary |

#### Admin Routes (`api/v1/admin/employee-tasks`)

| Method | Path | Controller Method | Description |
|--------|------|-------------------|-------------|
| GET | `/` | `index` | List all tasks (admin) |
| GET | `/inbox` | `inbox` | Combined inbox (tasks, extensions, approvals, end requests) |
| GET | `/inbox-counts` | `inboxCounts` | Inbox count badges |
| PATCH | `/{id}/approve` | `approve` | Unified approve (auto-detects type) |
| PATCH | `/{id}/reject` | `reject` | Unified reject (auto-detects type) |
| DELETE | `/{id}` | `destroy` | Cancel a task (admin) |
| GET | `/extension-requests` | `extensionRequests` | Paginated extension inbox |
| PATCH | `/extension-requests/{extensionId}/approve` | `approveExtension` | Approve extension |
| PATCH | `/extension-requests/{extensionId}/reject` | `rejectExtension` | Reject extension |

### `EmployeeTaskController`

Employee-facing controller. Handles task creation, lifecycle (start/pause/resume/end), location pings, extension requests, approval requests, and metadata endpoints.

Notable: The `liveStatus` endpoint returns a 3-step pipeline:
1. **قبول (Acceptance)** — Task was approved by admin
2. **تأكيد الموقع (Location Confirmation)** — Employee was in range at least once
3. **اعتماد (Task Approval)** — Final approval request was approved

### `AdminEmployeeTaskController`

Admin-facing controller. The `approve` and `reject` methods are **unified** — they auto-detect the model type by trying `EmployeeTaskApprovalRequest`, `EmployeeTaskEndRequest`, `EmployeeTaskExtensionRequest`, then falling back to `EmployeeTaskRequest`.

The `inbox` method merges all four inbox types (task requests, extension requests, approval requests, end requests), sorts them by `created_at` desc (or custom sort), and paginates the combined collection.

---

## 14. Exceptions

### `EmployeeTaskException`

Custom exception class with static factory methods. Each returns a user-friendly message and appropriate HTTP status code.

| Factory Method | Exception Key | HTTP Status | Description |
|----------------|---------------|-------------|-------------|
| `notFound()` | — | 404 | Task not found |
| `invalidStatus(...)` | — | 422 | Task is not in the expected status |
| `notApproved()` | — | 422 | Task must be approved first |
| `notInProgress()` | — | 422 | Task must be in progress |
| `notPaused()` | — | 422 | Task must be paused |
| `notCancellable()` | — | 422 | Task cannot be cancelled in current status |
| `cannotCancel()` | — | 403 | User cannot cancel another user's task |
| `pendingExtensionExists()` | — | 422 | A pending extension already exists |
| `pendingApprovalRequestExists()` | — | 422 | A pending approval already exists |
| `pendingEndRequestExists()` | — | 422 | A pending end request already exists |
| `notAllowedDuringShift()` | `notAllowedDuringShift` | 422 | Action not allowed during shift |
| `outsideShiftTimeWindow()` | `outsideShiftTimeWindow` | 422 | Outside the allowed time window |
| `notAllowedOnHolidays()` | `notAllowedOnHolidays` | 422 | Action not allowed on holidays |
| `notAllowedOutsideLocation()` | `notAllowedOutsideLocation` | 422 | Employee outside work area |
| `taskDurationExceedsLimit(int)` | `taskDurationExceedsLimit` | 422 | Duration exceeds max hours |
| `taskDateTooFarInFuture(int)` | `taskDateTooFarInFuture` | 422 | Task date too far in future |
| `taskDateExceedsContractEndDate()` | `taskDateExceedsContractEndDate` | 422 | Task date after contract end |
| `outsideCustomLocations()` | `outsideCustomLocations` | 422 | Outside custom location polygons |

---

## 15. Support Utilities

### `GeoDistance`

Static method `metres(float $lat1, float $lng1, float $lat2, float $lng2): float` — Calculates distance in meters between two GPS coordinates using the **Haversine formula**.

### `GeoPolygon`

Static methods for point-in-polygon checks:
- `isPointInPolygon(float $lat, float $lng, array $polygon): bool` — Ray-casting algorithm
- `isPointInAnyPolygon(float $lat, float $lng, array $polygons): bool` — Checks multiple polygons

---

## 16. Form Requests

| Request Class | Key Validation Rules |
|---------------|---------------------|
| `CreateEmployeeTaskRequest` | `title` required (max 255), `duration_hours` required (0.25–24), `task_date` required (Y-m-d), `task_latitude` required (-90,90), `task_longitude` required (-180,180), `files.*` max 20480 KB |
| `StartTaskRequest` | `latitude` required, `longitude` required |
| `EndTaskRequest` | `latitude` required, `longitude` required, `notes` nullable |
| `LocationPingRequest` | `latitude` required, `longitude` required, `timestamp` nullable |
| `CreateExtensionRequest` | `additional_hours` required (numeric), `reason` nullable |
| `ApproveExtensionRequest` | `approval_notes` nullable |
| `RejectExtensionRequest` | `rejection_reason` nullable |
| `RejectTaskRequest` | `rejection_reason` nullable |
| `AdminCancelTaskRequest` | `cancellation_reason` nullable |

---

## 17. Workflow Integration

The EmployeeTask module deeply integrates with the `ProcedureSetting` and `Process` modules for multi-step approval workflows.

### How It Works

1. **Task Creation** → `EmployeeTaskRequestService::create()` calls `WorkflowEngine::previewResponsibles()` to check if a workflow is configured for the `createTask` form.
   - **Auto-approve** (no workflow): Task is immediately set to `Approved`. `markCreateTaskProceduresTaken()` fires `WorkflowProcedureTaken` events for all `createTask` settings.
   - **Workflow exists**: `WorkflowEngine::startWorkflow()` creates a `Process` with `ProcessStep` records. The task stays `Pending` until all steps are approved.

2. **Admin Approve/Reject** → `EmployeeTaskRequestService::approve()/reject()` finds the pending step assigned to the admin and calls `ProcessWorkflowService::approveStep()/rejectStep()`.

3. **Extension Requests** → `EmployeeTaskExtensionService` creates an `EmployeeTaskExtensionRequest`. If a workflow is configured, `EmployeeTaskExtensionWorkflowService` manages step progression. On final approval, task duration is updated and auto-close job is rescheduled.

4. **Approval Requests** → `EmployeeTaskApprovalService` creates an `EmployeeTaskApprovalRequest`. On final approval, the task is completed.

5. **End Requests** → `EmployeeTaskEndRequestService` creates an `EmployeeTaskEndRequest`. On final approval, `EmployeeTaskLifecycleService::performEnd()` is called.

### Key Integration Points

- `WorkflowEngine` — Previews responsibles, starts workflows, resolves parent settings
- `ProcessWorkflowService` — Manages process steps (approve/reject)
- `InternalProcedureAvailableActionsService` — Determines available actions
- `WorkflowProcedureTaken` event — Fired when a procedure is marked as taken
- `WorkflowStepActivated` event — Fired by `ProcessWorkflowService` when a step is activated, triggers `EmployeeTaskWorkflowNotifier`
- `InternalProcessForm` enum — Form keys: `CreateTask`, `StartTask`, `EndTask`
- `ProcedureSettingType::EmployeeTask` — The procedure type for this module

---

## 18. Lifecycle State Machine

```
                    ┌──────────────────────────────────────────────────┐
                    │                                                  │
                    ▼                                                  │
  ┌──────────┐  admin approve  ┌──────────┐  start   ┌───────────┐    │
  │ Pending  │ ──────────────→ │ Approved │ ──────→  │ InProgress │    │
  └──────────┘                 └──────────┘          └───────────┘    │
       │                            │                      │          │
       │ admin reject               │ admin cancel         │ pause    │
       ▼                            ▼                      ▼          │
  ┌──────────┐               ┌──────────┐            ┌────────┐      │
  │ Rejected │               │ Cancelled │            │ Paused │      │
  └──────────┘               └──────────┘            └────────┘      │
                                                          │          │
                                                     resume│          │
                                                          └──→ InProgress
                                                                │
                                                     end / auto-close
                                                                ▼
                                                          ┌───────────┐
                                                          │ Completed │
                                                          └───────────┘
```

**Transitions:**

| From | To | Trigger |
|------|----|---------| 
| — | Pending | `create()` with workflow |
| — | Approved | `create()` auto-approve (no workflow) |
| Pending | Approved | Admin approves all workflow steps |
| Pending | Rejected | Admin rejects a workflow step |
| Pending | Cancelled | Employee cancels (`cancelByEmployee`) |
| Approved | InProgress | `start()` |
| Approved | Cancelled | Admin cancels (`cancelByAdmin`) |
| InProgress | Paused | `pause()` |
| Paused | InProgress | `resume()` |
| InProgress | Completed | `end()` / `performEnd()` |
| InProgress | Rejected | Auto-close (duration expiry / out of location) |
| Paused | Completed | `end()` / `performEnd()` |
| Paused | Rejected | `AutoRejectStaleTaskJob` (task date passed while paused) |
| InProgress | Cancelled | Admin cancels |
| Paused | Cancelled | Admin cancels |
| Pending | Rejected | `AutoRejectStaleTaskJob` (task date passed while pending) |
| Approved | Rejected | `AutoRejectStaleTaskJob` (task date passed, never started) |

**Auto-close triggers:**
- Duration expiry → `AutoCloseTaskAtDurationExpiryJob` → `shift_end_method = 'auto_duration'` → status = **Rejected**
- Out of location → `AutoCloseTaskIfOutOfLocationJob` → `shift_end_method = 'auto_location'` → status = **Rejected**
- Task date passed (pending/approved/paused) → `AutoRejectStaleTaskJob` → status = **Rejected**

**Auto-rejected tasks are excluded from reports:** `EmployeeTaskReportService::getIntraDayReport()` queries only `completed`, `in_progress`, and `paused` statuses — rejected tasks never appear.

---

## 19. API Reference

### Employee Endpoints

#### Create Task
```
POST /api/v1/employee-tasks
Content-Type: multipart/form-data

{
  "title": "string (required)",
  "description": "string",
  "employee_task_type_id": "uuid",
  "item_type": "string (exists:employee_task_items,key)",
  "item_id": "uuid",
  "project_id": "uuid",
  "approval_responsible_id": "uuid",
  "assignment_responsible_id": "uuid",
  "duration_hours": "float (0.25-24, required)",
  "task_date": "date (Y-m-d, required)",
  "task_latitude": "float (required)",
  "task_longitude": "float (required)",
  "current_latitude": "float",
  "current_longitude": "float",
  "notes": "string",
  "files[]": "file(s) (max 20480 KB)"
}
```

#### Start Task
```
POST /api/v1/employee-tasks/{id}/start
{
  "latitude": "float (required)",
  "longitude": "float (required)",
  "internal_procedure_setting_id": "uuid"
}
```

#### End Task
```
POST /api/v1/employee-tasks/{id}/end
{
  "latitude": "float (required)",
  "longitude": "float (required)",
  "notes": "string",
  "internal_procedure_setting_id": "uuid"
}
```

#### Location Ping
```
POST /api/v1/employee-tasks/{id}/location-ping
{
  "latitude": "float (required)",
  "longitude": "float (required)",
  "timestamp": "string",
  "internal_procedure_setting_id": "uuid"
}
```

#### Request Extension
```
POST /api/v1/employee-tasks/{id}/extension-requests
{
  "additional_hours": "float (required)",
  "reason": "string",
  "internal_procedure_setting_id": "uuid"
}
```

#### Request Final Approval
```
POST /api/v1/employee-tasks/{id}/request-approval
Content-Type: multipart/form-data

{
  "notes": "string (max 2000)",
  "file": "file (max 20480 KB)",
  "files[]": "file(s)",
  "internal_procedure_setting_id": "uuid"
}
```

#### Live Status
```
GET /api/v1/employee-tasks/{id}/status
```
Returns task details + `liveStatus` (elapsed/remaining seconds, progress %) + `pipeline` (3-step: acceptance → location confirmation → task approval).

### Admin Endpoints

#### Combined Inbox
```
GET /api/v1/admin/employee-tasks/inbox?type=task_request|extension_request|task_approval|end_request&task_id=...&date_from=...&date_to=...&sort=created_at_desc&page=1&per_page=15
```

#### Unified Approve
```
PATCH /api/v1/admin/employee-tasks/{id}/approve
{
  "approval_notes": "string"
}
```
Auto-detects type: tries `EmployeeTaskApprovalRequest` → `EmployeeTaskEndRequest` → `EmployeeTaskExtensionRequest` → `EmployeeTaskRequest`.

#### Unified Reject
```
PATCH /api/v1/admin/employee-tasks/{id}/reject
{
  "rejection_reason": "string"
}
```

#### Inbox Counts
```
GET /api/v1/admin/employee-tasks/inbox-counts
```
Returns: `{ pending_tasks, pending_extensions, pending_approvals, pending_end_requests, total }`

---

## Key Design Decisions

1. **Parallel to Attendance** — The module never writes to attendance tables. It reads attendance constraints for shift/holiday/location checks but maintains its own tables.

2. **Single Writer for Auto-Close** — `EmployeeTaskAutoCloseService` is the only service that writes the terminal status for auto-close paths (now sets **Rejected** instead of Completed), using `lockForUpdate()` to prevent race conditions.

9. **Auto-Rejection for Stale Tasks** — `AutoRejectStaleTaskJob` is dispatched at task creation with a delay to midnight of `task_date` in the employee's branch timezone. If the task is still `pending`, `approved`, or `paused` when it fires, the task is auto-rejected. `in_progress` tasks are skipped (handled by `AutoCloseTaskAtDurationExpiryJob`). This ensures tasks that are never started or left paused don't stay open indefinitely.

10. **Prevent Concurrent Active Tasks** — `EmployeeTaskLifecycleService::start()` checks via `EmployeeTaskRepository::findActiveTaskForUser()` if the user already has an `in_progress` or `paused` task. If found, throws `EmployeeTaskException::hasOtherOpenTask()`.

11. **Rejected Tasks Excluded from Reports** — `EmployeeTaskReportService::getIntraDayReport()` filters by `completed`, `in_progress`, and `paused` statuses only. Rejected tasks (whether auto-rejected or manually rejected) never appear in reports.

3. **Unified Admin Inbox** — All four request types (task, extension, approval, end) are merged into a single inbox with a normalized shape via `InboxItemPresenter`, so the frontend never branches by type.

4. **Unified Approve/Reject** — The admin `approve`/`reject` endpoints auto-detect the model type, so the frontend uses a single endpoint for all approval actions.

5. **Procedure-Driven Available Actions** — Available actions for a task are determined by the `InternalProcedureAvailableActionsService` based on the task's context and which procedures have been "taken" (completed).

6. **Condition Evaluation as Pre-Check** — Conditions are evaluated before task actions. The `EmployeeTaskFormConditionService` provides both hard checks (throws exceptions) and soft previews (for mobile UI hints).

7. **Session-Based Time Tracking** — Work time is tracked via sessions (start/end pairs), not a single timer. This allows pause/resume to create distinct work periods.

8. **Serial Numbers** — Format `TASK-{YEAR}-{00001}`, unique per company, with auto-incrementing sequence extracted from the max existing serial.

---

## Unregistered Form Keys — `extendTaskTime` and `sendForApproval`

### Current State

Two EmployeeTask form keys are used as **raw string literals** in service code but are **NOT registered** in the `InternalProcessForm` enum at `modules/Shared/InternalProcessType/Enums/InternalProcessForm.php`.

**1. `extendTaskTime`** — used by `EmployeeTaskExtensionService::resolveExtensionProcedureSetting()`:

```php
// EmployeeTaskExtensionService.php line 213
return $this->workflow->resolveInternalProcedureSettingByForm(
    ProcedureSettingType::EmployeeTask->value,
    'extendTaskTime',   // ← raw string, NOT an InternalProcessForm enum case
    $task->company_id,
    $branchId,
);
```

**2. `sendForApproval`** — used by `EmployeeTaskApprovalService::resolveApprovalProcedureSetting()`:

```php
// EmployeeTaskApprovalService.php line 249
return $this->workflow->resolveInternalProcedureSettingByForm(
    ProcedureSettingType::EmployeeTask->value,
    'sendForApproval',  // ← raw string, NOT an InternalProcessForm enum case
    $task->company_id,
    $branchId,
);
```

The `InternalProcessForm` enum only defines three forms for `employee_task`:

| Enum Case | Value | Label (AR) | Registered? |
|-----------|-------|------------|-------------|
| `CreateTask` | `'createTask'` | إنشاء مهمة | ✅ Yes |
| `StartTask` | `'startTask'` | بدء المهمة | ✅ Yes |
| `EndTask` | `'endTask'` | إنهاء المهمة | ✅ Yes |
| — | `'extendTaskTime'` | (no label) | ❌ No — raw string only |
| — | `'sendForApproval'` | (no label) | ❌ No — raw string only |

There is **no** `ExtendTaskTime = 'extendTaskTime'` case and **no** `SendForApproval = 'sendForApproval'` case.

> **Note:** The `endTask` form IS registered in the enum and is used properly by `EmployeeTaskEndRequestService` via `InternalProcessForm::EndTask->value`. Only the extension and approval forms are unregistered.

> **Cross-references:**
> - **Attendance Module Deep Reference** §24 (`ATTENDANCE_MODULE_DEEP_REFERENCE.md`) — documents the Attendance→EmployeeTask integration boundary.
> - **Procedure Workflow Deep Guide** §9 (`docs/PROCEDURE_WORKFLOW_DEEP_GUIDE.md`) — documents the full workflow architecture for all EmployeeTask forms including `extendTaskTime` and `sendForApproval`.

### What This Means

Because `extendTaskTime` and `sendForApproval` are not registered in the enum, both forms share these limitations:

1. **No condition definitions** — `InternalProcessForm::conditions()` has no entry for either form. The `EmployeeTaskFormConditionService` does not evaluate any conditions before requesting an extension or sending for approval (beyond basic status checks: task must be InProgress or Paused for extensions; no pending approval already exists for approvals).

2. **Not listed in `forType('employee_task')`** — `InternalProcessForm::forType()` returns only `CreateTask`, `StartTask`, `EndTask`. Neither form will appear in any form listing endpoint or admin UI that enumerates available forms.

3. **No label** — There is no `labelAr()` entry for either form.

4. **No sort order** — `sortOrder()` is not defined for either form.

5. **No `applicableTypes()` mapping** — Neither form is mapped to `'employee_task'` in the enum.

6. **No validation rules** — `InternalProcessCondition::validationRulesForForm('extendTaskTime')` and `validationRulesForForm('sendForApproval')` both return `[]` because `InternalProcessForm::tryFrom()` returns `null` for both.

7. **No default condition values** — `InternalProcessCondition::defaultValuesForForm()` cannot generate defaults for either form.

### How It Still Works

Despite not being in the enum, both workflows function at runtime because:

- `ProcedureWorkflowService::resolveInternalProcedureSettingByForm()` queries `procedure_settings` by the raw `form` column value. The database can store any string in the `form` column — it doesn't validate against the enum.
- Both services use `ProcedureWorkflowService` directly (not `WorkflowEngine`) to resolve the first step and manage step progression.
- `EmployeeTaskExtensionWorkflowService` and `EmployeeTaskApprovalService` use `ProcedureWorkflowService::advance()` and `assertCanReject()` which operate on the `procedure_setting_id` and step ID directly, without checking the enum.
- The `InternalProcedureSettingsSeeder` only seeds forms whose value starts with `create` or `end`. Since neither `extendTaskTime` nor `sendForApproval` starts with those prefixes, they are never auto-seeded — admins must manually create child `ProcedureSetting` rows with these form values via the API.

### What Would Be Needed to Fully Register Them

To bring both forms to parity with the other forms:

**For `extendTaskTime`:**
1. Add `case ExtendTaskTime = 'extendTaskTime'` to `InternalProcessForm`
2. Add `self::ExtendTaskTime => 'تمديد مدة المهمة'` to `labelAr()`
3. Add `self::ExtendTaskTime => [...]` to `conditions()` with any desired conditions (e.g., `AllowDuringShift`, `MaxTaskDuration`)
4. Add `self::ExtendTaskTime => 600` (or similar) to `sortOrder()` — after `StartTask` (500), before `EndTask` (900)
5. Add `self::ExtendTaskTime => ['employee_task']` to `applicableTypes()`
6. Add any corresponding condition evaluators if conditions are defined

**For `sendForApproval`:**
1. Add `case SendForApproval = 'sendForApproval'` to `InternalProcessForm`
2. Add `self::SendForApproval => 'إرسال للاعتماد'` to `labelAr()`
3. Add `self::SendForApproval => [...]` to `conditions()` with any desired conditions
4. Add `self::SendForApproval => 700` (or similar) to `sortOrder()` — after `ExtendTaskTime` (600), before `EndTask` (900)
5. Add `self::SendForApproval => ['employee_task']` to `applicableTypes()`
6. Add any corresponding condition evaluators if conditions are defined

### Complete Form Key Map

| Form Key | Used By | In Enum? | Seeded? | Purpose |
|----------|---------|----------|---------|---------|
| `createTask` | `EmployeeTaskRequestService::create()` | ✅ `InternalProcessForm::CreateTask` | ✅ Auto-seeded | Task creation approval workflow |
| `startTask` | `EmployeeTaskController::start()` | ✅ `InternalProcessForm::StartTask` | ❌ Not seeded | Task start procedure tracking |
| `endTask` | `EmployeeTaskEndRequestService` | ✅ `InternalProcessForm::EndTask` | ✅ Auto-seeded | Task end approval workflow |
| `extendTaskTime` | `EmployeeTaskExtensionService` | ❌ Raw string | ❌ Not seeded | Extension request approval workflow |
| `sendForApproval` | `EmployeeTaskApprovalService` | ❌ Raw string | ❌ Not seeded | Final completion approval workflow |

### Extension Workflow Flow (Current)

```
Employee requests extension
        │
        ▼
EmployeeTaskExtensionService::requestExtension()
        │
        ├─ Task must be InProgress or Paused
        ├─ No pending extension already exists
        │
        ▼
resolveExtensionProcedureSetting()
        │
        ├─ Queries procedure_settings by form='extendTaskTime'
        │
        ├─ No setting found → Auto-approve
        │   └─ Extension status = 'approved' immediately
        │
        └─ Setting found → Create pending extension
            ├─ Resolve first step
            ├─ Notify action takers
            └─ Extension status = 'pending'
                    │
                    ▼
            Admin approves/rejects via
            EmployeeTaskExtensionWorkflowService
                    │
        ┌───────────┴───────────┐
        ▼                       ▼
    approve()                reject()
        │                       │
        ├─ Workflow not final   └─ Status = 'rejected'
        │  → Move to next step     last_extension_status = 'extension_rejected'
        │
        └─ Workflow final
           ├─ Update task duration_hours
           ├─ Preserve original_duration_hours
           ├─ Reschedule AutoCloseTaskAtDurationExpiryJob
           ├─ Extension status = 'approved'
           └─ last_extension_status = 'extension_approved'
```

### Extension Data Flow on Approval

When an extension is finally approved via `EmployeeTaskExtensionWorkflowService::approve()`:

1. **Preserves original duration** — If `original_duration_hours` is null, it's set to the current `duration_hours` (only on the first extension).
2. **Adds additional hours** — `duration_hours = duration_hours + additional_hours`.
3. **Updates badge** — `last_extension_status` set to `'extension_approved'`.
4. **Reschedules auto-close** — If the task has started (`time_from` is set), a new `AutoCloseTaskAtDurationExpiryJob` is dispatched with the updated deadline = `time_from + new_duration`.
5. **Records reviewer** — `reviewed_by`, `reviewed_at`, `review_notes` are set on the extension record.

### Approval Workflow Flow (Current)

```
Employee sends task for final approval
        │
        ▼
EmployeeTaskApprovalService::create()
        │
        ├─ Task must not have a pending approval already
        ├─ Optional file upload (Spatie Media Library)
        │
        ▼
resolveApprovalProcedureSetting()
        │
        ├─ Queries procedure_settings by form='sendForApproval'
        │
        ├─ No setting found → Auto-approve
        │   └─ Approval status = 'approved' immediately
        │   └─ Task status → 'completed'
        │
        └─ Setting found → Create pending approval
            ├─ Resolve first step
            ├─ Notify action takers
            └─ Approval status = 'pending'
                    │
                    ▼
            Admin approves/rejects via
            AdminEmployeeTaskController::approve()/reject()
                    │
        ┌───────────┴───────────┐
        ▼                       ▼
    approve()                reject()
        │                       │
        ├─ Workflow not final   └─ Status = 'rejected'
        │  → Move to next step
        │
        └─ Workflow final
           ├─ Task status → 'completed'
           ├─ Approval status = 'approved'
           └─ Records reviewer info
```

### End Request Workflow Flow (Current)

```
Employee ends task (with procedure setting)
        │
        ▼
EmployeeTaskLifecycleService::end()
        │
        ├─ Checks for pending end request already exists
        │
        ▼
EmployeeTaskEndRequestService::create()
        │
        ├─ Resolves form = 'endTask' (via InternalProcessForm::EndTask)
        │
        ├─ No setting found → Auto-approve
        │   └─ Calls lifecycleService::performEnd() immediately
        │   └─ Task status → 'completed'
        │
        └─ Setting found → Create pending end request
            ├─ Resolve first step
            ├─ Notify action takers
            └─ End request status = 'pending'
                    │
                    ▼
            Admin approves/rejects via
            AdminEmployeeTaskController::approve()/reject()
                    │
        ┌───────────┴───────────┐
        ▼                       ▼
    approve()                reject()
        │                       │
        ├─ Workflow not final   └─ Status = 'rejected'
        │  → Move to next step     Task stays in_progress/paused
        │
        └─ Workflow final
           ├─ Calls lifecycleService::performEnd()
           ├─ Closes active session
           ├─ Calculates total_task_hours + total_pause_minutes
           └─ Task status → 'completed'
```
