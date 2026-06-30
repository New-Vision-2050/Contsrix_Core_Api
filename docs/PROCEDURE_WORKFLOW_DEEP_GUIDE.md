# Procedure Workflow Deep Guide

> Comprehensive implementation reference for AI assistants and developers.
>
> **Last updated:** 2026-06-30 — Added §40 (Module → Type → Forms → Conditions Lookup Table — the one table a weak AI needs to know which type, forms, and pre/in-form conditions apply to any module). Also added §35 (Complete Module Dependency Map), §36 (Step-by-Step Cookbook for any new module), §37 (Debug Guide with decision trees), §38 (Documentation Maintenance Protocol — MANDATORY), §39 (Quick Reference Card). Also: `WorkflowEngine` gained inbox & lifecycle helpers: `pendingProcessScopeForUser()`, `resolvePendingProcessesForUser()`, `hasActiveProcess()`, `startLifecycleWorkflow()`, `resolveLifecycleSetting()`. Project notification inbox no longer filters by top-level `status=pending`; any notification with a pending workflow step assigned to the user appears. `ProjectNotificationService` and `ProjectNotificationRepository` now delegate to `WorkflowEngine` instead of duplicating process-query logic. See §WorkflowEngine API table, §Inbox Queries, §35-§40.
>
> **Previous:** 2026-06-25 (rev 2) — Condition evaluation core centralized to `modules/ProcedureSetting/Conditions/` for cross-module reuse. `ConditionEvaluationService` (central engine) + `ExceptionResolver` interface added. `getInFormConditionsPreview()` now auto-generates via `InternalProcessCondition::toPreview()`. See §34. Full guide: `docs/CONDITION_EVALUATOR_IMPLEMENTATION_GUIDE.md`.
>
> **Previous:** 2026-06-25 (rev 1) — Condition evaluation refactored to registry-driven dispatch (Open/Closed Principle). Each condition now has a dedicated `ConditionEvaluator` class; new conditions are added without modifying `EmployeeTaskFormConditionService`.
>
> **Previous:** 2026-06-24 — Added `InsideCustomLocations` condition with `map_polygons` settings schema for custom polygon areas on task creation. See §3, §28, §31.
>
> **Previous:** 2026-06-22 — `action_taker_management_hierarchies` refactor: new JSON array of `{action_taker_management_hierarchy_type, is_Deputy_Director}` objects replaces deprecated single `action_taker_management_hierarchy_type` + `action_taker_alternative_management_hierarchy_type` fields. `deputy_manager` type replaced by `is_Deputy_Director` boolean flag. `ActionTakerResolver` updated to iterate array rows, skip unresolvable types (e.g. `project_manager` without `project_id`), and merge manager + deputy users. See §5.1, §32.

---

## ⚡ 2026-06-19 Breaking Changes (read first)

### New `action_taker_type` values
| Value | Behaviour |
|---|---|
| `himself` | The request submitter is the action taker. Only `approve` form is allowed. Resolver returns `[$createdByUserId]`. |

### New `action_taker_management_hierarchy_type` / alternative values
| Value | Behaviour |
|---|---|
| `deputy_manager` | When used as **primary** type: resolves the branch/management **manager + ALL deputy managers** into `authorized_user_ids`. Either one acting ends the step. When used in the **alternatives array**: resolves the first deputy manager as a single fallback user. |

### `action_taker_alternative_management_hierarchy_type` is now an **array**
- Old: `"branch_manager"` (string)
- New: `["branch_manager", "deputy_manager"]` (JSON array)
- Tried **in order**; first non-null result wins.

### `action_taker_specific_procedure_type` + `action_taker_specific_procedure_id` are now **arrays**
- Old: `"branch"` / `"5"` (single strings)
- New: `["branch", "management"]` / `["5", "12"]` (parallel JSON arrays)
- All targets are resolved and their users are **merged** into `authorized_user_ids`.
- Rejection is non-fatal only when **at least one** target type is `job_role`.

### Snapshot key renamed
`ProcessWorkflowService` snapshot rows now store:
- `specific_procedure_types` (array) — was `specific_procedure_type` (single string)
- `action_taker_type` (new field added for future use)
- Old snapshots with `specific_procedure_type` (string) are still read for backward compatibility.

### `WorkflowEngine` preview unified
`computeApprovalResponsiblesForSetting` now calls `ActionTakerResolver::resolveUsersForStep` for **all** dynamic types (`management_hierarchy`, `specific_procedures`, `himself`), eliminating the separate branch for single-manager lookup.

---

## Quick Start for Next AI Session

If you are reading this to implement **email notifications**, **SMS notifications**, **auto-approve (skipping_period)**, or **new features**, start here:

### Central Workflow Entry Point

New Process-based workflow code should go through `WorkflowEngine`:

- `WorkflowEngine::previewResponsibles()` previews the first action takers.
- `WorkflowEngine::startWorkflow()` resolves settings, creates `Process` records, and activates the first step.
- `WorkflowEngine::resolveParentSetting()` scopes by company + branch workflow, then falls back to the company default workflow.
- `WorkflowEngine::resolveSettingsForEntry()` returns the parent setting for no-form workflows or matching child settings for form-based workflows.

`ProcedureWorkflowService` still exists for template-step flows that do **not** create `Process` records, such as EmployeeTask extensions and completion approvals.

### Centralized Notification System (Event + Listener + Registry)

Process-based notifications are now handled centrally. Each module registers a `WorkflowNotifier` for its processable type.

**Architecture**:
- `WorkflowStepActivated` event is fired whenever a `ProcessStep` becomes active.
- `SendWorkflowStepNotification` listener handles real-time dispatch through `WorkflowNotifierRegistry` plus email + SMS.
- `EmployeeTaskWorkflowNotifier` broadcasts `EmployeeTaskNotification` and real inbox counts.
- `ClientRequestWorkflowNotifier` is registered for `client_request`; it is currently a no-op for real-time step activation and returns zero counts until ClientRequest has an inbox counter.
- `WorkflowActionRequired` notification sends mail (via `toMail()`), SMS (via `toSms()`), and WhatsApp (via `toWhatsapp()`).

**For Process-based workflows** (EmployeeTaskRequest, ClientRequest):
- `ProcessWorkflowService::createProcessStep()` automatically fires `WorkflowStepActivated`.
- The listener reads `notify_by_email`, `notify_by_sms`, `notify_by_whatsapp`, and `notify_by_push` flags from `ProcedureSettingStep`.
- Real-time behavior is module-specific through the registered `WorkflowNotifier`.
- Do not manually broadcast in the create path or notifications will be duplicated.

**For non-Process workflows** (EmployeeTask extensions/approvals):
- These call `dispatchStepNotifications()` directly since they don't create `ProcessStep` records.
- Located in `EmployeeTaskExtensionService::create()` and `EmployeeTaskApprovalService::create()`.

### To Customize Email Content
1. Edit `resources/views/emails/workflowActionRequired.blade.php`.
2. The notification passes `stepName` and `stepOrder` variables.

### To Customize SMS / WhatsApp Content
1. Edit `Modules\ProcedureSetting\Notifications\WorkflowActionRequired::toSms()` or `toWhatsapp()`.
2. SMS uses `MoraSms` driver by default. WhatsApp uses `TwilioWhatsApp` driver.
3. Country-specific driver resolution is supported for SMS.

### To Add a New Action Taker Type
1. Follow the 10-step guide in **§19.1**.
2. The core change is in `ActionTakerResolver::resolveUsersForStep()`.

### To Add a New Entity That Uses Workflows
1. Call `WorkflowEngine::startWorkflow()` when the entity enters a workflow.
2. Register a `WorkflowNotifier` for the new `processable_type`.
3. Reuse `ProcessWorkflowService::approveStep()` / `rejectStep()` or mirror the existing ClientRequest business rules if the entity needs custom status transitions.
4. If it does not create `Process` records, use `ProcedureWorkflowService` and dispatch notifications manually like extensions/approvals.

### To Apply Form Conditions (Backend Enforcement)
Form conditions stored on child `ProcedureSetting` records are enforced by `EmployeeTaskFormConditionService` **before** any workflow starts:

1. `createTask` — checks `allow_during_shift`, `allow_outside_shift`, `allow_on_holidays`, `inside_custom_locations` against the user's current attendance work-rules and custom polygon areas (via `AttendanceConstraintService::getTodaysWorkRulesForUser()` and `GeoPolygon`).
2. `endTask` — checks `can_exit_outside_location` against the task geofence (via `EmployeeTaskLocationService::isWithinTaskRadius()`).
3. `startTask` — checks `allow_on_holidays` against the user's attendance work-rules. If today is a holiday and the condition is inactive, the task cannot be started.

If **no child ProcedureSetting is found** for the form, or its `conditions` column is empty, the check passes silently. See **§28** for full details.

### Critical Rule for Notifications
`EmployeeTaskNotification` accepts `$userIds` explicitly. For `management_hierarchy` and `specific_procedures`, the template step has **EMPTY** `actionTakers`. For Process-based flows, `ProcessWorkflowService` resolves real user IDs into `authorized_user_ids` before firing `WorkflowStepActivated`; for non-Process flows, callers must resolve user IDs via `ProcedureWorkflowService::resolveActionTakerUserIdsForStep()` and pass them to the event constructor.

---

## Complete Public API Reference

### ActionTakerResolver

| Method | Returns | Parameters | Used By |
|--------|---------|-----------|---------|
| `resolveUsersForStep($step, $createdByUserId, $context = [])` | `array<string>` | `ProcedureSettingStep`, `?string`, `array` | ProcessWorkflowService, ProcedureWorkflowService, WorkflowEngine |
| `resolveAssignedUserId($step, $createdByUserId, $context = [])` | `?string` | Same as above | ProcessWorkflowService |
| `resolveManagerFromCreatorHierarchy($step, $createdByUserId, $context = [])` | `?string` | Same as above | ProcedureWorkflowService::userCanActOnStep, assertIsActionTaker |
| `rejectionShouldFailProcess($step)` | `bool` | `ProcedureSettingStep` | ProcessWorkflowService::rejectStep |

### ProcedureWorkflowService

| Method | Returns | Parameters | Used By |
|--------|---------|-----------|---------|
| `resolveFirstStep($procedureType)` | `ProcedureSettingStep` | `string` | EmployeeTask services (at creation preview) |
| `resolveFirstStepBySettingId($procedureSettingId)` | `ProcedureSettingStep` | `string` | EmployeeTaskExtensionService, EmployeeTaskApprovalService |
| `advance($currentStepId, $procedureSettingId, $userId, $createdByUserId = null, $context = [], $processableType = null, $processableId = null)` | `ProcedureWorkflowResult` | `?int`, `?string`, `string`, `?string`, `array`, `?string`, `?string` | All workflow approval services — **auto-marks taken** when `isFinal = true` and morph params provided |
| `assertCanReject($currentStepId, $userId, $createdByUserId = null, $context = [])` | `void` | Same as advance (without morph params) | EmployeeTaskExtensionWorkflowService, EmployeeTaskApprovalService, EmployeeTaskEndRequestService |
| `getApprovalResponsibles($procedureType, $createdByUserId = null, $context = [], $formKey = null)` | `array{auto_approve: bool, step: ?array, action_takers: array}` | `string`, `?string`, `array`, `?string` | ProcedureSettingController; delegates to `WorkflowEngine::previewResponsibles()` |
| `userCanActOnStep($step, $userId, $createdByUserId = null, $context = [])` | `bool` | `ProcedureSettingStep`, `string`, `?string`, `array` | Inbox filtering (read-only check) |
| `resolveActionTakerUserIdsForStep($step, $createdByUserId = null, $context = [])` | `array<string>` | `ProcedureSettingStep`, `?string`, `array` | Broadcasting (EmployeeTask services) |
| `resolveProcedureSettingForBranch($procedureType, $companyId, $branchId)` | `?ProcedureSetting` | `string`, `string`, `?string` | Delegates to `WorkflowEngine::resolveParentSetting()` |
| `resolveInternalProcedureSettingByForm($procedureCategoryType, $formKey, $companyId, $branchId = null)` | `?ProcedureSetting` | `string`, `string`, `string`, `?string` | EmployeeTask extension/approval creation; delegates to `WorkflowEngine::resolveSettingsForEntry()` |
| `markProcedureTaken($processableType, $processableId, $procedureSettingId, $takenBy = null)` | `void` | `string`, `string`, `string`, `?string` | All EmployeeTask action services; idempotent (`firstOrCreate`) |
| `getTakenProcedureIds($processableType, $processableId)` | `list<string>` | `string`, `string` | `EmployeeTaskAvailableActionsService::forTask()` |
| `isProcedureTaken($processableType, $processableId, $procedureSettingId)` | `bool` | `string`, `string`, `string` | Point checks against `internal_procedure_takens` table |

### WorkflowEngine

| Method | Returns | Parameters | Used By |
|--------|---------|-----------|---------|
| `resolveParentSetting($type, $companyId, $branchId)` | `?ProcedureSetting` | `string`, `string`, `?string` | Shared company/branch/default workflow lookup |
| `resolveSettingsForEntry($type, $formKey, $companyId, $branchId)` | `Collection<ProcedureSetting>` | `string`, `?string`, `string`, `?string` | Preview and workflow start |
| `previewResponsibles($type, $formKey, $companyId, $branchId, $createdByUserId, $context = [])` | `array{auto_approve: bool, step: ?array, action_takers: array}` | `string`, `?string`, `string`, `?string`, `?string`, `array` | ProcedureSettingController, EmployeeTask creation |
| `startWorkflow($processableType, $processableId, $type, $formKey, $companyId, $branchId, $createdByUserId = null, $context = [], $metadata = null, $resolvedSetting = null)` | `WorkflowStartResult` | `string`, `string`, `string`, `?string`, `string`, `?string`, `?string`, `array`, `?array`, `?ProcedureSetting` | EmployeeTaskRequestService, ClientRequestWorkflowService |
| `pendingProcessScopeForUser($processableType, $userId)` | `\Closure` | `string`, `string` | ProjectNotificationService::applyWorkflowInboxFilter, ProjectNotificationRepository::paginatedForInbox — returns a closure for `whereHas('employeeTask.processes', ...)` filtering in-progress processes with a pending step assigned to/authorized for the user |
| `resolvePendingProcessesForUser($task, $userId)` | `list<array{process_id, procedure_setting_id, form, mobile_inbox_action_key, pending_step_id, pending_step_order}>` | `Model`, `string` | ProjectNotificationService::resolvePendingProcessesForInbox — requires `$task->processes` relation loaded; calls `loadMissing('processes.procedureSetting')` internally |
| `hasActiveProcess($processableType, $processableId, $procedureSettingId = null)` | `bool` | `string`, `string`, `?string` | ProjectNotificationService::taskHasActiveProcess, confirmReceive |
| `startLifecycleWorkflow($processableType, $processableId, $procedureType, $formKey, $companyId, $branchId, $createdByUserId, $metadata, $context = [], $resolvedSetting = null)` | `WorkflowStartResult` | `string`, `string`, `string`, `string`, `string`, `?string`, `?string`, `array`, `array`, `?ProcedureSetting` | Centralised lifecycle start (update, site-status, fine, etc.); resolves setting by form key if not provided, then delegates to `startWorkflow()` |
| `resolveLifecycleSetting($explicitSettingId, $procedureType, $formKey, $companyId, $branchId)` | `?ProcedureSetting` | `?string`, `string`, `string`, `string`, `?string` | ProjectNotificationService request* methods — if `$explicitSettingId` is non-null, looks it up directly; otherwise delegates to `resolveSettingsForEntry()->first()` |

### ProcessWorkflowService

| Method | Returns | Parameters | Used By |
|--------|---------|-----------|---------|
| `createProcessesFromSettings($processableType, $processableId, $settings, $createdByUserId = null, $context = [])` | `?Process` | `string`, `string`, `Collection`, `?string`, `array` | EmployeeTaskRequestService, ClientRequestWorkflowService |
| `initializeProcessSteps($process, $context = [])` | `void` | `Process`, `array` | ProcessWorkflowService internals |
| `approveStep($id)` | `ProcessStep` | `string` (UUID) | ClientRequestWorkflowService, Process controllers |
| `rejectStep($id)` | `ProcessStep` | `string` (UUID) | ClientRequestWorkflowService, Process controllers |
| `getCurrentStep($process)` | `?ProcessStep` | `Process` | EmployeeTaskRequestService |

### ClientRequestWorkflowService

| Method | Returns | Parameters |
|--------|---------|-----------|
| `startForClientRequest($cr)` | `?Process` | `ClientRequest` |
| `createProcessForClientRequest($cr)` | `?Process` | `ClientRequest` |
| `actOnPendingStepForCurrentUser($clientRequestId, $action)` | `void` | `string`, `string ('approve'|'reject')` |
| `approve($processStepId)` | `ProcessStep` | `string` |
| `reject($processStepId)` | `ProcessStep` | `string` |
| `syncAfterClientRequestStatusChange($cr, $newStatus)` | `void` | `ClientRequest`, `string` |

### EmployeeTaskRequestService

| Method | Returns | Key Logic |
|--------|---------|-----------|
| `create($dto)` | `EmployeeTaskRequest` | Resolves creator branch, previews via `WorkflowEngine`, creates task, calls `markCreateTaskProceduresTaken()`, starts workflow |
| `approve($id, $adminId)` | `EmployeeTaskRequest` | findPendingStepForActor + `ProcessWorkflowService::approveStep` (Process-based) |
| `reject($id, $adminId, $reason)` | `EmployeeTaskRequest` | findPendingStepForActor + `ProcessWorkflowService::rejectStep` |
| `broadcastTaskNotification($task, $currentStep, $userIds = [])` | `void` | Takes `array $userIds` instead of deriving from actionTakers |
| `broadcastInboxCounts($userIds, $filters = [])` | `void` | Takes `array $userIds` instead of `ProcedureSettingStep` |
| `markCreateTaskProceduresTaken($task, $userId)` *(private)* | `void` | Finds all active `createTask`-form children and records each via `markProcedureTaken()` |

### EmployeeTaskExtensionWorkflowService

| Method | Returns | Key Change |
|--------|---------|-----------|
| `approve($extensionId, $adminId, $approvalNotes = null)` | `EmployeeTaskExtensionRequest` | Passes `processableType`/`processableId` to `advance()` — taken auto-recorded on final step |
| `reject($extensionId, $adminId, $rejectionReason)` | `EmployeeTaskExtensionRequest` | No change |

### EmployeeTaskExtensionService

| Method | Returns | Key Logic |
|--------|---------|-----------|
| `create($dto)` | `EmployeeTaskExtensionRequest` | Inherits procedure from parent task, resolves users with context, broadcasts |

### EmployeeTaskApprovalService

| Method | Returns | Key Logic |
|--------|---------|-----------|
| `create($taskId, $userId, $notes, $file)` | `EmployeeTaskApprovalRequest` | Resolves first step users with context, broadcasts. If auto-approved and `internalProcedureSettingId` provided → `markProcedureTaken()` |
| `approve($approvalId, $adminId, $approvalNotes)` | `EmployeeTaskApprovalRequest` | Passes morph params to `advance()` — taken auto-recorded on final step |
| `reject($approvalId, $adminId, $rejectionReason)` | `EmployeeTaskApprovalRequest` | `assertCanReject` with context; taken is NOT recorded on rejection |

### EmployeeTaskAvailableActionsService

| Method | Returns | Key Logic |
|--------|---------|-----------|
| `forTask($taskId)` | `list<array>` | Loads active child procedures, calls `getTakenProcedureIds()` from central morph table, filters by `appears_after_ids` (ALL must be taken) / `appears_before_ids` (hide if ANY taken), returns ordered visible list |

### EmployeeTaskFormConditionService

Backend enforcer for `InternalProcessForm` conditions. Called before workflow starts — throws `EmployeeTaskException` (HTTP 422) when a condition is violated.

| Method | Returns | Parameters | Called By |
|--------|---------|-----------|-----------|
| `checkCreateTaskConditions($userId, $companyId, $branchId, $durationHours, $taskDate, $taskLatitude, $taskLongitude, $currentLatitude, $currentLongitude)` | `void` | `string`, `string`, `?string`, `float`, `string`, `float`, `float`, `?float`, `?float` | `EmployeeTaskRequestService::create()` |
| `getPreConditionResults($userId, $companyId, $branchId, $currentLatitude, $currentLongitude)` | `array{all_passed: bool, conditions: array}` | `string`, `string`, `?string`, `?float`, `?float` | `EmployeeTaskController::preConditions()` |
| `getInFormConditionsPreview($companyId, $branchId)` | `list<array{key: string, label_ar: string, is_active: true, mode: ?string, constraints: array}>` | `string`, `?string` | `EmployeeTaskController::inFormConditions()` |
| `checkEndTaskConditions($task, $latitude, $longitude)` | `void` | `EmployeeTaskRequest`, `float`, `float` | `EmployeeTaskLifecycleService::end()` |

**Internal flow:**
1. Resolve the child `ProcedureSetting` via `ProcedureWorkflowService::resolveInternalProcedureSettingByForm()`.
2. If null or `conditions` is empty → return (no-op).
3. Read condition values from `$setting->conditions` (keyed by `InternalProcessCondition->value`).
4. Evaluate against real-time attendance / location data.
5. Throw on first violation.

---

## 1. Conceptual Model

The system models **business approval workflows** as configurable chains of **steps**.

- **ProcedureSetting** = static template (e.g., "Employee Task Request Approval")
- **ProcedureSettingStep** = single node in the chain (defines WHO must act)
- **Process** = runtime snapshot created when an entity enters a workflow
- **ProcessStep** = mutable runtime node tracking status (`pending`/`approved`/`rejected`)

**Rule**: Template (`ProcedureSettingStep`) is static configuration. Runtime (`ProcessStep`) is mutable state. Never confuse them.

---

## 2. Core Entities

### ProcedureSettingStep (Template Node)

| Field | Meaning |
|-------|---------|
| `action_taker_type` | Who can act: `specific_user`, `management_hierarchy`, `specific_procedures` |
| `action_taker_management_hierarchy_type` | For `management_hierarchy`: `branch_manager`, `management_manager`, `project_manager` |
| `action_taker_alternative_management_hierarchy_type` | Fallback if primary fails. **Must differ from primary.** |
| `action_taker_specific_procedure_type` | For `specific_procedures`: `branch`, `management`, `job_title`, `job_role` |
| `action_taker_specific_procedure_id` | Identifier (branch_id, job_title_id, or `1`/`2` for job_role) |
| `step_order` | Ordering within the chain |
| `is_approve` | Whether this is an approval gate |

### Process (Runtime Instance)

| Field | Meaning |
|-------|---------|
| `processable_type` | `employee_task`, `client_request`, etc. (was `employee_task_request` before June 2026) |
| `processable_id` | Polymorphic FK |
| `status` | `in_progress`, `completed`, `failed` |
| `execute_type` | `sequence` (one at a time) or `parallel` (all at once) |
| `template_snapshot` | JSON array of frozen step configs at creation time |

### ProcessStep (Runtime Node)

| Field | Meaning |
|-------|---------|
| `step_id` | FK to `ProcedureSettingStep.id` (**integer**, not UUID) |
| `assigned_user_id` | Primary user (first in resolved list) |
| `authorized_user_ids` | JSON array of ALL users who can act (new) |
| `status` | `pending` / `approved` / `rejected` |
| `action_by` | Who actually acted |

---

## 3. Enums

```php
enum ActionTakerType: string
{
    case SpecificUser        = 'specific_user';
    case ManagementHierarchy = 'management_hierarchy';
    case SpecificProcedures  = 'specific_procedures';
    case Himself             = 'himself'; // ⭐ NEW: submitter is the action taker; only "approve" form allowed
}

enum ActionTakerManagementHierarchyType: string
{
    case BranchManager     = 'branch_manager';
    case ManagementManager = 'management_manager';
    case ProjectManager    = 'project_manager';
    case DeputyManager     = 'deputy_manager'; // ⭐ NEW: resolves manager + ALL deputies (either can act)
}

// NOTE: action_taker_alternative_management_hierarchy_type is now a JSON ARRAY of these values,
// not a single string. e.g. ["branch_manager", "deputy_manager"]. Tried in order; first wins.

enum ActionTakerSpecificProcedureType: string
{
    case Branch     = 'branch';      // Manager of specific branch_id
    case Management = 'management';  // Manager of specific management_id
    case JobTitle   = 'job_title';   // ALL users with job_title_id
    case JobRole    = 'job_role';    // 1 = all mgmt managers, 2 = all branch managers
}

// NOTE: action_taker_specific_procedure_type and action_taker_specific_procedure_id are now
// JSON ARRAYs (parallel). e.g. type=["branch","management"], id=["5","12"].
// All targets are merged into authorized_user_ids. Rejection is non-fatal if ANY type is job_role.

enum ProcedureSettingType: string
{
    case EmployeeTask  = 'employee_task';   // Parent category for all employee task workflows
    case ClientRequest = 'client_request';  // Parent category for client request workflows
    case PriceOffer    = 'price_offer';     // Parent category for price offer workflows
    case Contract      = 'contract';        // Parent category for contract workflows
    case Meeting       = 'meeting';         // Parent category for meeting workflows
}

// Real namespace: Modules\Shared\InternalProcessType\Enums\InternalProcessForm
enum InternalProcessForm: string
{
    // ── Create forms (seeded automatically by InternalProcedureSettingsSeeder) ──
    case CreateClientRequest = 'createClientRequest';
    case CreatePriceOffer    = 'createPriceOffer';
    case CreateContract      = 'createContract';
    case CreateMeeting       = 'createMeeting';
    case CreateTask          = 'createTask';

    // ── End forms (seeded automatically by InternalProcedureSettingsSeeder) ────
    case EndTask             = 'endTask';           // employee_task
    case EndClientRequest    = 'endClientRequest';  // client_request
    case EndPriceOffer       = 'endPriceOffer';     // price_offer
    case EndContract         = 'endContract';       // contract
    case EndMeeting          = 'endMeeting';        // meeting

    // ── Other forms (not seeded automatically) ───────────────────────────────
    case StartTask           = 'startTask';
    case AttachAttachments   = 'attachAttachments';

    public function applicableTypes(): array
    {
        return match ($this) {
            self::CreateClientRequest,
            self::EndClientRequest    => ['client_request'],
            self::CreatePriceOffer,
            self::EndPriceOffer       => ['price_offer'],
            self::CreateContract,
            self::EndContract         => ['contract'],
            self::CreateMeeting,
            self::EndMeeting          => ['meeting'],
            self::CreateTask,
            self::StartTask,
            self::EndTask             => ['employee_task'],
            self::AttachAttachments   => ['client_request', 'price_offer', 'contract'],
        };
    }

    // conditions() returns InternalProcessCondition[] for each form:
    //   CreateTask        → AllowDuringShift, AllowOutsideShift, AllowOnHolidays,
    //                       InsideCustomLocations, MaxTaskDuration, MaxScheduledDateOffset
    //   StartTask         → AllowOnHolidays (holiday gating on task start)
    //   EndTask           → CanExitOutsideLocation
    //   AttachAttachments → MaxAttachments
    //   all others        → [] (default)

    public static function forType(string $procedureType): array;
    public function labelAr(): string;
    public function conditions(): array;
    public function toDefinition(): array;
    public static function values(): array;
}

// Form keys used as plain strings (no enum case — stored directly as form value in DB):
//   'extendTaskTime'  — used by EmployeeTaskExtensionService
//   'sendForApproval' — used by EmployeeTaskApprovalService
// These are valid ProcedureSetting child form values; they are NOT seeded automatically.

// InternalProcedureSettingsSeeder seeds all forms whose value starts with 'create' or 'end'.
// To add a new auto-seeded form, add it to InternalProcessForm and name it create* or end*.

enum InternalProcessCondition: string
{
    case AllowDuringShift       = 'allow_during_shift';
    case AllowOutsideShift      = 'allow_outside_shift';
    case AllowOnHolidays        = 'allow_on_holidays';
    case CanExitOutsideLocation = 'can_exit_outside_location';
    case MustBeInLocation       = 'must_be_in_location';
    case HasTaskDuration        = 'has_task_duration';
    case MaxDurationHours       = 'max_duration_hours';
    case MaxAttachments         = 'max_attachments';
    case MaxTaskDuration        = 'max_task_duration';
    case MaxScheduledDateOffset = 'max_scheduled_date_offset';
    case InsideShiftTime        = 'inside_shift_time';
    case InsideTaskLocation     = 'inside_task_location';
    case InsideCustomLocations  = 'inside_custom_locations';
    case EmployeeHasAttendance  = 'employee_has_attendance';
    case TaskIsApproved         = 'task_is_approved';
    case NoOpenTask             = 'no_open_task';
}
```

---

## 4. Action Taker Types

### 4.1 `specific_user`
- Step has explicit `actionTakers` pivot records with `user_id`.
- Any listed user can act. One acting advances the step.
- **Trap**: `actionTakers` is empty for other types. Code that iterates it without fallback fails silently.

### 4.2 `management_hierarchy`
- Assigned user(s) resolved from **CREATOR'S** org chart.
- Chain: creator → `UserProfessionalData` → `branch_id`/`management_id` → `ManagementHierarchy` → resolved user(s).
- If any link fails → fallback to `action_taker_alternative_management_hierarchy_type` array (tried in order).
- **Project Manager**: reads `context['project_id']` → `ProjectManagement.manager_id`. Falls back if unavailable.

#### Sub-types (primary + alternatives)
| Value | Resolution | Multi-user? |
|---|---|---|
| `branch_manager` | Creator's branch → `manager_id` | No (single) |
| `management_manager` | Creator's management → `manager_id` | No (single) |
| `project_manager` | `context['project_id']` → `ProjectManagement.manager_id` | No (single) |
| `deputy_manager` ⭐ | Creator's branch/management → **`manager_id` + ALL deputy managers** | **YES** |

#### `deputy_manager` behavior (primary type)
When `action_taker_management_hierarchy_type = deputy_manager`:
1. Resolves the branch/management **manager** (primary slot → `assigned_user_id`)
2. Resolves **all deputy managers** from `management_hierarchy_details` → `management_hierarchy_deputy_managers`
3. All are stored in `authorized_user_ids` → **any one acting ends the step**
4. Notifications are sent to ALL of them

#### `action_taker_alternative_management_hierarchy_type` (now an array)
- **Was**: single string e.g. `"branch_manager"`
- **Now**: JSON array e.g. `["branch_manager", "deputy_manager"]`
- Tried in order; first non-null result wins
- For `deputy_manager` in alternatives: returns the **first** deputy (single fallback user)

### 4.3 `specific_procedures`
- No explicit `actionTakers`. Resolved dynamically.
- **Was**: single `type`+`id` pair
- **Now**: parallel JSON arrays — `type[i]`+`id[i]` = one target. All targets merged into `authorized_user_ids`.

| Sub-type | Resolution |
|----------|-----------|
| `branch` | `ManagementHierarchy.find(id).manager_id` |
| `management` | `ManagementHierarchy.find(id).manager_id` |
| `job_title` | ALL users where `professionalData.job_title_id = id` |
| `job_role` | `id=1` → all management managers; `id=2` → all branch managers |

**Rejection Behavior**:
- `job_role`: Rejection **advances** the workflow (does NOT fail).
- All other types: Rejection **fails** the process.
- With multiple targets: rejection fails only if **NO target is `job_role`**.

### 4.4 `himself` ⭐ NEW
- The **original request submitter** (`createdByUserId`) is the action taker.
- Resolver returns `[$createdByUserId]` directly.
- Only the **`approve`** form is permitted for this action-taker type (enforced in request validation).
- Typical use-case: a step where the submitter must review/acknowledge their own request.

---

## 5. ActionTakerResolver

Single source of truth: `modules/ProcedureSetting/Services/ActionTakerResolver.php`

### API

```php
resolveUsersForStep($step, $createdByUserId, $context = []): array   // ALL IDs
resolveAssignedUserId($step, $createdByUserId, $context = []): ?string  // First ID
resolveManagerFromCreatorHierarchy($step, $createdByUserId, $context = []): ?string
rejectionShouldFailProcess($step): bool
```

### Resolution Dispatch

```
action_taker_type:
  'management_hierarchy'  → resolveManagementHierarchyUsers() → [ids...]
  'specific_procedures'   → resolveSpecificProcedureUsers()   → [many_ids...]
  default                 → resolveSpecificUserIds()          → [from actionTakers]
```

### New Format: `action_taker_management_hierarchies` (2026-06-22)

The `action_taker_management_hierarchies` column stores a JSON array of objects:
```json
[
  {"action_taker_management_hierarchy_type": "branch_manager", "is_Deputy_Director": false},
  {"action_taker_management_hierarchy_type": "management_manager", "is_Deputy_Director": true}
]
```

**Resolution behavior** (`resolveFromManagementHierarchiesArray`):
1. Iterate each row in array order.
2. For each row, resolve the manager by `action_taker_management_hierarchy_type`:
   - `project_manager` → looks up `context['project_id']` → `ProjectManagement.manager_id`. If no `project_id` or project not found → **skip row**, continue to next.
   - `branch_manager` → creator's `professionalData.branch_id` → `ManagementHierarchy.manager_id`
   - `management_manager` → creator's `professionalData.management_id` → `ManagementHierarchy.manager_id`
3. If `is_Deputy_Director === true`, also resolve all deputy managers for that hierarchy node and add them.
4. All resolved user IDs are **merged and de-duplicated**.
5. **Any one** of those users accepting/approving advances the step.

**Key behaviors:**
- If a row can't resolve (e.g. `project_manager` on an EmployeeTask without `project_id`), it is **silently skipped** — the next row is tried.
- `deputy_manager` is no longer a valid type value. Use `is_Deputy_Director: true` on any row instead.
- Legacy fields (`action_taker_management_hierarchy_type`, `action_taker_alternative_management_hierarchy_type`) still work as fallback when the new column is empty.

### Fallback Chain (legacy format)

```
resolveManagerFromCreatorHierarchy:
  hierarchy_type === 'project_manager'
    → resolveProjectManager(context['project_id'])
    → if null → tryAlternative()

  hierarchy_type === 'branch_manager'|'management_manager'
    → User.find(creator_id).professionalData.branch_id|management_id
    → ManagementHierarchy.find(id).manager_id
    → if null at ANY step → tryAlternative()

tryAlternative:
  → alternative_type + creator_id → resolve same as above
  → if still null → return null
```

---

## 6. Process Creation Flow

```
Entity created
  ↓
WorkflowEngine::startWorkflow(
  processableType, processableId,
  type, formKey,
  companyId, branchId,
  createdByUserId, context
)
  ↓
WorkflowEngine::resolveParentSetting()
  ├─ try parent by company + branch workflow
  └─ fallback to WorkFlow::defaultForCompany(companyId, type)
  ↓
WorkflowEngine::resolveSettingsForEntry()
  ├─ formKey === null → run parent setting (ClientRequest style)
  └─ formKey !== null → run matching child settings (EmployeeTask createTask style)
  ↓
ProcessWorkflowService::createProcessesFromSettings()
  ↓
For each ProcedureSettingStep:
  resolvedUsers = ActionTakerResolver.resolveUsersForStep(step, creator_id, context)
  if resolvedUsers === []: SKIP step entirely
  snapshot[] = {
    step_id, template_step_order,
    assigned_user_id: resolvedUsers[0],
    authorized_user_ids: resolvedUsers,
    specific_procedure_type: step.action_taker_specific_procedure_type?.value,
    escalation_management_hierarchy_id: step.escalation_management_hierarchy_id
  }
  ↓
Process created with template_snapshot = snapshot
  ↓
First ProcessStep created from snapshot[0]
  ↓
WorkflowStepActivated event fires with authorized user IDs and creation context
```

**Trap**: Unresolvable steps are **silently skipped**. The workflow may have fewer steps than the template.
If every step resolves to zero users, `WorkflowEngine::startWorkflow()` returns `autoApprove = true`.

---

## 7. ProcessWorkflowService

### approveStep(processStepId)
1. Lock `process_steps` + `processes` rows
2. Find snapshot row
3. `authorizedUsers = step.authorized_user_ids ?? snapshot['authorized_user_ids'] ?? [assigned_user_id]`
4. Check `Auth::id()` in list → 403 if not
5. Check status is `Pending` → 422 if not
6. Update: `status = Approved`, `action_by = Auth::id()`
7. `advanceProcessAfterAction()`

### rejectStep(processStepId)
Same as approve but:
- `status = Rejected`
- `isJobRole = snapshot['specific_procedure_type'] === 'job_role'`
- If `isJobRole` → `advanceProcessAfterAction()` (advances!)
- Else → `process.status = Failed`

### advanceProcessAfterAction
**Sequence**: actedCount = approved + rejected. If `actedCount < count(snapshot)`, create next step from `snapshot[actedCount]`. Else mark `Completed`.

**Parallel**: All steps created upfront. Complete when ALL acted on.

---

## 8. ClientRequest Integration

`ClientRequestWorkflowService::createProcessForClientRequest($cr)`:
- Keeps the existing-process guard.
- Calls `WorkflowEngine::startWorkflow()` with:
  - `processableType = 'client_request'`
  - `type = ProcedureSettingType::ClientRequest->value`
  - `formKey = null`
  - `companyId = $cr->company_id`
  - `branchId = $cr->branch_id`
  - `createdByUserId = $cr->created_by_user_id`
- Parent setting resolution uses company + branch workflow and falls back to the company default workflow.
- Snapshot creation and first step activation are delegated to `ProcessWorkflowService`.
- `approve()` / `reject()`: Uses `assertActorCanActOnStep()` which reads `authorized_user_ids` from snapshot.
- `closeProcessOnClientRequestAccepted(ClientRequest $cr)`: Auto-approves pending steps where actor is in `authorized_user_ids`.

---

## 9. EmployeeTask Integration

The EmployeeTask module now uses **Internal Procedure Settings** — child rows under a parent `ProcedureSetting` with `type = 'employee_task'`. Each child has a `form` key that defines what action it represents.

### Architecture

```
Parent ProcedureSetting (type = 'employee_task')
├── Child: form = 'createTask'          → Task creation workflow
├── Child: form = 'startTask'           → Task start workflow
├── Child: form = 'extendTaskTime'      → Extension request workflow
├── Child: form = 'sendForApproval'     → Completion approval workflow
├── Child: form = 'endTask'             → Task end/completion workflow
├── Child: form = 'confirmLocation'     → Location confirmation (can have MULTIPLE)
└── ... more children with same or different forms
```

Each child has:
- Its own `name` (display label)
- Its own `steps` (workflow steps)
- Its own `conditions` (JSON array of InternalProcessCondition)
- `appears_before_ids` / `appears_after_ids` (ordering constraints — JSON arrays of UUIDs)
- `sort_order` (display order)

> **⚠ Unregistered Form Keys:** `extendTaskTime` and `sendForApproval` are **NOT** registered in the `InternalProcessForm` enum (see §3). They are used as raw string literals in `EmployeeTaskExtensionService` and `EmployeeTaskApprovalService` respectively. This means:
> - No condition definitions — `EmployeeTaskFormConditionService` does not evaluate conditions for these forms.
> - Not listed in `InternalProcessForm::forType('employee_task')` — only `createTask`, `startTask`, `endTask` are returned.
> - No label, no sort order, no `applicableTypes()` mapping, no validation rules, no default condition values.
> - Not auto-seeded by `InternalProcedureSettingsSeeder` (only seeds forms starting with `create` or `end`).
> - They still work at runtime because `resolveInternalProcedureSettingByForm()` queries the raw `form` column value directly.
>
> **Full details and registration steps:** See `modules/EmployeeTask/EMPLOYEE_TASK_MODULE_GUIDE.md` → "Unregistered Form Keys" section.
> **Attendance integration context:** See `ATTENDANCE_MODULE_DEEP_REFERENCE.md` §24.12.

### Resolving a Child Procedure Setting

`ProcedureWorkflowService::resolveInternalProcedureSettingByForm()`:
```
1. Delegates to WorkflowEngine::resolveSettingsForEntry()
2. Finds parent by type + company + branch, with default workflow fallback
3. Finds first child where parent_id = parent.id AND form = 'extendTaskTime'
4. Return child with steps eager-loaded
```

**CRITICAL**: When multiple children share the same `form` (e.g., two `confirmLocation` entries), the backend must receive the specific `internal_procedure_setting_id` to load the correct child. The mobile app gets this ID from the `available-actions` API.

### 9.1 Task Request
**Creation** (`EmployeeTaskRequestService::create()`):
```
context = projectId ? ['project_id' => projectId] : []
creator branch = user.userProfessionalData.branch_id
preview = engine.previewResponsibles('employee_task', 'createTask', companyId, branchId, userId, context)
create task record
engine.startWorkflow('employee_task', task->id, 'employee_task', 'createTask', companyId, branchId, userId, context)
currentStep = processService.getCurrentStep(process)
update task: approval_responsible_id = currentStep.assigned_user_id
WorkflowStepActivated event broadcasts notification + inbox counts centrally
```

**Approval/Rejection**:
- `findPendingStepForActor()` searches pending ProcessSteps, checks `authorized_user_ids`.
- Calls `workflow->advance()` / `assertCanReject()` with context.

### 9.2 Task Extension
**Creation** (`EmployeeTaskExtensionService::requestExtension()`):
- Resolves child by `form = 'extendTaskTime'` under parent `type = 'employee_task'`.
- Optionally accepts explicit `internal_procedure_setting_id` for precise child selection.
- No `project_id` context → `project_manager` falls back to alternative.
- Resolves users with context, broadcasts to resolved IDs.

**Approval/Rejection** (`EmployeeTaskExtensionWorkflowService`):
- Passes `project_id` context to `workflow->advance()` and `assertCanReject()`.

### 9.3 Task Completion Approval
**Creation** (`EmployeeTaskApprovalService::create()`):
- Resolves child by `form = 'sendForApproval'` under parent `type = 'employee_task'`.
- Optionally accepts explicit `internal_procedure_setting_id` for precise child selection.
- Resolves first step users with `project_id` context.
- Broadcasts to resolved IDs (not template `actionTakers`).

### 9.4 Available Actions API (Mobile)

`GET /employee-tasks/{taskId}/available-actions`

Returns all **active** (`is_active = true`) child internal procedure settings for the task, filtered by ordering dependencies (`appears_before_ids` / `appears_after_ids`):

```json
[
  {
    "id": "child-uuid-1",
    "name": "تأكيد دخول الموقع",
    "form": { "key": "confirmLocation", "label_ar": "تأكيد الموقع" },
    "conditions": [...],
    "appears_before_ids": ["child-uuid-2"],
    "appears_after_ids": [],
    "sort_order": 1
  },
  {
    "id": "child-uuid-2",
    "name": "تأكيد خروج الموقع",
    "form": { "key": "confirmLocation", "label_ar": "تأكيد الموقع" },
    "conditions": [...],
    "appears_before_ids": [],
    "appears_after_ids": ["child-uuid-1"],
    "sort_order": 2
  }
]
```

#### Inactive Status

Each internal procedure setting has an `is_active` boolean field (default: `true`). When set to `false`:
- The procedure is **excluded** from the `available-actions` API response.
- It can be toggled via `PUT /procedure-settings/{id}/internal-procedures/{internalProcedureId}/set-status` with body `{ "is_active": false }`.
- It can also be set during **create** (`POST /procedure-settings/{id}/internal-procedures`) and **update** (`PUT /procedure-settings/{id}/internal-procedures/{internalProcedureId}`) via the `is_active` field.

#### Ordering Dependencies (`appears_before_ids` / `appears_after_ids`)

Each internal procedure can declare ordering constraints as **arrays of UUIDs** (multiple prerequisites supported):

- **`appears_after_ids`** *(array)*: **ALL** referenced procedures must be "taken" before this procedure is shown. If any prerequisite is not yet taken, this procedure is **hidden** (AND logic).
- **`appears_before_ids`** *(array)*: This procedure is hidden once **ANY** of the referenced procedures is "taken" (OR logic). Send an empty array `[]` to disable.

#### "Taken" Status Definition

A procedure is considered "taken" when it has been completed or approved. The "taken" status is tracked centrally in the **`internal_procedure_takens`** morph table, managed by `ProcedureWorkflowService`.

| Form | Taken When | How it's recorded |
|------|-----------|-------------------|
| `createTask` | **Auto-approve path**: marked immediately on task creation. **Real workflow path**: marked when all Process steps are approved (`ProcessWorkflowService::fireProcedureTakenIfApplicable()`) | `WorkflowProcedureTaken` event fired by `ProcessWorkflowService` on process `Completed`; or immediately via `markCreateTaskProceduresTaken()` when no approvers exist |
| `startTask` | Employee starts the task | `EmployeeTaskController::start()` calls `markProcedureTaken()` with the `internal_procedure_setting_id` from the request |
| `endTask` | Task is ended directly (no procedure) | `EmployeeTaskLifecycleService::end()` calls `markProcedureTaken()` |
| `endTask` (via end request) | End request is approved through workflow | `ProcedureWorkflowService::advance()` auto-marks when `isFinal = true` |
| `confirmLocation` | Location is confirmed via ping | `EmployeeTaskController::locationPing()` calls `markProcedureTaken()` |
| `extendTaskTime` | Extension request is approved through workflow | `ProcedureWorkflowService::advance()` auto-marks when `isFinal = true` |
| `sendForApproval` | Approval request is approved through workflow | `ProcedureWorkflowService::advance()` auto-marks when `isFinal = true` |

#### Central Morph Table: `internal_procedure_takens`

Instead of storing taken IDs on each entity, a dedicated morph table centralizes the tracking:

```
internal_procedure_takens
  id                  (UUID, PK)
  company_id          (UUID, tenant)
  processable_type    (string, morph type e.g. 'employee_task')
  processable_id      (UUID, morph ID)
  procedure_setting_id (UUID, FK → procedure_settings)
  form                (string, nullable)
  taken_by            (UUID, nullable)
  taken_at            (timestamp)
```

**Unique constraint**: `(processable_type, processable_id, procedure_setting_id)` — prevents duplicate entries.

**ProcedureWorkflowService** provides three central methods:

| Method | Description |
|--------|-------------|
| `markProcedureTaken($processableType, $processableId, $procedureSettingId, $takenBy)` | Records a procedure as taken (idempotent via `firstOrCreate`) |
| `getTakenProcedureIds($processableType, $processableId)` | Returns all taken procedure setting IDs for an entity |
| `isProcedureTaken($processableType, $processableId, $procedureSettingId)` | Checks if a specific procedure is taken |

**Auto-marking in `advance()`**: When `advance()` is called with `processableType` and `processableId` parameters and the workflow reaches its final step (`isFinal = true`), the procedure is automatically marked as taken. Callers no longer need to manually call `markProcedureTaken()` after workflow completion.

#### Per-Procedure Granularity

When multiple procedures share the same form (e.g., two `confirmLocation` procedures), only the specific procedure that was acted upon is marked as "taken" in the morph table. The `internal_procedure_setting_id` parameter passed by the mobile app determines which specific procedure gets marked.

Each action endpoint (`startTask`, `endTask`, `locationPing`, `requestApproval`, `storeExtension`) accepts an optional `internal_procedure_setting_id` parameter. When provided, that specific procedure is marked as taken.

#### Example Scenario

```
Procedures:
  A: createTask (sort_order: 100)
  B: confirmLocation, appears_after_ids = [A] (sort_order: 200)
  C: startTask, appears_after_ids = [A] (sort_order: 300)
  D: sendForApproval, appears_after_ids = [A, B] (sort_order: 400)  ← requires BOTH A and B

Timeline:
  1. Task created → A is taken
  2. available-actions returns: B, C (both appear after A which is taken)
  3. User confirms location → B is taken
  4. available-actions returns: C, D (D appears after B which is now taken)
  5. User starts task → C is taken
  6. available-actions returns: D
  7. User sends for approval → D is taken (after admin approval)
  8. available-actions returns: [] (all taken)
```

If `C` had `appears_before_ids = [B]` instead:
```
  1. Task created → A is taken
  2. available-actions returns: B, C
  3. User confirms location → B is taken
  4. available-actions returns: D (C is hidden because B is taken and C appears_before B)
```

**Duplicate Forms**: Two children can share the same `form` key. The mobile app MUST send back the specific `id` of the tapped item, not just the `form` key.

---

## 10. Context Passing (`project_id`)

Context is an associative array. Only key used: `project_id`.

**Flow**:
```
EmployeeTaskRequestService::create()
  → ProcessWorkflowService::createProcessesFromSettings(..., context)
    → ActionTakerResolver::resolveUsersForStep(..., context)
      → resolveProjectManager() reads context['project_id']
```

**Trap**: Context is NOT automatic. Every caller MUST build and pass it. Without it, `project_manager` always falls back or returns null.

---

## 11. Multi-User Authorization

### The Problem
Before: Only `assigned_user_id` stored. For `job_title`/`job_role`, many users authorized.

### The Solution
`authorized_user_ids` stored in BOTH:
1. `processes.template_snapshot` JSON
2. `process_steps.authorized_user_ids` DB column (JSON)

### Authorization Check Hierarchy
```
1. step.authorized_user_ids (DB column) — preferred
2. snapshot['authorized_user_ids'] (fallback)
3. [assigned_user_id] (final fallback)
```

### Inbox Queries

**Process-level inbox filter** (centralised in `WorkflowEngine::pendingProcessScopeForUser`):
```php
// Returns a \Closure for whereHas('employeeTask.processes', ...)
$scope = $engine->pendingProcessScopeForUser(
    ProcedureSettingType::ProjectNotificationTask->value,
    $userId,
);
$query->whereHas('employeeTask.processes', $scope);
```
The closure filters for:
- `processable_type` = the given type
- `status` = `ProcessStatus::InProgress`
- Has at least one `ProcessStep` with `status = Pending` AND (`assigned_user_id = $userId` OR `authorized_user_ids` JSON-contains `$userId`)

**No top-level status filter**: The inbox query does **not** filter by the notification's `status` column. Any notification (pending, approved, in_progress, completed) that has a pending workflow step assigned to the user will appear. This ensures update/site-status/fine/postponement workflows on already-approved notifications show up.

**Resolving pending process descriptors** (centralised in `WorkflowEngine::resolvePendingProcessesForUser`):
```php
$descriptors = $engine->resolvePendingProcessesForUser($task, $userId);
// Each: {process_id, procedure_setting_id, form, mobile_inbox_action_key, pending_step_id, pending_step_order}
```
Requires `$task->processes` relation to be loaded. Calls `loadMissing('processes.procedureSetting')` internally. The `form` key falls back to `$process->procedureSetting?->form` if not in `$process->metadata['form']`.

**Step-level check** (used by `ProcessWorkflowService::approveStep`):
```php
$q->where('assigned_user_id', $adminId)
  ->orWhereJsonContains('authorized_user_ids', $adminId);
```

**Trap**: `whereJsonContains` requires exact type matching. Store UUIDs as strings.

---

## 12. Notification Broadcasting

### Process-Based Activation

For `Process` workflows, `ProcessWorkflowService::createProcessStep()` fires:

```php
new WorkflowStepActivated(
    processStep: $step,
    templateStep: $templateStep,
    userIds: $authorizedUserIds,
    context: $context,
)
```

`SendWorkflowStepNotification` then:
1. Looks up a module notifier from `WorkflowNotifierRegistry` by `process.processable_type`.
2. Calls `WorkflowNotifier::notifyStepActivated()` for real-time module-specific behavior.
3. Broadcasts `InboxCountsUpdated` with counts from `WorkflowNotifier::inboxCountsForUser()`.
4. Sends `WorkflowActionRequired` by mail/SMS when `notify_by_email` or `notify_by_sms` is enabled.

### EmployeeTaskNotification Event

```php
new EmployeeTaskNotification($task, $currentStep, $userIds = [])
```

- `$userIds` provided → broadcasts to those IDs only.
- Empty → falls back to `$currentStep->actionTakers->pluck('user_id')`.

**Critical Fix**: Previously, broadcasters loaded `actionTakers` from the template step. For `management_hierarchy` and `specific_procedures`, `actionTakers` is EMPTY → **NO ONE received notifications**.

Non-Process EmployeeTask extension/approval flows still call:
```php
$userIds = $workflow->resolveActionTakerUserIdsForStep($firstStep, $task->user_id, $context);
event(new EmployeeTaskNotification($task, $firstStep, $userIds));
$requestService->broadcastInboxCounts($userIds);
```

EmployeeTask task creation does not call this manually anymore; it uses `WorkflowStepActivated` through `WorkflowEngine::startWorkflow()`.

---

## 13. Presenters

### ProcedureSettingStepPresenter
- `action_taker_type_label`: "Specific User", "Management Hierarchy", "Specific Procedures"
- `action_taker_management_hierarchy_type_label`: "Branch Manager", "Management Manager", "Project Manager"
- Includes alternative hierarchy and specific procedure fields.

### EmployeeTaskRequestPresenter::presentCurrentStep
```
1. Template actionTakers loaded and not empty → use those
2. Else if process step has authorized_user_ids → use those
3. Else empty
```

### InboxItemPresenter::stepFromProcess
Reads from `ProcessStep` directly: `$processStep->authorized_user_ids ?? [$assigned_user_id]`

---

## 14. Migrations

1. `2026_06_12_000001_add_action_taker_upgrade_columns_to_procedure_setting_steps.php`
   - `action_taker_alternative_management_hierarchy_type`
   - `action_taker_specific_procedure_type`
   - `action_taker_specific_procedure_id`

2. `2026_06_12_000002_add_authorized_user_ids_to_process_steps.php`
   - `authorized_user_ids` (JSON, nullable)

---

## 15. Traps & Rules for AI

### 15.1 The `different` Rule
`action_taker_alternative_management_hierarchy_type` **MUST** differ from `action_taker_management_hierarchy_type`. Validation enforces this.

### 15.2 Empty `actionTakers`
For `management_hierarchy` and `specific_procedures`, `ProcedureSettingStep->actionTakers` is EMPTY. Any code assuming it contains users fails silently.

### 15.3 `assigned_user_id` vs `authorized_user_ids`
- `assigned_user_id` = primary user only.
- `authorized_user_ids` = full list.
- Always check `authorized_user_ids` first.

### 15.4 Snapshot vs DB Column
Authorization prefers DB column, falls back to snapshot. Keep both in sync.

### 15.5 Context Forgetting
`project_id` context is NOT automatic. Every caller must pass `['project_id' => $task->project_id]`.

### 15.6 JobRole Rejection
`job_role` rejection **advances** the workflow, does NOT fail. This is unique.

### 15.7 Skipped Steps
If `resolveUsersForStep()` returns `[]`, step is **omitted from snapshot**. Fewer runtime steps than template steps.

### 15.8 Integer ID Trap
`ProcedureSettingStep.id` is **INTEGER**, not UUID. Process steps reference it as `step_id` (integer). Most other entities use UUIDs.

### 15.9 `specific_procedure_id` Type
Stored as `string` in DB. Can be integer (branch/mgmt IDs), string (job_title UUID), or `1`/`2` (job_role constants).

### 15.10 Extension/Approval Processable Type
Extensions and approvals do NOT have their own `Process` records. They use `ProcedureWorkflowService` directly on `ProcedureSettingStep` (not `ProcessWorkflowService`). No `template_snapshot`, no `ProcessStep` records.

### 15.11 `approval_responsible_id` Staleness
Set at creation time, NOT updated on workflow advance. Do NOT use for authorization. Only for legacy display/fallback.

### 15.12 Lock Safety
`approveStep()` and `rejectStep()` use `DB::transaction()` + `lockForUpdate()`. Never remove.

### 15.13 `findPendingStepForActor`
Iterates ALL pending steps, checks `authorized_user_ids`. Does NOT match `assigned_user_id` only.

### 15.14 `employeeTaskProcess` vs `processes`
`EmployeeTaskRequest` has `processes()` (HasMany) and `employeeTaskProcess()` (HasOne filtered by type). Presenters needing the active process must use `employeeTaskProcess`.

---

## 16. Decision Flowcharts

### Action Taker Resolution
```
ProcedureSettingStep
  |
  +-- action_taker_type?
       |
       +-- 'specific_user' → step->actionTakers->pluck('user_id')
       |
       +-- 'management_hierarchy'
       |     +-- 'project_manager' → context['project_id'] → ProjectManagement.manager_id
       |     +-- 'branch_manager'  → creator->professionalData->branch_id → ManagementHierarchy.manager_id
       |     +-- 'management_manager' → creator->professionalData->management_id → ManagementHierarchy.manager_id
       |     → Any failure → tryAlternative()
       |
       +-- 'specific_procedures'
             +-- 'branch'      → ManagementHierarchy.find(id).manager_id
             +-- 'management'  → ManagementHierarchy.find(id).manager_id
             +-- 'job_title'   → User.whereHas(job_title_id = id).pluck('id')
             +-- 'job_role'    → id=1 ? all_mgmt_managers : all_branch_managers
```

### Rejection Behavior
```
rejectStep()
  |
  +-- specific_procedure_type === 'job_role' ?
       +-- YES → advanceProcessAfterAction()  [ADVANCES]
       +-- NO  → process.status = Failed
```

### Authorization Check
```
getAuthorizedUsersForStep(process, step)
  |
  +-- step.authorized_user_ids !== null ?
       +-- YES → return step.authorized_user_ids
       +-- NO  → read snapshot['authorized_user_ids'] ?? [assigned_user_id]
```

---

## 17. Complete Notification Architecture

The system has **three notification channels**:
1. **Real-time (WebSocket)** — Laravel Echo / Pusher
2. **Email** — Configurable per step (`notify_by_email`)
3. **SMS** — Configurable per step (`notify_by_sms`)

Real-time for Process-based workflows is routed through `WorkflowNotifierRegistry`. Email and SMS are handled by `SendWorkflowStepNotification` using `WorkflowActionRequired`.

### 17.1 Configuration Flags on ProcedureSettingStep

| Field | Type | Meaning |
|-------|------|---------|
| `notify_by_email` | bool | If true, send email to action takers when step becomes active |
| `notify_by_sms` | bool | If true, send SMS to action takers when step becomes active |

These are set in the admin UI when configuring the procedure setting step. They are stored in the DB and available on every `ProcedureSettingStep` instance.

### 17.2 When Notifications Should Fire

Notifications should be dispatched at these lifecycle events:

1. **Step Becomes Active** — A new `ProcessStep` is created from the snapshot. This is when the action taker first learns they need to act.
2. **Step is Approved** — The actor approved. Notify the entity owner (e.g., employee who submitted the task) that their request advanced.
3. **Step is Rejected** — The actor rejected. Entity-owner notifications are module-specific.
4. **Process Completes** — All steps done. Entity-owner notifications are module-specific.
5. **Auto-Approve Timer Expires** — If `requires_approval_within_period` and `skipping_period` are set, `AutoApproveWorkflowStep` can approve the pending step after the configured delay.

### 17.3 Real-Time Events (Already Implemented)

#### `EmployeeTaskNotification`

**File**: `modules/EmployeeTask/Events/EmployeeTaskNotification.php`

```php
class EmployeeTaskNotification implements ShouldBroadcast
{
    public function __construct(
        public EmployeeTaskRequest $task,
        public ProcedureSettingStep $currentStep,
        public array $userIds = [],
    ) {}

    public function broadcastOn(): array
    {
        // Channels: employee-task.notification.{user_id}
        $userIds = $this->userIds !== []
            ? $this->userIds
            : $this->currentStep->actionTakers->pluck('user_id')->all();

        foreach ($userIds as $userId) {
            $channels[] = new Channel('employee-task.notification.' . $userId);
        }
        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'employee-task.notification';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->task->id,
            'serial_number' => $this->task->serial_number,
            'title' => $this->task->title,
            'status' => $this->task->status,
            'task_date' => $this->task->task_date?->format('Y-m-d'),
            'duration_hours' => $this->task->duration_hours,
            'description' => $this->task->description,
            'notes' => $this->task->notes,
            'requested_by' => ['id' => $this->task->user_id, 'name' => optional($this->task->user)->name ?? 'Unknown'],
            'current_step' => ['id' => $this->currentStep->id, 'name' => $this->currentStep->name, 'step_order' => $this->currentStep->step_order],
            'created_at' => $this->task->created_at?->toISOString(),
            'notification_type' => 'employee_task',
        ];
    }
}
```

**Broadcasted by**:
- `EmployeeTaskWorkflowNotifier` — when a Process-based EmployeeTask step is activated centrally.
- `EmployeeTaskExtensionService::create()` — when extension is created.
- `EmployeeTaskApprovalService::create()` — when completion approval is submitted.

**Key insight**: The event takes `$userIds` explicitly. If provided, channels are built from those IDs. If empty, it falls back to `$currentStep->actionTakers`. For `management_hierarchy` and `specific_procedures`, `actionTakers` is EMPTY, so the explicit `$userIds` parameter is **mandatory** for correct delivery.

#### `InboxCountsUpdated`

**File**: `modules/EmployeeTask/Events/InboxCountsUpdated.php`

```php
class InboxCountsUpdated implements ShouldBroadcast
{
    public function __construct(
        public string $userId,
        public int $pendingTasks,
        public int $pendingExtensions,
        public int $pendingApprovals,
        public int $total,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('employee-task.inbox-counts.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'employee-task.inbox-counts';
    }
}
```

Broadcasts to each authorized user so their inbox badge count updates in real-time.

#### `ClientRequestCreated`

**File**: `modules/ClientRequest/Events/ClientRequestCreated.php`

Broadcasts to each `receiverEmployees` on the `ClientRequest`:
```
Channel: client-request.{receiverUserId}
Event:   client-request.created
Payload: {id, serial_number, status, company, created_by, notes, created_at, notification_type}
```

#### `ClientRequestStatusChanged`

**File**: `modules/ClientRequest/Events/ClientRequestStatusChanged.php`

Broadcasts to each `receiverEmployees` when status changes:
```
Channel: client-request.{receiverUserId}
Event:   client-request.status-changed
Payload: {id, serial_number, status, action, company, created_by, reject_cause, updated_at, notification_type}
```

### 17.4 Where to Add Module Real-Time Notifications

#### Register a `WorkflowNotifier`

Create a notifier implementing:

```php
interface WorkflowNotifier
{
    public function notifyStepActivated(ProcessStep $step, array $userIds, array $context = []): void;

    /** @return array{pending_tasks:int,pending_extensions:int,pending_approvals:int,total:int} */
    public function inboxCountsForUser(string $userId): array;
}
```

Then register it in the module service provider:

```php
app(WorkflowNotifierRegistry::class)->register('your_processable_type', app(YourWorkflowNotifier::class));
```

Do not modify `SendWorkflowStepNotification` for each new module.

#### Existing Notifiers

- `EmployeeTaskWorkflowNotifier` sends `EmployeeTaskNotification` and real EmployeeTask inbox counts.
- `ClientRequestWorkflowNotifier` is registered for `client_request`; step activation is currently no-op and counts are zero until a ClientRequest inbox exists.

### 17.5 Email / SMS

Email/SMS for Process-based flows are already centralized:

- `notify_by_email` adds `mail`.
- `notify_by_sms` adds `sms`.
- `WorkflowActionRequired` sends the mail/SMS notification.

Non-Process EmployeeTask extension/approval flows call `dispatchStepNotifications()` directly because they do not create `ProcessStep` records.

---

## 18. Complete Service Dependency Map

### 18.1 Core Services

| Service | File | Responsibilities | Depends On |
|---------|------|------------------|------------|
| `ActionTakerResolver` | `modules/ProcedureSetting/Services/ActionTakerResolver.php` | Resolve authorized users for ANY step type | `User`, `ManagementHierarchy`, `ProjectManagement` |
| `WorkflowEngine` | `modules/ProcedureSetting/Services/WorkflowEngine.php` | Central setting resolution, preview, and workflow start | `ActionTakerResolver`, `ProcessWorkflowService` |
| `ProcedureWorkflowService` | `modules/ProcedureSetting/Services/ProcedureWorkflowService.php` | Template workflow stepping for non-Process flows; delegates preview/resolution to `WorkflowEngine` | `ActionTakerResolver`, `WorkflowEngine` |
| `ProcessWorkflowService` | `modules/Process/Services/ProcessWorkflowService.php` | Creates processes, handles approve/reject | `ActionTakerResolver` |
| `WorkflowNotifierRegistry` | `modules/Process/Services/WorkflowNotifierRegistry.php` | Maps `processable_type` to module notifiers | Registered `WorkflowNotifier` instances |
| `ClientRequestWorkflowService` | `modules/ClientRequest/Services/ClientRequestWorkflowService.php` | ClientRequest-specific approval/rejection/status transitions; starts workflow via `WorkflowEngine` | `WorkflowEngine` |

### 18.2 EmployeeTask Services

| Service | File | Responsibilities | Depends On |
|---------|------|------------------|------------|
| `EmployeeTaskRequestService` | `modules/EmployeeTask/Services/EmployeeTaskRequestService.php` | Create/approve/reject/cancel tasks | `WorkflowEngine`, `ProcessWorkflowService`, `EmployeeTaskRepository` |
| `EmployeeTaskExtensionWorkflowService` | `modules/EmployeeTask/Services/EmployeeTaskExtensionWorkflowService.php` | Approve/reject extensions via workflow | `ProcedureWorkflowService`, `EmployeeTaskRepository` |
| `EmployeeTaskExtensionService` | `modules/EmployeeTask/Services/EmployeeTaskExtensionService.php` | Create extension requests | `ProcedureWorkflowService`, `EmployeeTaskRequestService` |
| `EmployeeTaskApprovalService` | `modules/EmployeeTask/Services/EmployeeTaskApprovalService.php` | Create/approve/reject completion approvals | `ProcedureWorkflowService`, `EmployeeTaskRequestService` |
| `EmployeeTaskWorkflowNotifier` | `modules/EmployeeTask/Services/EmployeeTaskWorkflowNotifier.php` | EmployeeTask step activation real-time + inbox counts | `EmployeeTaskRequestService` |
| `ClientRequestWorkflowNotifier` | `modules/ClientRequest/Services/ClientRequestWorkflowNotifier.php` | ClientRequest notifier registration placeholder | none |

### 18.3 Presenters

| Presenter | File | What It Presents |
|-----------|------|-----------------|
| `ProcedureSettingStepPresenter` | `modules/ProcedureSetting/Presenters/ProcedureSettingStepPresenter.php` | Step config with labels |
| `EmployeeTaskRequestPresenter` | `modules/EmployeeTask/Presenters/EmployeeTaskRequestPresenter.php` | Full task with current step |
| `InboxItemPresenter` | `modules/EmployeeTask/Presenters/InboxItemPresenter.php` | Unified inbox shape for all 3 types |
| `EmployeeTaskApprovalPresenter` | `modules/EmployeeTask/Presenters/EmployeeTaskApprovalPresenter.php` | Approval request detail |
| `EmployeeTaskExtensionPresenter` | `modules/EmployeeTask/Presenters/EmployeeTaskExtensionPresenter.php` | Extension request detail |

### 18.4 Events

| Event | File | Broadcasts To |
|-------|------|--------------|
| `EmployeeTaskNotification` | `modules/EmployeeTask/Events/EmployeeTaskNotification.php` | `employee-task.notification.{user_id}` |
| `InboxCountsUpdated` | `modules/EmployeeTask/Events/InboxCountsUpdated.php` | `employee-task.inbox-counts.{user_id}` |
| `ClientRequestCreated` | `modules/ClientRequest/Events/ClientRequestCreated.php` | `client-request.{receiverUserId}` |
| `ClientRequestStatusChanged` | `modules/ClientRequest/Events/ClientRequestStatusChanged.php` | `client-request.{receiverUserId}` |

### 18.5 Repositories

| Repository | File | Queries |
|------------|------|---------|
| `EmployeeTaskRepository` | `modules/EmployeeTask/Repositories/EmployeeTaskRepository.php` | Inbox queries (task/extension/approval), filter queries |

---

## 19. How to Add New Features (Extension Guide)

### 19.1 Adding a New Action Taker Type

1. **Add enum case** to `ActionTakerType`.
2. **Add resolution method** in `ActionTakerResolver`.
3. **Update `resolveUsersForStep()`** dispatch to handle new case.
4. **Update validation** in `CreateProcedureSettingStepRequest` and `UpdateProcedureSettingStepRequest`.
5. **Update `ProcedureSettingStepPresenter`** to include a label.
6. **Update `ProcedureSettingStep` model** `$fillable` and `$casts` if new DB columns needed.
7. **Create migration** if new columns needed.
8. **Update DTO** `CreateProcedureSettingStepDTO`.
9. **Test** `ActionTakerResolver` resolution.
10. **Update deep guide** (this file).

### 19.2 Adding Email Notifications

1. **Create Mailable** class (e.g., `EmployeeTaskActionRequiredMail`).
2. **Create blade template** for email body.
3. **Inject dispatcher** or add logic to `broadcastTaskNotification()` in:
   - `EmployeeTaskRequestService`
   - `EmployeeTaskExtensionService`
   - `EmployeeTaskApprovalService`
4. **For Process-based flows**, no change is needed in `ProcessWorkflowService`; it already fires `WorkflowStepActivated`.
5. **Read `notify_by_email`** flag from the `ProcedureSettingStep`.
6. **Resolve user emails** from `authorized_user_ids`.
7. **Queue mail** using `Mail::queue()` to avoid blocking the HTTP response.

### 19.3 Adding SMS Notifications

Same pattern as email, but:
- Use a dedicated `SmsSender` service.
- The sender service should be injected, not instantiated inline.
- Store phone numbers on `User` model or a related profile model.
- Respect the `notify_by_sms` flag on `ProcedureSettingStep`.

### 19.4 Adding a New Entity That Uses Workflows

Use the centralized Process workflow path:

1. **Create entity model** and register its `processable_type` in the `Process` morph map.
2. **Call `WorkflowEngine::startWorkflow()`** on creation with the same type/form/company/branch/context inputs used by any preview.
3. **Register a `WorkflowNotifier`** for the `processable_type` in the module provider.
4. **On approval/rejection**:
   - Load pending `ProcessStep`.
   - Check `authorized_user_ids`.
   - Call `ProcessWorkflowService::approveStep()` / `rejectStep()` or implement module-specific wrappers like ClientRequest.
   - Apply module-specific terminal status changes.
5. **Create presenter** with action taker fallback logic.
6. **Update repository** inbox queries to check `authorized_user_ids` if the module exposes an inbox.

Do not copy old inline parent/child ProcedureSetting queries into new modules.

Legacy non-Process path:
1. Use `ProcedureWorkflowService` directly.
2. Persist `procedure_setting_id` and `current_procedure_step_id`.
3. **On creation**:
   - Resolve first step via `ProcedureWorkflowService::resolveFirstStep()` or `getApprovalResponsibles()`.
   - Store `current_procedure_step_id` on entity.
   - Resolve and dispatch notifications manually.

---

## 20. File Reference Index

### Models
- `modules/ProcedureSetting/Models/ProcedureSetting.php`
- `modules/ProcedureSetting/Models/ProcedureSettingStep.php`
- `modules/Process/Models/Process.php`
- `modules/Process/Models/ProcessStep.php`
- `modules/ClientRequest/Models/ClientRequest.php`
- `modules/EmployeeTask/Models/EmployeeTaskRequest.php`
- `modules/EmployeeTask/Models/EmployeeTaskExtensionRequest.php`
- `modules/EmployeeTask/Models/EmployeeTaskApprovalRequest.php`
- `modules/User/Models/User.php`
- `modules/UserInfo/UserProfessionalData/Models/UserProfessionalData.php`
- `modules/Company/ManagementHierarchy/Models/ManagementHierarchy.php`
- `modules/Project/ProjectManagement/Models/ProjectManagement.php`

### Services
- `modules/ProcedureSetting/Services/ActionTakerResolver.php`
- `modules/ProcedureSetting/Services/WorkflowEngine.php`
- `modules/ProcedureSetting/Services/ProcedureWorkflowService.php`
- `modules/Process/Services/ProcessWorkflowService.php`
- `modules/Process/Services/WorkflowNotifierRegistry.php`
- `modules/ClientRequest/Services/ClientRequestWorkflowService.php`
- `modules/ClientRequest/Services/ClientRequestWorkflowNotifier.php`
- `modules/EmployeeTask/Services/EmployeeTaskRequestService.php`
- `modules/EmployeeTask/Services/EmployeeTaskWorkflowNotifier.php`
- `modules/EmployeeTask/Services/EmployeeTaskExtensionWorkflowService.php`
- `modules/EmployeeTask/Services/EmployeeTaskExtensionService.php`
- `modules/EmployeeTask/Services/EmployeeTaskApprovalService.php`

### Contracts / DTOs
- `modules/Process/Contracts/WorkflowNotifier.php`
- `modules/ProcedureSetting/DTO/WorkflowStartResult.php`

### Presenters
- `modules/ProcedureSetting/Presenters/ProcedureSettingStepPresenter.php`
- `modules/EmployeeTask/Presenters/EmployeeTaskRequestPresenter.php`
- `modules/EmployeeTask/Presenters/InboxItemPresenter.php`
- `modules/EmployeeTask/Presenters/EmployeeTaskApprovalPresenter.php`
- `modules/EmployeeTask/Presenters/EmployeeTaskExtensionPresenter.php`

### Events
- `modules/EmployeeTask/Events/EmployeeTaskNotification.php`
- `modules/EmployeeTask/Events/InboxCountsUpdated.php`
- `modules/ClientRequest/Events/ClientRequestCreated.php`
- `modules/ClientRequest/Events/ClientRequestStatusChanged.php`

### Repositories
- `modules/EmployeeTask/Repositories/EmployeeTaskRepository.php`

### Requests / DTOs
- `modules/ProcedureSetting/Requests/CreateProcedureSettingStepRequest.php`
- `modules/ProcedureSetting/Requests/UpdateProcedureSettingStepRequest.php`
- `modules/ProcedureSetting/DTO/CreateProcedureSettingStepDTO.php`

### Enums
- `modules/ProcedureSetting/Enums/ActionTakerType.php`
- `modules/ProcedureSetting/Enums/ActionTakerManagementHierarchyType.php`
- `modules/ProcedureSetting/Enums/ActionTakerSpecificProcedureType.php`
- `modules/Process/Enums/ProcessStatus.php`
- `modules/Process/Enums/ProcessStepStatus.php`
- `modules/EmployeeTask/Enums/EmployeeTaskStatus.php`

### Migrations
- `modules/ProcedureSetting/Database/Migrations/2026_06_12_000001_add_action_taker_upgrade_columns_to_procedure_setting_steps.php`
- `modules/Process/Database/Migrations/2026_06_12_000002_add_authorized_user_ids_to_process_steps.php`

---

## 21. Additional AI Traps

### 21.1 The `broadcastOn()` Channel Name Trap

Channels are named `employee-task.notification.{user_id}`. If you create a new event, ensure the channel name is consistent. Frontend listens to these exact names.

### 21.2 The `ShouldBroadcast` vs `ShouldBroadcastNow` Trap

- `ShouldBroadcast` → queued by default (uses Laravel's queue).
- `ShouldBroadcastNow` → synchronous, blocks the HTTP response.

`EmployeeTaskNotification` uses `ShouldBroadcast`. `ClientRequestCreated` and `ClientRequestStatusChanged` use `ShouldBroadcastNow` (synchronous). If you change this, you affect latency.

### 21.3 The `actionTakerUserIds` Null Trap

`resolveActionTakerUserIdsForStep()` returns `[]` if no users can be resolved. If you pass this to a mailer, you will send to NO ONE (safe). But if your code assumes it's non-empty, you may crash.

### 21.4 The `ProcedureSettingStep` vs `ProcessStep` ID Trap

- `ProcedureSettingStep.id` = integer
- `ProcessStep.id` = UUID string
- `ProcessStep.step_id` = integer (FK to `ProcedureSettingStep.id`)

When querying snapshots, compare `snapshotRow['step_id']` to `$processStep->step_id` (both integers).

### 21.5 The `notify_by_email` / `notify_by_sms` Trap

These booleans are read by `SendWorkflowStepNotification` when `WorkflowStepActivated` fires for Process-based workflows. Non-Process workflows must call their manual `dispatchStepNotifications()` path.

### 21.6 The Escalation Timer Trap

`skipping_period` auto-approve is implemented through `AutoApproveWorkflowStep` when `requires_approval_within_period` is true. Broader escalation handoff logic is not implemented.

### 21.7 The `is_view_only` and `is_return_with_notes` Trap

These flags exist on `ProcedureSettingStep` but have no effect on the workflow engine. They are UI hints only.

### 21.8 The `ProcedureWorkflowService` vs `ProcessWorkflowService` Naming Trap

- `ProcedureWorkflowService` = steps through `ProcedureSettingStep` templates (used by EmployeeTask extensions/approvals).
- `ProcessWorkflowService` = steps through `ProcessStep` runtime records (used by ClientRequest and EmployeeTask requests).

Do not confuse them. Extensions and approvals use `ProcedureWorkflowService` because they don't have `Process` records.

### 21.9 The `userCanActOnStep` vs Authorization Check Trap

`ProcedureWorkflowService::userCanActOnStep()` is a **read-only check** for inbox filtering. It does NOT enforce authorization. The actual enforcement is in `assertIsActionTaker()` (throws exception) or `ProcessWorkflowService::approveStep()` (403 abort).

### 21.10 The Polymorphic Processable Trap

`Process` uses polymorphic relations (`processable_type`, `processable_id`). The type strings must match exactly:
- `employee_task` (was `employee_task_request` before June 2026 refactor)
- `client_request`

The morph map in `Process::boot()` registers `employee_task` → `EmployeeTaskRequest::class`.

If you create a new entity using workflows, you MUST register its type string consistently everywhere. The `employee_task_request` string is DEPRECATED — use `employee_task` for all new code.

### 21.11 The `advance()` Result Trap

`ProcedureWorkflowService::advance()` returns a `ProcedureWorkflowResult`:
```php
class ProcedureWorkflowResult
{
    public function __construct(
        public ProcedureSettingStep $currentStep,
        public ?ProcedureSettingStep $nextStep,
        public bool $isFinal,
    ) {}
}
```

Callers check `$result->isFinal` to decide whether to apply terminal business logic (e.g., mark task as approved). If you ignore `isFinal`, the workflow will not complete properly.

### 21.12 The `getApprovalResponsibles` Preview Trap

`ProcedureWorkflowService::getApprovalResponsibles()` is called BEFORE the entity is created to show the user who will approve. It delegates to `WorkflowEngine::previewResponsibles()` so preview uses the same company/branch/default-workflow resolution as creation. The return shape is:
```php
[
    'auto_approve' => bool,
    'step' => ['id' => int, 'name' => ?string, 'step_order' => int],
    'action_takers' => [['user_id' => string, 'name' => ?string], ...]
]
```

If `auto_approve` is true, the entity should be created in `approved` status directly.

---

## 22. Testing Checklist for AI

When modifying workflow code, verify these scenarios:

1. **Specific user step** — One user acts, step advances.
2. **Management hierarchy step** — Creator is in branch A. Step resolves to branch A manager. Manager approves.
3. **Project manager step** — Task has `project_id`. Step resolves to project manager. Manager approves.
4. **Project manager fallback** — Task has NO `project_id`. Step falls back to alternative hierarchy.
5. **Specific procedures / branch** — Resolves to specific branch manager.
6. **Specific procedures / job_title** — Multiple users have the job title. ALL see the task in inbox. ANY ONE can approve.
7. **Specific procedures / job_role (id=1)** — All management managers are authorized. Rejection ADVANCES the workflow.
8. **Specific procedures / job_role (id=2)** — All branch managers are authorized. Rejection ADVANCES the workflow.
9. **Sequence workflow** — Steps run one at a time. Next step created only after previous acted on.
10. **Parallel workflow** — All steps created upfront. Process completes when all acted on.
11. **Notification broadcast** — All authorized users receive real-time notification.
12. **Inbox query** — All authorized users see the task in their inbox.
13. **Presenter display** — Action takers shown correctly for all types.
14. **Validation** — Alternative hierarchy type cannot equal primary type.

---

## 23. Refactor Change Log (What Was Added in This Session)

If you are a future AI reading this, these are the changes made to the codebase in this refactor session. Legacy code did NOT have these features.

### New Enums
- `ActionTakerSpecificProcedureType` — NEW enum created (`branch`, `management`, `job_title`, `job_role`).
- `ActionTakerType` — ADDED `SpecificProcedures` case.
- `ActionTakerManagementHierarchyType` — ADDED `ProjectManager` case.

### New Database Columns
- `procedure_setting_steps.action_taker_alternative_management_hierarchy_type`
- `procedure_setting_steps.action_taker_specific_procedure_type`
- `procedure_setting_steps.action_taker_specific_procedure_id`
- `process_steps.authorized_user_ids` (JSON)

### New Service
- `ActionTakerResolver` — NEW service. Before, resolution logic was scattered inline in `ClientRequestWorkflowService::resolveAssignedUserId()` and `resolveManagerFromCreatorHierarchy()`. Now centralized.
- `WorkflowEngine` — Central service for resolving parent/child settings, previewing responsibles, and starting Process-based workflows.
- `WorkflowNotifierRegistry` — Registry that maps `processable_type` to module-level `WorkflowNotifier` implementations.

### Refactored Services
- `ProcessWorkflowService` — Refactored to use `ActionTakerResolver`. Added `context` parameter. Stores `authorized_user_ids` and `specific_procedure_type` in snapshots. `approveStep`/`rejectStep` now check `authorized_user_ids`; `rejectStep` compares enum-to-enum; `createProcessStep()` fires `WorkflowStepActivated` with context.
- `ClientRequestWorkflowService` — Creation now calls `WorkflowEngine::startWorkflow()`. Approval/rejection and ClientRequest-specific status transitions remain in this service.
- `ProcedureWorkflowService` — Added `resolveActionTakerUserIdsForStep()` and delegates preview/resolution to `WorkflowEngine`.

### EmployeeTask Services Updated
- `EmployeeTaskRequestService` — Now passes `project_id` context, previews and starts task creation via `WorkflowEngine`, uses `findPendingStepForActor()` (checks `authorized_user_ids`), and no longer manually broadcasts in the create path.
- `EmployeeTaskExtensionWorkflowService` — Now passes `project_id` context to `advance()` and `assertCanReject()`.
- `EmployeeTaskExtensionService` — Now resolves users with context, broadcasts to resolved IDs.
- `EmployeeTaskApprovalService` — Now resolves users with context, broadcasts to resolved IDs.

### Notification Broadcasting Fix (CRITICAL BUG FIX)
**Before**: Broadcasters loaded `actionTakers` from template step. For `management_hierarchy` and `specific_procedures`, `actionTakers` was EMPTY → **NO ONE received notifications**.

**After**: Process-based creation paths store actual user IDs in `authorized_user_ids` and fire `WorkflowStepActivated`. Non-Process paths resolve user IDs via `resolveActionTakerUserIdsForStep()` and pass them explicitly to `EmployeeTaskNotification($task, $step, $userIds)`.

### Presenters Updated
- `ProcedureSettingStepPresenter` — Added labels for new fields.
- `EmployeeTaskRequestPresenter` — Falls back to process step `authorized_user_ids`.
- `InboxItemPresenter` — `stepFromProcess` reads `authorized_user_ids`. `step` falls back to `task.approval_responsible_id`.
- `EmployeeTaskApprovalPresenter` — Same fallback logic.

### Inbox Queries Updated
- `EmployeeTaskRepository::paginateInboxForAdmin()` — Added `orWhereJsonContains('authorized_user_ids', $adminId)`.
- `EmployeeTaskRepository::allInboxForAdmin()` — Same JSON column check.

### Validation Updated
- `CreateProcedureSettingStepRequest` — Added rules for new fields with `different` enforcement.
- `UpdateProcedureSettingStepRequest` — Same rules.
- `CreateProcedureSettingStepDTO` — Added new properties.

### June 2026 — Internal Procedure Settings Refactor (NEW)

#### Architecture Change
- `ProcedureSetting` is now a **self-referencing table** with `parent_id`.
- **Parent rows**: `parent_id = NULL`, `type` = category (`employee_task`, `client_request`, etc.)
- **Child rows**: `parent_id = parent.id`, `form` = action key (`startTask`, `extendTaskTime`, etc.)
- Each child has its own `name`, `steps`, `conditions`, `appears_before_ids` (JSON array), `appears_after_ids` (JSON array), `sort_order`

#### Enum Changes
- `ProcedureSettingType` simplified to categories only (`employee_task`, `client_request`, `price_offer`, `contract`, `meeting`)
- Removed: `EmployeeTaskRequest`, `EmployeeTaskExtension`, `EmployeeTaskCompletionApproval` cases
- `InternalProcessForm` enum added under `Modules\Shared\InternalProcessType\Enums` with `applicableTypes()`, `forType()`, `labelAr()`, `conditions()`, `toDefinition()`, and `values()`
- `InternalProcessCondition` enum added for per-form condition definitions

#### Database Changes
- Added to `procedure_settings`: `parent_id` (UUID, nullable, FK to self), `form` (string, nullable), `conditions` (JSON, nullable), `appears_before_id` (JSON array, nullable), `appears_after_id` (JSON array, nullable)
- Migration `2026_06_21_000001`: changed `appears_before_id` / `appears_after_id` from single `uuid` to `json` arrays; dropped FK constraints; auto-converts existing single-UUID rows
- Dropped `internal_process_types` table
- Removed `employee_task_requests.internal_process_type_id` column

#### New APIs
- `GET /employee-tasks/{id}/available-actions` — Returns child internal procedures for mobile
- `GET/POST/PUT/DELETE /procedure-settings/{id}/internal-procedures` — Admin CRUD for children
- `GET /procedure-settings/{id}/available-forms` — Returns form definitions for admin UI

#### Updated APIs
- `GET /procedure-settings/approval-responsibles` — Accepts `type` (category), optional `form`, and optional `branch_id`
- `POST /employee-tasks/{id}/request-approval` — Now accepts optional `internal_procedure_setting_id`
- `POST /employee-tasks/{id}/extension-requests` — Now accepts optional `internal_procedure_setting_id`

#### Service Changes
- `ProcedureWorkflowService::resolveInternalProcedureSettingByForm()` — Resolves child by category + form key
- `EmployeeTaskExtensionService::loadInternalProcedureSetting()` — Loads specific child by ID, verifies parent belongs to task's company/category
- `EmployeeTaskApprovalService::loadInternalProcedureSetting()` — Same
- `EmployeeTaskAvailableActionsService::forTask()` — Returns all active children with IDs and form details

#### Polymorphic Type Change
- `Process.processable_type` changed from `employee_task_request` → `employee_task`
- Updated in `Process::boot()` morph map, `EmployeeTaskRequest` model, presenter, listener, and seeder

#### Removed
- `InternalProcessType` module (standalone table, model, seeder, API)
- `employee_task_requests.internal_process_type_id` column and all references

---

### June 2026 — Centralized Taken-Procedure Tracking

#### Problem
Taken status was stored as a JSON array (`taken_internal_procedure_ids`) on `employee_task_requests`, with logic scattered across services inferring taken state from sessions, `location_confirmed_at`, and approved request records.

#### Solution: `internal_procedure_takens` Morph Table

A dedicated table in the `ProcedureSetting` module centralizes tracking for any entity type:

```
internal_procedure_takens
  id                   UUID PK
  company_id           UUID  (tenant-scoped)
  processable_type     string  (e.g. 'employee_task')
  processable_id       UUID
  procedure_setting_id UUID  FK → procedure_settings (cascade delete)
  form                 string nullable  (denormalized for fast reads)
  taken_by             UUID nullable
  taken_at             timestamp
  UNIQUE (processable_type, processable_id, procedure_setting_id)
```

**Model**: `Modules\ProcedureSetting\Models\InternalProcedureTaken` — `MorphTo processable`, `BelongsTo procedureSetting`, tenant-scoped via `BelongsToTenant`.

#### New Methods on `ProcedureWorkflowService`

| Method | Behaviour |
|--------|-----------|
| `markProcedureTaken($type, $id, $settingId, $takenBy)` | Idempotent `firstOrCreate`; resolves `form` from setting |
| `getTakenProcedureIds($type, $id)` | Returns `list<string>` of all taken setting IDs |
| `isProcedureTaken($type, $id, $settingId)` | Boolean point-check |
| `advance(..., $processableType, $processableId)` | Extended signature — auto-calls `markProcedureTaken()` when `isFinal = true` |

#### How Each Form Gets Taken

| Form | Mechanism |
|------|-----------|
| `createTask` | **Auto-approve**: `markCreateTaskProceduresTaken()` called immediately (preview says no steps, or runtime resolves to empty users). **With approvers**: `ProcessWorkflowService::fireProcedureTakenIfApplicable()` fires `WorkflowProcedureTaken` when `Process.status` → `Completed` |
| `startTask` | `EmployeeTaskController::start()` calls `markProcedureTaken()` with `internal_procedure_setting_id` |
| `confirmLocation` | `EmployeeTaskController::locationPing()` calls `markProcedureTaken()` on first in-location confirmation |
| `endTask` (direct) | `EmployeeTaskLifecycleService::end()` calls `markProcedureTaken()` |
| `endTask` (via request) | `advance()` auto-marks on final step of end-request workflow |
| `extendTaskTime` | `advance()` auto-marks on final step of extension workflow |
| `sendForApproval` | `advance()` auto-marks on final step of approval workflow |
| Any (auto-approve) | Caller explicitly calls `markProcedureTaken()` when skipping workflow |

#### New Migrations
- `ProcedureSetting/Migrations/2026_06_19_000002_create_internal_procedure_takens_table.php`
- `EmployeeTask/Migrations/2026_06_19_000003_drop_taken_internal_procedure_ids_from_employee_task_requests.php`
- `Process/Migrations/2026_06_23_000001_add_procedure_setting_id_to_processes.php` — adds nullable `procedure_setting_id` UUID column to `processes`. Populated only for child/internal procedure settings (those with `form != null`). When the process reaches `Completed`, `ProcessWorkflowService::fireProcedureTakenIfApplicable()` fires `WorkflowProcedureTaken` for this setting ID.

#### `processes.procedure_setting_id` Rules
- `null` for parent-level (ClientRequest, PriceOffer) processes — no event fires.
- Set to `$setting->id` for any child `ProcedureSetting` where `form !== null` (e.g. `createTask`, `endTask`).
- On process `Completed`, `WorkflowProcedureTaken` is dispatched automatically — **no manual `markProcedureTaken()` call needed by callers**.
- Idempotent: `RecordInternalProcedureTaken` uses `firstOrCreate`, so double-firing is harmless.

#### Removed
- `employee_task_requests.taken_internal_procedure_ids` JSON column
- `EmployeeTaskRequest::markInternalProcedureTaken()`, `isInternalProcedureTaken()`, `takenInternalProcedureIds()`
- `EmployeeTaskAvailableActionsService::resolveTakenProcedureIds()` — 6 separate DB queries replaced by one `getTakenProcedureIds()` call

---

## 24. Data Flow Diagrams

### 24.1 Task Creation (EmployeeTaskRequest)

```
EmployeeTaskRequestController::store()
  ↓
EmployeeTaskRequestService::create(CreateEmployeeTaskRequestDTO)
  ├─→ builds context = ['project_id' => $dto->projectId] (if set)
  ├─→ resolves creator branch from userProfessionalData.branch_id
  ├─→ calls WorkflowEngine::previewResponsibles('employee_task', 'createTask', companyId, branchId, userId, context)
  │     ├─→ resolves parent by branch/default workflow
  │     ├─→ resolves child form createTask
  │     └─→ returns preview with action_takers
  ├─→ creates EmployeeTaskRequest record
  ├─→ markCreateTaskProceduresTaken(task, userId)            ← NEW: central taken tracking
  │     ├─→ resolveParentSetting() → parent ProcedureSetting for company/branch
  │     ├─→ query: all active createTask-form children under parent
  │     └─→ markProcedureTaken('employee_task', task->id, settingId, userId) for each
  ├─→ WorkflowEngine::startWorkflow('employee_task', task->id, 'employee_task', 'createTask', companyId, branchId, userId, context)
  │  └─→ ProcessWorkflowService::createProcessesFromSettings(...)
  │     ├─→ for each step:
  │     │     ├─→ ActionTakerResolver::resolveUsersForStep(step, userId, context)
  │     │     ├─→ if resolvedUsers === []: SKIP step
  │     │     └─→ snapshot[] = {step_id, assigned_user_id: resolvedUsers[0], authorized_user_ids: resolvedUsers, ...}
  │     ├─→ creates Process with template_snapshot
  │     └─→ creates first ProcessStep from snapshot[0]
  ├─→ ProcessWorkflowService::getCurrentStep(process) → ProcessStep
  ├─→ updates task: approval_responsible_id = currentStep->assigned_user_id, current_procedure_step_id = currentStep->step_id
  └─→ notifications fire centrally through WorkflowStepActivated → EmployeeTaskWorkflowNotifier
```

### 24.2 Task Approval (EmployeeTaskRequest)

```
EmployeeTaskRequestController::approve($id)
  ↓
EmployeeTaskRequestService::approve($id, $adminId)
  ├─→ finds task by id
  ├─→ loads task->processes (in_progress)
  ├─→ findPendingStepForActor(process, $adminId)
  │     ├─→ gets all pending ProcessSteps for process
  │     ├─→ for each step: reads step->authorized_user_ids ?? snapshot fallback
  │     └─→ returns first step where $adminId is in authorized list
  ├─→ if no step found → throw notFound()
  ├─→ builds context = ['project_id' => task->project_id]
  ├─→ ProcedureWorkflowService::advance(currentStepId, procedureSettingId, $adminId, task->user_id, context)
  │     ├─→ loads ProcedureSettingStep
  │     ├─→ assertIsActionTaker(step, $adminId, task->user_id, context)
  │     │     └─→ ActionTakerResolver::resolveUsersForStep(step, task->user_id, context)
  │     │     └─→ checks $adminId in resolved list → 403 if not
  │     ├─→ finds next ProcedureSettingStep by step_order > current
  │     └─→ returns ProcedureWorkflowResult(currentStep, nextStep, isFinal)
  ├─→ if result->isFinal:
  │     └─→ update task status = approved
  │     └─→ update task approved_at = now()
  ├─→ if !result->isFinal:
  │     └─→ update task current_procedure_step_id = nextStep->id
  │     └─→ update task approval_responsible_id = nextStep resolved user
  └─→ returns updated task
```

### 24.3 Notification Broadcast (Fixed Flow)

```
Before Fix (BROKEN):
  broadcastTaskNotification(task, currentStep)
    → currentStep->load('actionTakers')
    → event(new EmployeeTaskNotification(task, currentStep))
    → broadcastOn(): channels from currentStep->actionTakers
    → For management_hierarchy: actionTakers is EMPTY
    → Result: NO channels created → NO ONE receives notification

After Fix (WORKING):
  broadcastTaskNotification(task, currentStep, userIds = [resolved IDs])
    → event(new EmployeeTaskNotification(task, currentStep, userIds))
    → broadcastOn(): uses provided $userIds
    → channels = [employee-task.notification.{id} for each id]
    → Result: ALL authorized users receive notification
```

---

## 25. Glossary

| Term | Definition |
|------|------------|
| **Action Taker** | The user(s) authorized to approve or reject a workflow step. |
| **ProcedureSetting** | A static template defining a multi-step approval workflow. |
| **ProcedureSettingStep** | A single node in a ProcedureSetting. Configures action takers, timers, notifications. |
| **Process** | A runtime instance of a ProcedureSetting, created when an entity enters the workflow. |
| **ProcessStep** | A mutable runtime node representing the current state of one step. |
| **Template Snapshot** | JSON stored on Process that freezes the ProcedureSettingStep configuration at creation time. |
| **Context** | Associative array (usually `['project_id' => ...]`) passed to resolution methods for conditional logic. |
| **Authorized User IDs** | The complete list of users who can act on a step (stored in DB and snapshot). |
| **Assigned User ID** | The primary user (first in authorized list). Used as fallback and for legacy compatibility. |
| **Specific User** | Action taker type where users are explicitly listed on the step. |
| **Management Hierarchy** | Action taker type where the assigned user is resolved from the creator's org chart. |
| **Specific Procedures** | Action taker type where users are resolved dynamically by branch, management, job title, or job role. |
| **Job Role** | A specific procedure sub-type: `1` = all management managers, `2` = all branch managers. |
| **Alternative Hierarchy** | Fallback management hierarchy type used when the primary cannot be resolved. |
| **Sequence** | Execute type: steps run one at a time in order. |
| **Parallel** | Execute type: all steps are active simultaneously. |
| **Processable** | The entity (ClientRequest, EmployeeTaskRequest) that owns the Process. |
| **Escalation** | Timer-based handoff to a higher authority if a step is not acted on in time. |
| **Inbox** | The admin dashboard showing pending items the current user can act on. |
| **Real-Time Notification** | WebSocket broadcast via Laravel Echo/Pusher. |
| **Approval Responsible** | Legacy field on EmployeeTaskRequest storing the first step's assigned user. **Do not use for authorization.** |
| **notify_by_sms** | New flag on ProcedureSettingStep. If true, sends SMS to action takers. |
| **skipping_period** | New field on ProcedureSettingStep (hours). Auto-approves step after N hours if `requires_approval_within_period` is true. |
| **WorkflowStepActivated** | Central event fired when a ProcessStep becomes active. Listener handles ALL notification channels. |
| **WorkflowActionRequired** | Laravel Notification class supporting mail + SMS channels. |
| **AutoApproveWorkflowStep** | Queued job that auto-approves a step after the skipping_period delay. |
| **Internal Procedure Setting** | Child row under a parent `ProcedureSetting` with a `form` key. Has its own steps, conditions, and ordering. |
| **Parent Procedure Setting** | Category-level `ProcedureSetting` (`parent_id = NULL`). Groups related internal procedures. |
| **Form Key** | Action identifier on a child: `startTask`, `extendTaskTime`, `sendForApproval`, etc. Defined in `InternalProcessForm` enum. |
| **Conditions** | JSON array of `InternalProcessCondition` values on a child. UI/UX hints for the mobile app. |
| **appears_before_ids** | Ordering constraint (JSON array): this child is hidden once ANY referenced procedure is taken. Empty array = no constraint. |
| **appears_after_ids** | Ordering constraint (JSON array): this child is visible only when ALL referenced procedures are taken. Empty array = always visible. |
| **InternalProcessForm** | Enum defining valid form keys per category. Has `applicableTypes()` and `forType()` methods. |
| **InternalProcessCondition** | Enum defining valid condition keys (e.g., `AllowDuringShift`, `ApplyToAllBranches`). |

---

## 26. New Features Added (This Session)

### 26.1 notify_by_sms
- Added to `procedure_setting_steps` as boolean column.
- Validated in `CreateProcedureSettingStepRequest` and `UpdateProcedureSettingStepRequest`.
- Presented in `ProcedureSettingStepPresenter`.
- Dispatched via `WorkflowActionRequired` notification's `toSms()` method.

### 26.2 skipping_period
- Added to `procedure_setting_steps` as nullable integer (hours).
- When `requires_approval_within_period = true` AND `skipping_period > 0`:
  - `ProcessWorkflowService::createProcessStep()` schedules `AutoApproveWorkflowStep` job with a delay of `skipping_period` hours.
  - The job checks if the step is still pending. If yes, it calls `autoApproveStep()` which marks it as approved without requiring an actor.
  - The workflow then advances to the next step (or completes if final).
- If the step is already acted on before the delay expires, the job silently skips.

### 26.3 Centralized Notification Architecture (Event + Listener)

**Problem**: Notifications were scattered across services. Each service manually broadcast real-time events and had no email/SMS support.

**Solution**: `WorkflowStepActivated` event + `SendWorkflowStepNotification` listener.

#### Event: `WorkflowStepActivated`

**File**: `modules/ProcedureSetting/Events/WorkflowStepActivated.php`

```php
new WorkflowStepActivated(
    processStep: $processStep,      // The newly created ProcessStep
    templateStep: $templateStep,    // The ProcedureSettingStep config
    userIds: $authorizedUserIds,     // Resolved action taker IDs
    context: [],                    // Optional context (e.g., project_id)
)
```

Fired from:
- `ProcessWorkflowService::createProcessStep()` — for ALL new ProcessSteps (initial creation + step advance)

#### Listener: `SendWorkflowStepNotification`

**File**: `modules/ProcedureSetting/Listeners/SendWorkflowStepNotification.php`

Handles three channels:
1. **Real-time broadcast** — Always fires. Sends `EmployeeTaskNotification` + `InboxCountsUpdated` to all authorized users.
2. **Email** — Only if `templateStep->notify_by_email` is true. Sends `WorkflowActionRequired` notification via `toMail()`.
3. **SMS** — Only if `templateStep->notify_by_sms` is true. Sends `WorkflowActionRequired` notification via `toSms()`.

Registered in `ProcedureSettingServiceProvider::registerEventListeners()`:
```php
Event::listen(
    WorkflowStepActivated::class,
    SendWorkflowStepNotification::class,
);
```

#### Notification: `WorkflowActionRequired`

**File**: `modules/ProcedureSetting/Notifications/WorkflowActionRequired.php`

Follows the `SendOtpForLogin` pattern:
- `via($notifiable)` — returns channels array (e.g., `['mail', 'sms']`)
- `toMail($notifiable)` — returns `MailMessage` with blade template `emails.workflowActionRequired`
- `toSms($notifiable)` — returns SMS via `MoraSms` driver (or country-specific driver)

The `processStep` parameter is nullable to support non-Process workflows (extensions/approvals).

### 26.4 Email Template

**File**: `resources/views/emails/workflowActionRequired.blade.php`

Styled like existing project emails (`loginWithOtp.blade.php`). Supports RTL/LTR based on app locale.

### 26.5 Non-Process Workflows (Extensions + Approvals)

Extensions and approvals do NOT create `Process`/`ProcessStep` records. They cannot use the `WorkflowStepActivated` event.

Instead, `EmployeeTaskExtensionService::create()` and `EmployeeTaskApprovalService::create()` call `dispatchStepNotifications()` directly:
- Resolves users via `ProcedureWorkflowService::resolveActionTakerUserIdsForStep()`
- Checks `notify_by_email` / `notify_by_sms` flags on the `ProcedureSettingStep`
- Sends `WorkflowActionRequired` notification to each user
- Also broadcasts real-time events manually

### 26.6 Changes to EmployeeTaskRequestService::create()

**Before**: Manually created a dummy `ProcedureSettingStep`, broadcast real-time events, and called `broadcastInboxCounts()`.

**After**: Removed manual broadcast. All notifications are now dispatched centrally via `WorkflowStepActivated` event fired inside `ProcessWorkflowService::createProcessStep()`.

### 26.7 File Reference Index (New Files)

| File | Purpose |
|------|---------|
| `modules/ProcedureSetting/Database/Migrations/2026_06_12_100003_add_notify_by_sms_and_skipping_period_to_procedure_setting_steps.php` | Migration for new columns |
| `modules/ProcedureSetting/Events/WorkflowStepActivated.php` | Event fired when step becomes active |
| `modules/ProcedureSetting/Notifications/WorkflowActionRequired.php` | Mail + SMS notification |
| `modules/ProcedureSetting/Listeners/SendWorkflowStepNotification.php` | Central listener for all channels |
| `modules/ProcedureSetting/Jobs/AutoApproveWorkflowStep.php` | Delayed job for skipping_period auto-approve |
| `resources/views/emails/workflowActionRequired.blade.php` | Email blade template |

---

## 27. Internal Procedure Settings (Self-Referencing ProcedureSetting)

> Added: June 2026
> Architecture change from standalone `ProcedureSetting` types to a self-referencing parent/child model.

### 27.1 The Problem

Before, `ProcedureSettingType` had separate cases for every workflow variant:
- `EmployeeTaskRequest` — task creation
- `EmployeeTaskExtension` — extension request
- `EmployeeTaskCompletionApproval` — completion approval

Each was a **standalone** `ProcedureSetting` row with its own steps. This made it impossible to:
- Have multiple workflows with the same action type (e.g., two "confirm location" forms)
- Group related workflows under a single category
- Share category-level configuration

### 27.2 The Solution

`ProcedureSetting` is now a **self-referencing table** with 3 levels:

#### Level 1 — Parent Category (`parent_id = NULL`)
The category header. Created via `POST /procedure-settings`. Does NOT have a `form`.

| Field | Meaning |
|-------|---------|
| `type` | Category: `employee_task`, `client_request`, `price_offer`, `contract`, `meeting` |
| `company_id` | The company this category belongs to |
| `name` | Display name (e.g., "إجراءات مهام العمال") |
| `execute_type` | `sequence` or `parallel` |
| `is_internal_procedure` | `false` (always, for parents) |

**How to get parent by type:**
```
GET /procedure-settings?type=employee_task
```
Returns WorkFlow → `procedure-settings` array contains ONLY parents (`parent_id = NULL`). Children are excluded.

**How to get children of a parent under a specific workflow:**
```
GET /procedure-settings?type=employee_task&parent_id=<parent_uuid>
```
Returns the **default** workflow for that type. `procedure-settings` contains only children of `parent_id` whose `work_flow_id` matches the default workflow. If the child was created for a branch-specific workflow it will NOT appear here — only children belonging to the default workflow are returned.

```
GET /procedure-settings?type=employee_task&parent_id=<parent_uuid>&branch_id=9
```
Returns the workflow for branch 9. `procedure-settings` contains only children of `parent_id` whose `work_flow_id` matches the branch workflow.

> **CRITICAL — work_flow_id on children**: A child's `work_flow_id` must match the workflow you are querying. The backend filters children per-workflow in the eager load (`WHERE work_flow_id = <workflow_id> AND parent_id = <parent_id>`). A child created with `branch_id=9` will ONLY appear under the branch-9 workflow query, never under the default workflow query.

#### Controller routing table for `GET /procedure-settings`:

| Filters sent | Branch used | Returns |
|---|---|---|
| _(none)_ | `getDefaultWorkFlowForList()` | Default `client_request` workflow, root PS only |
| `type` + `parent_id` | `listByWorkFlow(filters)` → first `name=default` | Single workflow, children of `parent_id` scoped to that workflow |
| `type` + `parent_id` + `branch_id` | `firstByWorkFlowFilters(filters)` | Single branch workflow, children of `parent_id` scoped to that workflow |
| `type` only | `getDefaultWorkFlowByType(type)` | Default workflow for type, root PS only |
| `branch_id` only | `firstByWorkFlowFilters(filters)` | Single branch workflow, root PS only |
| `work_flow_id` | `listByWorkFlow(filters)` | All workflows (list), root PS only |

#### Level 2 — Internal Procedure (`parent_id = parent.id`)
The actionable form. Created via `POST /procedure-settings/{parent_id}/internal-procedures`. MUST have a `form`.

| Field | Meaning |
|-------|---------|
| `parent_id` | FK to the parent category row |
| `form` | Action key: `startTask`, `extendTaskTime`, `sendForApproval`, `cancelTask`, `confirmLocation`, `assignOtherEmployee`, `attachAttachments` |
| `name` | Display name (e.g., "تأكيد دخول الموقع") |
| `conditions` | JSON array of `InternalProcessCondition` values |
| `appears_before_ids` | JSON array of UUIDs — hide this child once ANY referenced procedure is taken |
| `appears_after_ids` | JSON array of UUIDs — show this child only when ALL referenced procedures are taken |
| `sort_order` | Display order |
| `is_active` | Whether this child is enabled |
| `is_internal_procedure` | `true` (always, for children) |

**How to get children:**
```
GET /procedure-settings/{parent_id}/internal-procedures
```

#### Level 3 — Steps
Steps belong to EITHER a parent (for Process workflows like task creation) OR a child (for non-Process workflows like extensions). Steps do NOT have a `form`.

```
GET /procedure-settings/{id}/steps        ← Parent or Child steps
```

**UI Note:** The UI may show step configuration grouped into sections like "الموافقة" (Approval) or "الاعتماد" (Endorsement). These are **UI groupings only** — they are NOT separate database tables. In the database, these are just columns on `procedure_setting_steps` (e.g., `action_taker_type`, `is_approve`, `action_taker`).

### 27.3 Model Relations

```php
class ProcedureSetting extends Model
{
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProcedureSetting::class, 'parent_id');
    }

    public function internalProcedures(): HasMany
    {
        return $this->hasMany(ProcedureSetting::class, 'parent_id')
            ->whereNotNull('form')
            ->orderBy('sort_order');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ProcedureSettingStep::class)
            ->orderBy('step_order');
    }

    public function isInternalProcedure(): bool
    {
        return $this->parent_id !== null && $this->form !== null;
    }
}
```

### 27.4 Resolution Methods

#### By Form Key (Fallback)

```php
ProcedureWorkflowService::resolveInternalProcedureSettingByForm(
    string $procedureCategoryType,  // 'employee_task'
    string $formKey,               // 'extendTaskTime'
    string $companyId,
    ?string $branchId = null,
): ?ProcedureSetting
```

Returns the **first** child matching the form key. Works when there's only one child per form.

#### By Explicit ID (Precise)

```php
// In EmployeeTaskExtensionService / EmployeeTaskApprovalService
private function loadInternalProcedureSetting(string $id, EmployeeTaskRequest $task): ?ProcedureSetting
{
    return ProcedureSetting::query()
        ->where('id', $id)
        ->whereNotNull('form')
        ->whereHas('parent', function ($q) use ($task) {
            $q->where('type', ProcedureSettingType::EmployeeTask->value)
              ->where('company_id', $task->company_id);
        })
        ->with(['steps' => fn ($q) => $q->orderBy('step_order')])
        ->first();
}
```

**Security check**: Verifies the child belongs to the task's company and category.

### 27.5 Duplicate Forms Support

A parent can have multiple children with the same `form`:

```
Parent (employee_task)
├── Child A: form = "confirmLocation", name = "تأكيد دخول الموقع"
├── Child B: form = "extendTaskTime", name = "تمديد وقت المهمة"
└── Child C: form = "confirmLocation", name = "تأكيد خروج الموقع"
```

**Mobile Flow**:
```
1. GET /employee-tasks/{id}/available-actions
   → Returns [Child A, Child B, Child C] with unique IDs

2. User taps "تأكيد خروج الموقع" (Child C)
   → App stores: internal_procedure_setting_id = "uuid-of-child-c"

3. POST /employee-tasks/{id}/request-approval
   body: { internal_procedure_setting_id: "uuid-of-child-c", ... }
   → Backend loads Child C explicitly by ID
   → Uses Child C's specific steps and conditions
```

### 27.6 Admin CRUD API

**URL Parameter `procedure_setting_id`:** This is the **PARENT** category ProcedureSetting UUID (the row with `parent_id = NULL`, `type = 'employee_task'`). It is NOT the `type` string itself.

**`internal_procedure_setting_id`:** This is the **CHILD** UUID, auto-generated by the server. You send it in the URL for Update/Delete. You do NOT send it when Creating — the server returns it in the response.

#### List Children
```
GET /procedure-settings/{parent_procedure_setting_uuid}/internal-procedures
```

#### Create Child
```
POST /procedure-settings/{parent_procedure_setting_uuid}/internal-procedures
body: {
  "name": "بدء مهمة العمل",
  "form": "startTask",
  "conditions": [],
  "appears_before_ids": [],
  "appears_after_ids": [],
  "sort_order": 1,
  "is_active": true
}

Response: { "id": "auto-generated-child-uuid", ... }
```

- **`name`**: Display name (e.g., "بدء مهمة العمل")
- **`form`**: Must be a valid `InternalProcessForm` case (e.g., `startTask`)
- **`conditions`**: JSON array of condition objects
- **`appears_before_ids`** / **`appears_after_ids`**: Ordering constraints — arrays of procedure-setting UUIDs (optional, default `[]`)
- **`sort_order`**: Display priority (optional, fallback)
- **`is_active`**: Boolean (optional, default true)

**Note:** You do NOT send `internal_procedure_setting_id` in the body. The server generates it.

#### Update Child
```
PUT /procedure-settings/{parent_uuid}/internal-procedures/{child_uuid}
```

#### Delete Child
```
DELETE /procedure-settings/{parent_uuid}/internal-procedures/{child_uuid}
```

#### Get Child by Form Key
```
GET /procedure-settings/{parent_uuid}/internal-procedures/by-form/{form_key}
```

Returns the **first** child matching the form key under the parent. Useful for quick lookups when you know there's only one child per form.

**Example:**
```
GET /procedure-settings/{parent_uuid}/internal-procedures/by-form/startTask
```

**Warning:** If multiple children share the same form, this returns only the first match. For precise selection, use the child UUID directly.

#### Get Form Definitions (for Admin UI)
```
GET /procedure-settings/{procedure_setting_id}/available-forms
```
Returns all `InternalProcessForm` values applicable to the parent's category, with their condition schemas.

### 27.7 Seeding

`InternalProcedureSettingsSeeder` auto-creates default children under each parent category.

**Seeding rule:** any `InternalProcessForm` case whose value starts with `create` **or** `end` is seeded automatically. This covers:

| Seeded form | Procedure category |
|-------------|-------------------|
| `createClientRequest` | `client_request` |
| `createPriceOffer` | `price_offer` |
| `createContract` | `contract` |
| `createMeeting` | `meeting` |
| `createTask` | `employee_task` |
| `endTask` | `employee_task` |
| `endClientRequest` | `client_request` |
| `endPriceOffer` | `price_offer` |
| `endContract` | `contract` |
| `endMeeting` | `meeting` |

Forms that are **not** seeded automatically (and must be created via API if needed):
- `startTask`, `attachAttachments`, `extendTaskTime` (string-only), `sendForApproval` (string-only)

**To add a new auto-seeded form:** add an `InternalProcessForm` case whose value starts with `create` or `end` — the seeder picks it up on next run without code changes.

### 27.8 Conditions Schema

The `conditions` column on a child `ProcedureSetting` is a JSON **object** (not an array) keyed by the snake_case value of `InternalProcessCondition`:

```json
{
  "allow_during_shift": true,
  "allow_outside_shift": false,
  "allow_on_holidays": false
}
```

```json
{
  "can_exit_outside_location": false
}
```

```json
{
  "max_attachments": 5
}
```

The key is `InternalProcessCondition->value`. The value type depends on the condition:
- **Boolean** conditions (`AllowDuringShift`, `AllowOutsideShift`, `AllowOnHolidays`, `CanExitOutsideLocation`, `HasTaskDuration`): `true` / `false`
- **Integer** conditions (`MaxDurationHours`, `MaxAttachments`): integer

`InternalProcessCondition::defaultValuesForForm($form)` returns the default object for a given form:
- `createTask` → `{"allow_during_shift": true, "allow_outside_shift": false, "allow_on_holidays": false}`
- `endTask` → `{"can_exit_outside_location": true}`
- `attachAttachments` → `{"max_attachments": <default>}`
- all others → `{}`

**Backend enforcement:** conditions for `createTask` and `endTask` are enforced at runtime by `EmployeeTaskFormConditionService` (see §28). Other conditions are returned to the client for UI enforcement.

> **Trap:** The historic documentation described conditions as `[{"key": "...", "value": ...}]` array format. This was incorrect. The actual stored format is a flat JSON object as shown above.

### 27.9 Ordering Constraints

`appears_before_ids` and `appears_after_ids` are **JSON arrays of UUIDs** forming a DAG of ordering dependencies.

#### Logic rules

| Field | Logic | Meaning |
|-------|-------|---------|
| `appears_after_ids` | **AND** | Show only when **all** listed procedures are taken |
| `appears_before_ids` | **OR** | Hide as soon as **any** listed procedure is taken |

#### Example

```json
"appears_after_ids":  ["uuid-A", "uuid-B"],
"appears_before_ids": ["uuid-C"]
```

This procedure is visible only when **both A and B are taken** and **C is not yet taken**.

#### API input/output
```json
// Input (create/update)
{ "appears_after_ids": ["uuid-A", "uuid-B"], "appears_before_ids": [] }

// Output (presenter / available-actions)
{ "appears_after_ids": ["uuid-A", "uuid-B"], "appears_before_ids": [] }
```

Empty arrays `[]` and `null` are both treated as "no constraint". The `sort_order` column controls display ordering within the visible set.

### 27.10 Traps

#### 27.10.1 `resolveInternalProcedureSettingByForm` Returns First Match

If multiple children share the same form, this method returns only the first one. Always prefer explicit ID resolution for user-triggered actions.

#### 27.10.2 Parent Steps vs Child Steps

Parent rows can have steps too (for Process-based workflows like task creation). Child rows have their own steps (for non-Process workflows like extensions/approvals). Do not confuse them.

#### 27.10.3 Conditions: Backend-Enforced vs Client-Enforced

**Some conditions are enforced by the backend.** As of 2026-06-18, the following conditions are validated server-side by `EmployeeTaskFormConditionService` before any workflow is started:

| Form | Condition key | Enforcement |
|------|--------------|-------------|
| `createTask` | `allow_during_shift` | Attendance shift check (HTTP 422 if violated) |
| `createTask` | `allow_outside_shift` | Attendance shift check (HTTP 422 if violated) |
| `createTask` | `allow_on_holidays` | Attendance holiday check (HTTP 422 if violated) |
| `endTask` | `can_exit_outside_location` | GPS radius check (HTTP 422 if violated) |

All other conditions (e.g., `max_attachments`, `has_task_duration`) are **client-enforced**: stored and returned to the client app, which must apply them in the UI. The backend does not currently evaluate them.

#### 27.10.4 `form` Must Match `InternalProcessForm` Cases

When creating a child, the `form` value must be a valid `InternalProcessForm` case. Invalid values are rejected at the API level.

---

## 28. Form Condition Enforcement

### 28.1 Overview

`Modules\EmployeeTask\Services\EmployeeTaskFormConditionService` is the backend gate that evaluates child `ProcedureSetting` conditions before a task lifecycle action is allowed to proceed. It fires **before** the workflow preview / workflow start, so a condition violation never creates orphan workflow records.

```
Employee Request
      │
      ▼
EmployeeTaskFormConditionService::check*Conditions()
      │
      ├── resolveInternalProcedureSettingByForm()  ← ProcedureWorkflowService
      │         returns ?ProcedureSetting (child)
      │
      ├── if null or conditions empty → pass (no restriction)
      │
      ├── read $setting->conditions  (JSON object, see §27.8)
      │
      ├── evaluate shift/holiday/location
      │
      └── throw EmployeeTaskException (HTTP 422) on violation
                    │
                    ▼
             WorkflowEngine::previewResponsibles()  ← proceeds only if no violation
```

### 28.2 createTask Condition Check

**Called by:** `EmployeeTaskRequestService::create()` — immediately after resolving `$branchId` and before `$engine->previewResponsibles()`.

**Conditions evaluated:**

| Condition key | Default | Enforcement logic |
|--------------|---------|------------------|
| `allow_during_shift` | `true` | If the user's `current_work_period` is not null (they are inside a scheduled period right now), this must be `true`. |
| `allow_outside_shift` | `false` | If the user has no active period, this must be `true`. |
| `allow_on_holidays` | `false` | If `is_holiday = true` in work rules, this must be `true`. Holiday check runs first; shift checks are skipped on holidays. |

**Attendance data source:** `AttendanceConstraintService::getTodaysWorkRulesForUser(User $user)` — returns `is_holiday` (bool) and `current_work_period` (?array). If no attendance constraint is assigned to the user, `current_work_period` is null (treated as outside shift) and `is_holiday` defaults to `false`.

**User loading:** the service loads `professionalData.attendanceConstraint`, `userProfessionalData.branch`, and `userProfessionalData.department` for the constraint resolution query.

### 28.3 endTask Condition Check

**Called by:** `EmployeeTaskLifecycleService::end()` — after the pending-end-request guard, before `resolveEndTaskProcedure()`.

**Conditions evaluated:**

| Condition key | Default | Enforcement logic |
|--------------|---------|------------------|
| `can_exit_outside_location` | `true` | If `false`, the employee must be within `task.radius_meters` of `task.end_location` / task GPS anchor. Uses `EmployeeTaskLocationService::isWithinTaskRadius($task, $lat, $lng)`. |

The task's `user.userProfessionalData` is lazy-loaded inside the service to resolve `branchId` for the procedure setting lookup.

### 28.4 startTask Condition Check (new rich array format)

**Called by:** `EmployeeTaskController::start()` via `EmployeeTaskLifecycleService::start()`.

`InternalProcessForm::StartTask` maps to 5 rich conditions (each evaluated only when `is_active = true`):

| Condition key | Category | Enforcement logic | Configurable settings |
|--------------|----------|------------------|-----------------------|
| `inside_shift_time` | `time` | Current server time within `[start_time − tolerance, end_time − tolerance]` | `start_time`, `end_time`, `allow_before_start_minutes`, `allow_before_end_minutes` |
| `inside_task_location` | `location` | Haversine distance ≤ `settings.radius_meters` | `radius_meters` |
| `employee_has_attendance` | `attendance` | Must have active clock-in record (`clock_out IS NULL`) | none |
| `task_is_approved` | `task_status` | `task.status` must equal `approved` | none |
| `no_open_task` | `open_task` | No other task for user with `status = in_progress` | none |

> See §31 for the full conditions system design and frontend integration guide.

### 28.5 Exceptions Thrown

| Exception method | HTTP | When thrown |
|-----------------|------|-------------|
| `notAllowedDuringShift()` | 422 | createTask: user is inside shift, `allow_during_shift = false` |
| `notAllowedOutsideShift()` | 422 | createTask: user is outside shift, `allow_outside_shift = false` |
| `notAllowedOnHolidays()` | 422 | createTask: today is a holiday, `allow_on_holidays = false` |
| `cannotEndTaskOutsideLocation()` | 422 | endTask: employee is outside radius, `can_exit_outside_location = false` |
| `outsideShiftTimeWindow()` | 422 | startTask: current time outside configured window |
| `employeeHasNoAttendance()` | 422 | startTask: no active attendance record |
| `taskNotApproved()` | 422 | startTask: task not in `approved` status |
| `hasOtherOpenTask()` | 422 | startTask: employee already has an `in_progress` task |
| `cannotStartTaskOutsideLocation()` | 422 | startTask: outside `inside_task_location` radius |

### 28.6 How to Add a New Condition

> **Updated 2026-06-25:** The condition system now uses a registry-driven evaluator pattern. See §34 for full details.

1. Add enum case to `InternalProcessCondition` (with `category()`, `labelAr()`, `settingsSchema()`).
2. Register it in `InternalProcessForm::conditions()` for the target form.
3. Add an `EmployeeTaskException::yourError()` factory.
4. Create a new evaluator class implementing `ConditionEvaluator` in `modules/EmployeeTask/Conditions/`.
5. Register the evaluator in `EmployeeTaskServiceProvider` (add to the `ConditionEvaluatorRegistry` constructor + singleton).
6. Add an exception case to `throwFromResult()` in `EmployeeTaskFormConditionService`.
7. **No changes** to `checkCreateTaskConditions()`, `evaluateAndThrow()`, or `getPreConditionResults()`.
8. See `docs/CONDITION_EVALUATOR_IMPLEMENTATION_GUIDE.md` for the full step-by-step recipe.

---

## 29. 2026-06-19 Feature: Deputy Manager, Himself, Array Fields

### 29.1 Summary of all changes

| Area | Change |
|---|---|
| `ActionTakerType` enum | Added `himself = 'himself'` |
| `ActionTakerManagementHierarchyType` enum | Added `deputy_manager = 'deputy_manager'` |
| `action_taker_alternative_management_hierarchy_type` DB column | Changed from `varchar(30)` to `text` (stores JSON array) |
| `action_taker_specific_procedure_type` DB column | Changed from `varchar(30)` to `text` (stores JSON array) |
| `action_taker_specific_procedure_id` DB column | Changed from `varchar(255)` to `text` (stores JSON array) |
| `ProcedureSettingStep` model casts | `alternative_type` and both specific-procedure fields now cast as `array` |
| `CreateProcedureSettingStepDTO` | `alternative_type`, `specific_type`, `specific_id` now `?array` |
| Request validation (Create + Update) | All three fields accept arrays; `action_taker_type` accepts `himself`; `forms` restricted to `approve` when type is `himself` |
| `ActionTakerResolver` | `deputy_manager` primary → returns manager + ALL deputies; `tryAlternatives` iterates array; `resolveSpecificProcedureUsers` iterates parallel arrays |
| `ProcedureWorkflowService` | `assertIsActionTaker` + `userCanActOnStep` unified for `management_hierarchy`, `specific_procedures`, `himself` using `resolveUsersForStep` |
| `ProcessWorkflowService` snapshot | `specific_procedure_types` (array) replaces `specific_procedure_type` (string); backward compat reads old key |
| `ProcessWorkflowService::rejectStep` | `isJobRole` check uses `in_array` over the types array |
| `WorkflowEngine` | `computeApprovalResponsiblesForSetting` unified to use `resolveUsersForStep` for all dynamic types |
| `ProcedureSettingStepPresenter` | `alternative_type` returns array + labels array; `specific_procedures` returns parallel arrays + combined `[{type,id}]` convenience field |
| Migration | `2026_06_19_000001_change_action_taker_columns_to_json_on_procedure_setting_steps.php` |

### 29.2 `deputy_manager` data flow

```
step.action_taker_management_hierarchy_type = 'deputy_manager'

ActionTakerResolver::resolveManagementHierarchyUsers()
  └─ resolveManagerAndDeputies()
       ├─ creator.professionalData.branch_id → ManagementHierarchy
       │    ├─ .manager_id                   → user A
       │    └─ .detail.deputyManagerRelations → users B, C, ...
       └─ returns [A, B, C, ...]  ← ALL stored in authorized_user_ids

ProcessWorkflowService::createProcessStep()
  ├─ assigned_user_id   = A  (first / primary)
  └─ authorized_user_ids = [A, B, C]

WorkflowStepActivated fired with userIds = [A, B, C]
  └─ All three receive notification

approveStep() / assertIsActionTaker()
  └─ checks Auth::id() ∈ authorized_user_ids → any one is sufficient
```

### 29.3 `himself` data flow

```
step.action_taker_type = 'himself'

ActionTakerResolver::resolveUsersForStep()
  └─ returns [$createdByUserId]

ProcessWorkflowService snapshot:
  ├─ assigned_user_id    = createdByUserId
  └─ authorized_user_ids = [createdByUserId]

Validation: forms field must be 'approve' (enforced in request)
```

### 29.4 Array specific-procedures data flow

```
step.action_taker_specific_procedure_type = ["branch", "management"]
step.action_taker_specific_procedure_id   = ["5",      "12"]

ActionTakerResolver::resolveSpecificProcedureUsers()
  ├─ resolves branch 5  → manager X
  ├─ resolves management 12 → manager Y
  └─ returns [X, Y]  ← merged, de-duplicated

Rejection:
  - types = ["branch", "management"] → no job_role → process FAILS on rejection
  - types = ["branch", "job_role"]   → has job_role → process ADVANCES on rejection
```

---

## 30. Multi-Module Centralized Taken-Status Expansion (2026-06-21)

### 30.1 Motivation

Phase 1 centralised taken-status tracking for `EmployeeTask` in `internal_procedure_takens`. This section documents the three-phase expansion to all modules.

---

### 30.2 Phase 1 — Generic `InternalProcedureAvailableActionsService`

**File**: `modules/ProcedureSetting/Services/InternalProcedureAvailableActionsService.php`

A single, module-agnostic service that encapsulates all available-actions filtering logic. Any module calls `forProcessable()` — no duplication needed.

```php
$actions = $this->actionsService->forProcessable(
    processableType: 'employee_task',   // or 'client_request', etc.
    processableId:   $task->id,
    procedureCategoryType: ProcedureSettingType::EmployeeTask->value,
    companyId:       $task->company_id,
    branchId:        $branchId,         // nullable
);
```

**Filtering rules (applied internally)**:
- `is_active = true`
- `appears_after_ids` *(array)*: **ALL** IDs must be in `takenIds` → else hidden (AND)
- `appears_before_ids` *(array)*: **ANY** ID in `takenIds` → hidden (OR)

**Module wrappers** (thin, only resolve entity context):
- `EmployeeTaskAvailableActionsService::forTask(string $taskId)` → resolves branch from `userProfessionalData`, calls `forProcessable()`
- `ClientRequestAvailableActionsService::forClientRequest(string $id)` → resolves `branch_id` from model, calls `forProcessable()`

**Adding a new module** takes ~10 lines:
```php
final class FooAvailableActionsService
{
    public function __construct(
        private readonly InternalProcedureAvailableActionsService $actionsService,
    ) {}

    public function forFoo(string $fooId): array
    {
        $foo = Foo::query()->findOrFail($fooId);
        return $this->actionsService->forProcessable(
            'foo', $foo->id, ProcedureSettingType::Foo->value,
            $foo->company_id, $foo->branch_id,
        );
    }
}
```

---

### 30.3 Phase 2 — ClientRequest Integration

#### Marking procedures as taken

| Trigger | Form marked taken | Location |
|---------|-------------------|----------|
| CR created | `createClientRequest` | `ClientRequestCRUDService::markCreateProceduresTaken()` |
| CR workflow fully approved (all steps) | `endClientRequest` | `ClientRequestWorkflowService::markEndProceduresTaken()` |

Both methods:
1. Call `WorkflowEngine::resolveParentSetting(type, companyId, branchId)` to find the parent `ProcedureSetting`.
2. Query active children with the relevant `form` key.
3. Fire `WorkflowProcedureTaken` event for each (see §30.4).

#### Available-actions API endpoint

```
GET /api/v1/client-requests/{id}/available-actions
Permission: CLIENT_REQUEST_VIEW
```

Returns the same structure as `GET /employee-tasks/{id}/available-actions`:
```json
[
  {
    "id": "uuid",
    "name": "إنشاء طلب عميل",
    "form": { "key": "createClientRequest", "label_ar": "إنشاء طلب عميل" },
    "conditions": [],
    "appears_before_ids": [],
    "appears_after_ids": [],
    "sort_order": 100
  }
]
```

---

### 30.4 Phase 3 — Event-Driven Architecture

#### Zero-coupling design

External modules fire a domain event instead of injecting `ProcedureWorkflowService` just to mark procedures.

**Event**: `Modules\ProcedureSetting\Events\WorkflowProcedureTaken`

```php
new WorkflowProcedureTaken(
    processableType:    'client_request',
    processableId:      $cr->id,
    procedureSettingId: $settingId,
    takenBy:            $userId,      // nullable
)
```

**Listener**: `Modules\ProcedureSetting\Listeners\RecordInternalProcedureTaken`
- Registered in `ProcedureSettingServiceProvider::registerEventListeners()`
- Calls `ProcedureWorkflowService::markProcedureTaken()` → writes to `internal_procedure_takens`

#### Call-site map after Phase 3

| Where | Change |
|-------|--------|
| `EmployeeTaskController::start()` | `event(new WorkflowProcedureTaken(...))` — removed `ProcedureWorkflowService` injection |
| `EmployeeTaskController::locationPing()` | `event(new WorkflowProcedureTaken(...))` — removed injection |
| `EmployeeTaskLifecycleService::end()` | `Event::dispatch(new WorkflowProcedureTaken(...))` — removed injection |
| `EmployeeTaskExtensionService::requestExtension()` | `event(...)` — injection kept (still uses `resolveFirstStepBySettingId`) |
| `EmployeeTaskApprovalService::create()` (auto-approve) | `event(...)` — injection kept (still uses other `ProcedureWorkflowService` methods) |
| `EmployeeTaskRequestService::markCreateTaskProceduresTaken()` | `event(...)` — removed `ProcedureWorkflowService` injection |
| `ClientRequestCRUDService::markCreateProceduresTaken()` | `event(...)` — never had the injection; uses `WorkflowEngine` |
| `ClientRequestWorkflowService::markEndProceduresTaken()` | `event(...)` — already had `WorkflowEngine` injection |
| `ProcedureWorkflowService::advance()` (when `isFinal`) | **Direct call** — internal to ProcedureSetting module, no event needed |

#### Adding a new module (full recipe)

1. Add `YourType = 'your_type'` to `ProcedureSettingType` enum.
2. Create `YourModule\Services\YourAvailableActionsService` (thin wrapper, ~10 lines).
3. In entity creation service: fire `WorkflowProcedureTaken('your_type', $id, $settingId, $userId)` for each `createX`-form setting.
4. In entity completion service: fire `WorkflowProcedureTaken('your_type', $id, $settingId, $userId)` for each `endX`-form setting.
5. Add `GET /{id}/available-actions` route with `VIEW` permission.
6. No changes to `ProcedureSetting` module code required — the event listener handles everything automatically.

---

### 30.5 Multi-Value `appears_after_ids` / `appears_before_ids` (2026-06-21)

#### What changed

`appears_after_id` and `appears_before_id` on `procedure_settings` changed from **single UUID** columns to **JSON array** columns, enabling a procedure to depend on or precede multiple others simultaneously.

| | Before | After |
|--|--------|-------|
| DB column type | `uuid` (nullable) | `json` (nullable) |
| FK constraint | Yes (`nullOnDelete`) | **Removed** (JSON can't have FKs) |
| Model cast | none (raw string) | `'array'` |
| API input | `"appears_after_id": "uuid"` | `"appears_after_id": ["uuid1", "uuid2"]` |
| API output key | `appears_after_id` | `appears_after_ids` |
| Empty / null | `null` | `[]` |

#### Logic semantics

| Field | Logic | Rule |
|-------|-------|------|
| `appears_after_ids` | **AND** | Procedure is shown only when **all** IDs in this array are taken |
| `appears_before_ids` | **OR** | Procedure is hidden as soon as **any** ID in this array is taken |

#### Files changed

| File | Change |
|------|--------|
| `ProcedureSetting/Database/Migrations/2026_06_21_000001_...php` | Drops FKs, converts existing rows, changes column type |
| `ProcedureSetting/Models/ProcedureSetting.php` | Added `array` casts; removed `belongsTo` relations |
| `ProcedureSetting/Services/InternalProcedureAvailableActionsService.php` | Filter loop replaces single-value check; output uses plural keys |
| `ProcedureSetting/Requests/CreateInternalProcedureSettingRequest.php` | `'appears_after_id' => ['nullable','array']` + `'appears_after_id.*' => ['uuid','exists:...']` |
| `ProcedureSetting/Requests/UpdateInternalProcedureSettingRequest.php` | Same |
| `ProcedureSetting/Presenters/InternalProcedureSettingPresenter.php` | Output key renamed to plural, always returns `[]` not `null` |
| `ProcedureSetting/Presenters/ProcedureSettingPresenter.php` | Same |

---

## 31. Rich Conditions System (2026-06-21)

> Full reference: `docs/CONDITIONS_SYSTEM_GUIDE.md`

### 31.1 Problem Solved

The old `conditions` column was a flat JSON object (`{"allow_during_shift": true}`) — values only, no metadata. Frontend had no way to know what conditions exist for a form, what their display labels are, or what configurable sub-fields they need.

The new system adds:
- A discovery endpoint the frontend calls once per form type.
- A `settings_schema` per condition describing its configurable parameters.
- A rich stored format with `is_active`, `sort_order`, and `settings`.

---

### 31.2 New Enums

#### `InternalProcessConditionCategory` (new file)

`modules/Shared/InternalProcessType/Enums/InternalProcessConditionCategory.php`

| Case | Value | `labelAr()` |
|------|-------|-------------|
| `Time` | `time` | وقت |
| `Location` | `location` | موقع |
| `Attendance` | `attendance` | حضور |
| `TaskStatus` | `task_status` | حالة المهمة |
| `OpenTask` | `open_task` | مهمة مفتوحة |
| `Shift` | `shift` | دوام |
| `Duration` | `duration` | مدة |
| `Attachment` | `attachment` | مرفقات |

#### `InternalProcessConditionType` — added `Time = 'time'`

Used for `HH:MM` string values stored in `settings`.

---

### 31.3 New Methods on `InternalProcessCondition`

| Method | Returns | Purpose |
|--------|---------|---------|
| `category()` | `InternalProcessConditionCategory` | Groups condition by kind |
| `settingsSchema()` | `list<array{key, type, label_ar, default}>` | Describes configurable parameters for this condition |
| `toDefinition()` | array | Full shape sent to frontend via `formsConditions` endpoint |
| `validationRulesForForm()` | `array<string, list>` | **Changed** — now validates the rich array format: `conditions.*.key`, `conditions.*.is_active`, `conditions.*.sort_order`, `conditions.*.settings` |
| `defaultValuesForForm()` | `list<array>` | **Changed** — returns list of condition objects with `settingsSchema` defaults |

---

### 31.4 New Conditions for `startTask`

`InternalProcessForm::StartTask::conditions()` now returns:

```php
[
    InternalProcessCondition::InsideShiftTime,       // inside_shift_time
    InternalProcessCondition::InsideTaskLocation,    // inside_task_location
    InternalProcessCondition::EmployeeHasAttendance, // employee_has_attendance
    InternalProcessCondition::TaskIsApproved,        // task_is_approved
    InternalProcessCondition::NoOpenTask,            // no_open_task
]
```

---

### 31.5 Discovery API

```
GET /api/v1/admin/procedure-settings/forms-conditions?type=startTask
```

Returns one definition object per condition in the form:

```json
{
  "key": "inside_shift_time",
  "type": "time",
  "category": "time",
  "category_label_ar": "وقت",
  "label_ar": "داخل وقت الدوام",
  "settings_schema": [
    { "key": "start_time",                 "type": "time", "label_ar": "من",                                   "default": "08:00" },
    { "key": "end_time",                   "type": "time", "label_ar": "إلى",                                  "default": "17:00" },
    { "key": "allow_before_start_minutes", "type": "int",  "label_ar": "يسمح قبل بداية الدوام بـ (دقيقة)", "default": 0 },
    { "key": "allow_before_end_minutes",   "type": "int",  "label_ar": "يسمح قبل نهاية الدوام بـ (دقيقة)", "default": 0 }
  ]
}
```

**Route:** `modules/ProcedureSetting/Resources/routes/api.php`
**Controller method:** `InternalProcedureSettingController::formsConditions(Request $request)`
**No authentication middleware change** — sits inside the existing `auth:api` group.

---

### 31.6 Frontend Flow

```
1. GET /forms-conditions?type=startTask
      → receive array of condition definitions (each with settings_schema)

2. Render table rows — one per definition:
      toggle  |  label_ar  |  category_label_ar  |  settings inputs

3. For each settings_schema field:
      type = 'time'          → time picker (HH:MM)
      type = 'int'           → number input
      type = 'bool'          → checkbox / toggle
      type = 'select'        → dropdown list
      type = 'map_polygons'  → interactive map with polygon drawing (stored as array of polygon vertex arrays)

4. On save  →  POST or PUT with:
   "conditions": [
     { "key": "inside_shift_time", "is_active": true, "sort_order": 1,
       "settings": { "start_time": "08:00", "end_time": "17:00",
                     "allow_before_start_minutes": 30, "allow_before_end_minutes": 0 } },
     { "key": "inside_task_location", "is_active": true, "sort_order": 2,
       "settings": { "radius_meters": 150 } },
     { "key": "inside_custom_locations", "is_active": true, "sort_order": 3,
       "settings": { "polygons": [
         [{ "lat": 24.7136, "lng": 46.6753 }, { "lat": 24.7140, "lng": 46.6760 }, { "lat": 24.7130, "lng": 46.6760 }],
         [{ "lat": 24.7200, "lng": 46.6800 }, { "lat": 24.7210, "lng": 46.6810 }, { "lat": 24.7200, "lng": 46.6810 }]
       ] } },
     { "key": "employee_has_attendance", "is_active": false, "sort_order": 4, "settings": {} },
     { "key": "task_is_approved",        "is_active": false, "sort_order": 5, "settings": {} },
     { "key": "no_open_task",            "is_active": true,  "sort_order": 6, "settings": {} }
   ]
```

---

### 31.7 Backend Dual-Format Support

`EmployeeTaskFormConditionService::indexConditions(array $conditions)` normalises both formats into a keyed map `['condition_key' => {key, is_active, sort_order, settings}]`:

```
New format (array_is_list = true):
  [{key: "inside_shift_time", is_active: true, ...}]  →  ['inside_shift_time' => {…}]

Old format (associative):
  {"allow_during_shift": true}  →  ['allow_during_shift' => {key, is_active: true, settings: []}]
```

This means **old `createTask`/`endTask` procedures stored in the database continue to work unchanged**.

---

### 31.8 Files Changed in This Feature

| File | Change |
|------|--------|
| `modules/Shared/InternalProcessType/Enums/InternalProcessConditionCategory.php` | **New** |
| `modules/Shared/InternalProcessType/Enums/InternalProcessConditionType.php` | Added `Time` case |
| `modules/Shared/InternalProcessType/Enums/InternalProcessCondition.php` | 5 new cases; new `category()`, `settingsSchema()`; updated `toDefinition()`, `validationRulesForForm()`, `defaultValuesForForm()` |
| `modules/Shared/InternalProcessType/Enums/InternalProcessForm.php` | `StartTask` conditions updated to 5 new rich cases |
| `modules/ProcedureSetting/Controllers/InternalProcedureSettingController.php` | Added `formsConditions()` |
| `modules/ProcedureSetting/Resources/routes/api.php` | Added `GET /forms-conditions` |
| `modules/ProcedureSetting/Requests/CreateInternalProcedureSettingRequest.php` | Removed `prepareForValidation()` normalization |
| `modules/ProcedureSetting/Requests/UpdateInternalProcedureSettingRequest.php` | Same |
| `modules/EmployeeTask/Exceptions/EmployeeTaskException.php` | Added 4 new exception factories |
| `modules/EmployeeTask/Services/EmployeeTaskFormConditionService.php` | Full rewrite with `indexConditions()`, 5 new `assert*()` methods, `AttendanceRepository` injection |
| `docs/CONDITIONS_SYSTEM_GUIDE.md` | **New** — standalone guide for AI/frontend/backend |

---

## 32. action_taker_management_hierarchies Refactor (2026-06-22)

### Background

The old format used two separate fields:
- `action_taker_management_hierarchy_type` — single string (e.g. `"branch_manager"`)
- `action_taker_alternative_management_hierarchy_type` — JSON array of fallback type strings

The new format uses a single JSON column:
- `action_taker_management_hierarchies` — array of `{action_taker_management_hierarchy_type, is_Deputy_Director}` objects

### What Changed

| Aspect | Old | New |
|--------|-----|-----|
| DB column | `action_taker_management_hierarchy_type` (string) + `action_taker_alternative_management_hierarchy_type` (text/JSON) | `action_taker_management_hierarchies` (text/JSON) |
| Deputy handling | `deputy_manager` as a type value | `is_Deputy_Director: true` boolean flag on any row |
| Max entries | Unlimited alternatives | Max 3 rows |
| Allowed types | `branch_manager`, `management_manager`, `project_manager`, `deputy_manager` | `branch_manager`, `management_manager`, `project_manager` (no `deputy_manager`) |
| Resolution | Primary type → fallback alternatives in order | All rows resolved, users merged; any one user accepting advances step |
| Unresolvable row | N/A (single primary + fallbacks) | Silently skipped (e.g. `project_manager` without `project_id` → try next row) |

### Resolution Logic (ActionTakerResolver)

```
resolveUsersForStep(step, createdByUserId, context)
  └─ resolveManagementHierarchyUsers()
       ├─ if step.action_taker_management_hierarchies is not empty:
       │    └─ resolveFromManagementHierarchiesArray()
       │         ├─ for each row in array:
       │         │    ├─ resolve manager by type (branch_manager, management_manager, project_manager)
       │         │    ├─ if is_Deputy_Director: also resolve all deputy managers for that hierarchy
       │         │    └─ merge into result set (de-duplicated)
       │         └─ return all unique user IDs
       │
       └─ else (legacy fallback):
            ├─ if type === 'deputy_manager': resolveManagerAndDeputies()
            └─ else: resolveManagerFromCreatorHierarchy() → [single_id]
```

### "One Accept Advances Step" Behavior

When `is_Deputy_Director: true` is set on a row, both the manager AND all deputy managers are resolved. Any one of them accepting/approving the step advances it — there is no requirement for all to act.

Example: `[{type: "branch_manager", is_Deputy_Director: true}]`
- Resolves: branch manager (user A) + all deputy managers (users B, C)
- Any of A, B, or C can approve → step advances

### Skipping Unresolvable Rows

If a row's type cannot resolve, it is silently skipped and the next row is tried:

Example: `[{type: "project_manager", is_Deputy_Director: false}, {type: "branch_manager", is_Deputy_Director: false}]`
- EmployeeTask without `project_id` → `project_manager` row skipped
- `branch_manager` row resolves → step proceeds with branch manager

### Backward Compatibility

- Legacy fields remain in DB, model `$fillable`, and `$casts`.
- GET endpoints return both old and new format fields.
- If `action_taker_management_hierarchies` column is empty, presenter builds the array from legacy fields.
- `deputy_manager` enum case kept in `ActionTakerManagementHierarchyType` for reading old data.
- Legacy requests with old fields still accepted (validation relaxed: `required_if` removed from `action_taker_management_hierarchy_type`).

### Files Changed

| File | Change |
|------|--------|
| `modules/ProcedureSetting/Database/Migrations/2026_06_22_000001_add_action_taker_management_hierarchies_to_procedure_setting_steps.php` | **New** — adds column |
| `modules/ProcedureSetting/Models/ProcedureSettingStep.php` | Added to `$fillable` and `$casts` |
| `modules/ProcedureSetting/Requests/CreateProcedureSettingStepRequest.php` | New validation rules; legacy `required_if` removed |
| `modules/ProcedureSetting/Requests/UpdateProcedureSettingStepRequest.php` | Same with `sometimes` |
| `modules/ProcedureSetting/DTO/CreateProcedureSettingStepDTO.php` | New field in constructor + `toArray()` |
| `modules/ProcedureSetting/Presenters/ProcedureSettingStepPresenter.php` | Returns new field; backward-compat build from legacy |
| `modules/ProcedureSetting/Services/ActionTakerResolver.php` | New methods: `resolveFromManagementHierarchiesArray`, `resolveManagerByType`, `resolveDeputyManagersForType`; updated `resolveManagementHierarchyUsers` + `resolveManagerFromCreatorHierarchy` |
| `docs/PROCEDURE_STEPS_API_CHANGES.md` | **New** — changelog |

---

## 33. InsideCustomLocations Condition (2026-06-24)

### Background

New `InternalProcessCondition::InsideCustomLocations` allows admins to define custom polygon areas on a map. When the condition is active, the employee's task location (`task_latitude`, `task_longitude`) must fall inside at least one of the configured polygons.

### Key Behaviour

- **Form group**: `in_form` (not a precondition — it validates the task's location data entered in the form).
- **Settings schema type**: `map_polygons` — stored as array of polygons, each polygon is an ordered list of `{lat, lng}` vertices.
- **Backend check**: `EmployeeTaskFormConditionService::assertCustomLocationConditions()` uses `GeoPolygon::isPointInAnyPolygon()` (ray-casting algorithm).
- **Exception thrown**: `EmployeeTaskException::outsideCustomLocations()` (422).
- **Task location**: The task's GPS coordinates (from `CreateEmployeeTaskRequestDTO::$taskLatitude` / `$taskLongitude`) are checked, NOT the employee's current location.

### Files Changed

| File | Change |
|------|--------|
| `modules/Shared/InternalProcessType/Enums/InternalProcessCondition.php` | Added `InsideCustomLocations` case with `labelAr()`, `category()`, `formGroup()`, and `settingsSchema()` containing `type: 'map_polygons'` |
| `modules/Shared/InternalProcessType/Enums/InternalProcessForm.php` | Added `InsideCustomLocations` to `CreateTask` conditions list |
| `modules/EmployeeTask/Support/GeoPolygon.php` | **New** — ray-casting point-in-polygon algorithm |
| `modules/EmployeeTask/Exceptions/EmployeeTaskException.php` | Added `outsideCustomLocations()` |
| `modules/EmployeeTask/Services/EmployeeTaskFormConditionService.php` | `checkCreateTaskConditions()` now accepts `$taskLatitude` / `$taskLongitude`; new `assertCustomLocationConditions()` method; new `getPreConditionResults()` method for mobile precondition check API; new `getInFormConditionsPreview()` method for mobile in-form constraints preview; `checkStartTaskConditions()` now evaluates `AllowOnHolidays` for `startTask` form |
| `modules/EmployeeTask/Services/EmployeeTaskRequestService.php` | Passes `$taskLatitude` / `$taskLongitude` from DTO to condition service |
| `modules/EmployeeTask/Controllers/EmployeeTaskController.php` | Added `preConditions()` method for `GET /employee-tasks/pre-conditions`; added `inFormConditions()` method for `GET /employee-tasks/in-form-conditions` |
| `modules/EmployeeTask/Routes/employee_tasks.php` | Added `GET /pre-conditions` and `GET /in-form-conditions` routes |
| `modules/Shared/InternalProcessType/Enums/InternalProcessForm.php` | `StartTask` conditions now include `AllowOnHolidays` |

---

## 34. Centralized Condition Evaluation (2026-06-25, rev 2)

> **Full implementation guide:** `docs/CONDITION_EVALUATOR_IMPLEMENTATION_GUIDE.md`

### 34.1 Problem

The old `EmployeeTaskFormConditionService` had hard-coded `if`/`assert*()` calls for each condition. Adding a new condition required modifying `checkCreateTaskConditions()`, adding a new `assert*()` method, and wiring it into the flow. This violated the Open/Closed Principle and made the system EmployeeTask-only.

### 34.2 Solution: Strategy + Registry + Central Engine

The condition evaluation system was refactored in two phases:

1. **Rev 1** — Registry-driven dispatch: each condition gets a dedicated evaluator class implementing `ConditionEvaluator`. A registry maps enum values to evaluators.
2. **Rev 2** — Centralization: core infrastructure (interface, DTOs, registry, evaluation engine) moved to `modules/ProcedureSetting/Conditions/` so any module can reuse it. Module-specific exception mapping via `ExceptionResolver` interface. In-form preview auto-generated via `toPreview()`.

### 34.3 Two-Layer Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│  modules/ProcedureSetting/Conditions/  (SHARED INFRASTRUCTURE)  │
│                                                                 │
│  ConditionEvaluator        (interface)                          │
│  ConditionContext          (DTO — data bag)                     │
│  ConditionResult           (DTO — evaluation result)            │
│  ConditionEvaluatorRegistry (condition enum → evaluator map)    │
│  ExceptionResolver         (interface — module-specific throws) │
│  ConditionEvaluationService (central engine — evaluateAndThrow) │
└─────────────────────────────────────────────────────────────────┘
                              ▲
                              │ uses
                              │
┌─────────────────────────────────────────────────────────────────┐
│  modules/EmployeeTask/Conditions/  (MODULE-SPECIFIC)            │
│                                                                 │
│  6 evaluator classes  ── implement ConditionEvaluator           │
│  EmployeeTaskExceptionResolver ── implements ExceptionResolver  │
│  ResolvesUserAttendance ── trait (shared user logic)            │
│  ConditionEvaluator/Context/Result/Registry ── deprecated stubs │
└─────────────────────────────────────────────────────────────────┘
```

### 34.4 Core Components (Shared — `ProcedureSetting\Conditions`)

| Component | File | Purpose |
|-----------|------|---------|
| `ConditionEvaluator` (interface) | `ProcedureSetting/Conditions/ConditionEvaluator.php` | Strategy contract: `condition()` + `evaluate()` |
| `ConditionContext` (DTO) | `ProcedureSetting/Conditions/ConditionContext.php` | Immutable data bag (userId, coordinates, duration, taskDate, etc.) |
| `ConditionResult` (DTO) | `ProcedureSetting/Conditions/ConditionResult.php` | Evaluation output (key, labelAr, passed, message, exception, context) |
| `ConditionEvaluatorRegistry` | `ProcedureSetting/Conditions/ConditionEvaluatorRegistry.php` | Maps `InternalProcessCondition` → evaluator; provides `get()` and `forFormGroup()` |
| `ExceptionResolver` (interface) | `ProcedureSetting/Conditions/ExceptionResolver.php` | Module-specific exception mapping contract: `throwFromResult()` |
| `ConditionEvaluationService` | `ProcedureSetting/Conditions/ConditionEvaluationService.php` | Central engine: `evaluateAndThrow()` + `evaluateForResults()` |

### 34.5 Module-Specific Components (EmployeeTask)

| Component | File | Purpose |
|-----------|------|---------|
| `EmployeeTaskExceptionResolver` | `EmployeeTask/Conditions/EmployeeTaskExceptionResolver.php` | Maps `ConditionResult::$exception` → `EmployeeTaskException` factories |
| `ResolvesUserAttendance` (trait) | `EmployeeTask/Conditions/ResolvesUserAttendance.php` | Shared user loading + timezone resolution for attendance-based evaluators |
| 6 evaluator classes | `EmployeeTask/Conditions/*Evaluator.php` | Implement `ConditionEvaluator` (import from `ProcedureSetting\Conditions`) |
| Deprecated stubs | `EmployeeTask/Conditions/Condition{Evaluator,Context,Result,Registry}.php` | Extend shared classes for backward compat |

### 34.6 Registered Evaluators

| Evaluator | Condition | Form Group | Exception Key |
|-----------|-----------|------------|---------------|
| `AllowDuringShiftEvaluator` | `allow_during_shift` | precondition | `notAllowedDuringShift` / `outsideShiftTimeWindow` |
| `AllowOnHolidaysEvaluator` | `allow_on_holidays` | precondition | `notAllowedOnHolidays` |
| `AllowOutsideShiftEvaluator` | `allow_outside_shift` | precondition | `notAllowedOutsideLocation` |
| `InsideCustomLocationsEvaluator` | `inside_custom_locations` | in_form | `outsideCustomLocations` |
| `MaxTaskDurationEvaluator` | `max_task_duration` | in_form | `taskDurationExceedsLimit` |
| `MaxScheduledDateOffsetEvaluator` | `max_scheduled_date_offset` | in_form | `taskDateTooFarInFuture` / `taskDateExceedsContractEndDate` |

### 34.7 Exception Mapping

`EmployeeTaskExceptionResolver::throwFromResult()` maps `ConditionResult::$exception` to `EmployeeTaskException` factories. The central `ConditionEvaluationService` delegates to this resolver — it never knows about `EmployeeTaskException` directly. Evaluators that need parameters (e.g. `maxHours`, `maxDays`) pass them via `ConditionResult::$context`.

### 34.8 Automatic In-Form Preview (toPreview)

`getInFormConditionsPreview()` now uses `InternalProcessCondition::toPreview()` instead of a hard-coded `match` block. `toPreview()` auto-generates `{mode, constraints}` from `settingsSchema()` + stored settings, respecting `visible_when` filters. Adding a new in_form condition with a `settingsSchema()` automatically makes it appear in the preview API — zero changes to `getInFormConditionsPreview()`.

### 34.9 How to Add a New Condition (Open/Closed)

1. Add enum case to `InternalProcessCondition` (with `category()`, `labelAr()`, `formGroup()`, `settingsSchema()`).
2. Register in `InternalProcessForm::conditions()`.
3. Add exception factory to `EmployeeTaskException`.
4. Create evaluator class implementing `ConditionEvaluator` (import from `ProcedureSetting\Conditions`).
5. Register evaluator in `EmployeeTaskServiceProvider` (add to registry constructor + singleton).
6. Add exception case to `EmployeeTaskExceptionResolver::throwFromResult()`.
7. **Zero changes** to `checkCreateTaskConditions()`, `ConditionEvaluationService`, `getPreConditionResults()`, `getInFormConditionsPreview()`, or any existing evaluator.

### 34.10 Reusing the Engine in Other Modules

Any module (e.g. ClientRequest) can reuse the central engine with 5 steps:
1. Create evaluators implementing `ProcedureSetting\Conditions\ConditionEvaluator`
2. Create an `ExceptionResolver` implementation for the module's exceptions
3. Register evaluators + resolver in the module's service provider
4. Build a thin condition service injecting `ConditionEvaluationService` + registry + resolver
5. Call `evaluateAndThrow()` from the module's controller/service

**Zero changes to `ProcedureSetting` module needed.**

### 34.11 Backward Compatibility

- The dual-format `indexConditions()` normalizer is preserved — old flat associative conditions still work.
- Unknown condition keys (no registered evaluator) are silently skipped, preventing breakage during partial deployments.
- All existing exception types and HTTP 422 responses are preserved.
- Deprecated stubs in `EmployeeTask\Conditions` extend shared classes so old imports don't break.

### 34.12 Files Changed (rev 2)

| File | Change |
|------|--------|
| `modules/ProcedureSetting/Conditions/ConditionEvaluator.php` | **New** — shared interface |
| `modules/ProcedureSetting/Conditions/ConditionContext.php` | **New** — shared DTO |
| `modules/ProcedureSetting/Conditions/ConditionResult.php` | **New** — shared DTO |
| `modules/ProcedureSetting/Conditions/ConditionEvaluatorRegistry.php` | **New** — shared registry |
| `modules/ProcedureSetting/Conditions/ExceptionResolver.php` | **New** — exception resolver interface |
| `modules/ProcedureSetting/Conditions/ConditionEvaluationService.php` | **New** — central engine |
| `modules/EmployeeTask/Conditions/EmployeeTaskExceptionResolver.php` | **New** — maps to EmployeeTaskException |
| `modules/EmployeeTask/Conditions/ConditionEvaluator.php` | **Deprecated stub** — extends shared interface |
| `modules/EmployeeTask/Conditions/ConditionContext.php` | **Deprecated stub** — extends shared DTO |
| `modules/EmployeeTask/Conditions/ConditionResult.php` | **Deprecated stub** — extends shared DTO |
| `modules/EmployeeTask/Conditions/ConditionEvaluatorRegistry.php` | **Deprecated stub** — extends shared registry |
| `modules/EmployeeTask/Conditions/*Evaluator.php` (6 files) | Updated imports to `ProcedureSetting\Conditions` |
| `modules/EmployeeTask/Services/EmployeeTaskFormConditionService.php` | Delegates to `ConditionEvaluationService`; `getInFormConditionsPreview()` uses `toPreview()` |
| `modules/EmployeeTask/Providers/EmployeeTaskServiceProvider.php` | Registers `EmployeeTaskExceptionResolver`; imports registry from `ProcedureSetting\Conditions` |
| `modules/ProcedureSetting/Providers/ProcedureSettingServiceProvider.php` | Registers `ConditionEvaluationService` singleton |
| `modules/Shared/InternalProcessType/Enums/InternalProcessCondition.php` | Added `toPreview()` method |
| `docs/CONDITION_EVALUATOR_IMPLEMENTATION_GUIDE.md` | Updated to rev 2 — full centralized architecture |

---

## 35. Complete Module Dependency Map (2026-06-30)

> **Read this first if you are a new AI session.** This section tells you which modules currently use the procedure workflow system, what `ProcedureSettingType` they register, what forms they use, and what services they inject. If you change any workflow service, check this map to see what else might break.

### 35.1 Modules That Depend on the Procedure Workflow System

| Module | `ProcedureSettingType` | `processable_type` string | Forms Used | Key Services Injected | Inbox? |
|--------|----------------------|--------------------------|------------|----------------------|--------|
| **EmployeeTask** | `employee_task` | `employee_task` | `createTask`, `startTask`, `endTask`, `extendTaskTime`, `sendForApproval`, `confirmLocation` | `WorkflowEngine`, `ProcessWorkflowService`, `ProcedureWorkflowService`, `EmployeeTaskFormConditionService` | Yes — `EmployeeTaskRepository::paginateInboxForAdmin()` |
| **EmployeeTask** (project notifications) | `project_notification_task` | `project_notification_task` | `createProjectNotificationTask`, `endProjectNotificationTask`, `updateProjectNotificationTask`, `updateProjectNotificationSiteStatus`, `projectNotificationFine`, `confirmProjectNotificationLocation`, `projectNotificationWorkStoppageReport`, `projectNotificationWorkResumption`, `projectNotificationTaskPostponement` | `WorkflowEngine`, `ProcessWorkflowService`, `ProcedureWorkflowService` (via `EmployeeTaskRequestService`) | Yes — `ProjectNotificationRepository::paginatedForInbox()` via `WorkflowEngine::pendingProcessScopeForUser()` |
| **ClientRequest** | `client_request` | `client_request` | `createClientRequest`, `endClientRequest`, `attachAttachments` | `WorkflowEngine` | No (placeholder notifier returns zero counts) |
| **PriceOffer** | `price_offer` | _(not yet implemented)_ | `createPriceOffer`, `endPriceOffer` | _(enum registered, no service yet)_ | No |
| **Contract** | `contract` | _(not yet implemented)_ | `createContract`, `endContract` | _(enum registered, no service yet)_ | No |
| **Meeting** | `meeting` | _(not yet implemented)_ | `createMeeting`, `endMeeting` | _(enum registered, no service yet)_ | No |

### 35.2 Service Injection Map (who injects what)

```
WorkflowEngine
  ├─ injected by: EmployeeTaskRequestService
  ├─ injected by: ClientRequestCRUDService
  ├─ injected by: ClientRequestWorkflowService
  ├─ injected by: ProjectNotificationService
  ├─ injected by: ProjectNotificationRepository
  └─ injected by: EmployeeTaskRequestService (via createLifecycleProcess)

ProcessWorkflowService
  ├─ injected by: WorkflowEngine
  ├─ injected by: EmployeeTaskRequestService
  ├─ injected by: EmployeeTaskStartRequestService
  ├─ injected by: EmployeeTaskEndRequestService
  └─ injected by: ClientRequestWorkflowService (legacy — has own initializeProcessSteps)

ProcedureWorkflowService
  ├─ injected by: EmployeeTaskExtensionService
  ├─ injected by: EmployeeTaskExtensionWorkflowService
  ├─ injected by: EmployeeTaskApprovalService
  ├─ injected by: EmployeeTaskStartRequestService
  ├─ injected by: EmployeeTaskEndRequestService
  └─ injected by: EmployeeTaskRequestService (via createLifecycleProcess)

ActionTakerResolver
  ├─ injected by: WorkflowEngine
  ├─ injected by: ProcessWorkflowService
  └─ injected by: ProcedureWorkflowService

EmployeeTaskFormConditionService
  ├─ injected by: EmployeeTaskRequestService
  └─ injected by: EmployeeTaskLifecycleService (for endTask conditions)

ConditionEvaluationService (shared central engine)
  ├─ injected by: EmployeeTaskFormConditionService
  └─ available for any module via service container

WorkflowNotifierRegistry
  ├─ registers: EmployeeTaskWorkflowNotifier → 'employee_task'
  ├─ registers: ClientRequestWorkflowNotifier → 'client_request'
  └─ registers: ProjectNotificationWorkflowNotifier → 'project_notification_task' (if exists)
```

### 35.3 Event Flow Map (who fires what)

```
WorkflowStepActivated (fired by ProcessWorkflowService::createProcessStep)
  └─ listened by: SendWorkflowStepNotification
       ├─ looks up WorkflowNotifierRegistry by process.processable_type
       ├─ EmployeeTaskWorkflowNotifier → broadcasts EmployeeTaskNotification + InboxCountsUpdated
       ├─ ClientRequestWorkflowNotifier → no-op (placeholder)
       └─ sends WorkflowActionRequired (mail + SMS) if flags set

WorkflowProcedureTaken (fired when a procedure is completed/taken)
  ├─ fired by: ProcessWorkflowService::fireProcedureTakenIfApplicable (on Process Completed)
  ├─ fired by: EmployeeTaskController::start() (manual)
  ├─ fired by: EmployeeTaskController::locationPing() (manual)
  ├─ fired by: EmployeeTaskLifecycleService::end() (manual)
  ├─ fired by: ClientRequestCRUDService::markCreateProceduresTaken() (manual)
  ├─ fired by: ClientRequestWorkflowService::markEndProceduresTaken() (manual)
  ├─ fired by: EmployeeTaskRequestService::markCreateTaskProceduresTaken() (manual)
  ├─ fired by: ProjectNotificationService::takeAction() (manual)
  └─ listened by: RecordInternalProcedureTaken → writes to internal_procedure_takens table

EmployeeTaskLifecycleProcessCompleted (fired by EmployeeTaskRequest::onAllProcessesCompleted)
  └─ listened by: applies lifecycle business logic (e.g., update notification, sync status)
```

### 35.4 Database Table Dependency Map

```
procedure_settings (self-referencing: parent_id, type, form, conditions, appears_*_ids)
  ├─ procedure_setting_steps (FK: procedure_setting_id, stores action_taker config)
  │    └─ action_taker_users (pivot: step_id, user_id — only for specific_user type)
  ├─ work_flows (FK: company_id, type, name — branch/default workflow scoping)
  │    └─ management_hierarchies (branch/management nodes with manager_id)
  └─ internal_procedure_takens (morph: processable_type, processable_id, procedure_setting_id)

processes (morph: processable_type, processable_id, template_snapshot, procedure_setting_id, metadata)
  └─ process_steps (FK: process_id, step_id → procedure_setting_steps.id, assigned_user_id, authorized_user_ids)

employee_task_requests (FK: procedure_setting_id, current_procedure_step_id, is_project_notification)
  └─ hasMany processes (polymorphic via processable_id)

project_notifications (FK: employee_task_request_id)
  └─ inherits workflow via employeeTask.processes
```

---

## 36. Step-by-Step Cookbook: Add Procedure Workflow to Any New Module

> **Goal**: You have a new module (e.g., `Invoice`, `WorkOrder`, `MaintenanceRequest`) and you want it to go through an approval workflow. Follow these steps **in order**. Each step has a checklist you can verify.

### Step 1: Register the Procedure Setting Type

**File**: `modules/ProcedureSetting/Enums/ProcedureSettingType.php`

```php
enum ProcedureSettingType: string
{
    // ... existing cases ...
    case Invoice = 'invoice';  // ← ADD THIS
}
```

Add `labelAr()`:
```php
self::Invoice => 'فاتورة',
```

**Checklist**:
- [ ] Enum case added with correct string value (use snake_case)
- [ ] `labelAr()` returns Arabic label
- [ ] `toDefinition()` works automatically (uses `labelAr()`)
- [ ] `values()` includes the new value automatically

### Step 2: Register Internal Process Forms (if needed)

**File**: `modules/Shared/InternalProcessType/Enums/InternalProcessForm.php`

```php
case CreateInvoice = 'createInvoice';  // ← ADD (auto-seeded by seeder)
case EndInvoice    = 'endInvoice';     // ← ADD (auto-seeded by seeder)
```

Update `applicableTypes()`:
```php
self::CreateInvoice,
self::EndInvoice => ['invoice'],
```

**Checklist**:
- [ ] Form names start with `create` or `end` for auto-seeding
- [ ] `applicableTypes()` maps the form to `['invoice']`
- [ ] `conditions()` returns `[]` for forms without conditions (default)
- [ ] `labelAr()` returns Arabic label
- [ ] `forType('invoice')` returns the forms

### Step 3: Create the Entity Model

**File**: `modules/Invoice/Models/Invoice.php`

The model must have:
- `company_id` (UUID, tenant-scoped)
- `created_by_user_id` (UUID, who created it)
- `branch_id` (nullable UUID, for branch-scoped workflow resolution)
- `status` (string, your module's status field)

Register the morph map in the model's `boot()` method:
```php
protected static function boot(): void
{
    parent::boot();
    Process::resolveRelationUsing('invoice', fn () => static::class);
}
```

Or in `Process::boot()`:
```php
Relation::morphMap([
    'invoice' => Invoice::class,
]);
```

**Checklist**:
- [ ] Model has `company_id`, `created_by_user_id`, `branch_id` fields
- [ ] Morph map registered for `processable_type = 'invoice'`
- [ ] Model uses `BelongsToTenant` trait (multi-tenancy)

### Step 4: Create the Workflow Service

**File**: `modules/Invoice/Services/InvoiceWorkflowService.php`

```php
<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Modules\Invoice\Models\Invoice;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Services\WorkflowEngine;
use Modules\Process\Models\Process;

class InvoiceWorkflowService
{
    private const TYPE = 'invoice';

    public function __construct(
        private readonly WorkflowEngine $engine,
    ) {}

    public function startForInvoice(Invoice $invoice): ?Process
    {
        $result = $this->engine->startWorkflow(
            processableType: self::TYPE,
            processableId: $invoice->id,
            type: ProcedureSettingType::Invoice->value,
            formKey: null,  // or InternalProcessForm::CreateInvoice->value
            companyId: $invoice->company_id,
            branchId: $invoice->branch_id !== null ? (string) $invoice->branch_id : null,
            createdByUserId: $invoice->created_by_user_id,
        );

        return $result->autoApprove ? null : $result->activeProcess;
    }

    public function approve(string $processStepId): void
    {
        // Delegate to ProcessWorkflowService::approveStep()
        // Or implement custom approval logic
    }

    public function reject(string $processStepId): void
    {
        // Delegate to ProcessWorkflowService::rejectStep()
    }
}
```

**Checklist**:
- [ ] Injects `WorkflowEngine`
- [ ] Uses correct `processable_type` string (`'invoice'`)
- [ ] Uses correct `ProcedureSettingType` enum value
- [ ] Passes `companyId`, `branchId`, `createdByUserId`
- [ ] Handles `autoApprove = true` (no process created → entity is auto-approved)

### Step 5: Create the Workflow Notifier (for real-time notifications)

**File**: `modules/Invoice/Services/InvoiceWorkflowNotifier.php`

```php
<?php

namespace Modules\Invoice\Services;

use Modules\Process\Contracts\WorkflowNotifier;
use Modules\Process\Models\ProcessStep;

class InvoiceWorkflowNotifier implements WorkflowNotifier
{
    public function notifyStepActivated(ProcessStep $step, array $userIds, array $context = []): void
    {
        // Broadcast your module's real-time event to $userIds
        // Example: event(new InvoiceNotification($step, $userIds));
    }

    public function inboxCountsForUser(string $userId): array
    {
        return [
            'pending_invoices' => 0,  // implement your count query
            'total' => 0,
        ];
    }
}
```

Register in the module service provider:
```php
app(WorkflowNotifierRegistry::class)->register('invoice', app(InvoiceWorkflowNotifier::class));
```

**Checklist**:
- [ ] Implements `WorkflowNotifier` interface
- [ ] Registered in module service provider
- [ ] `notifyStepActivated` broadcasts to all `$userIds`
- [ ] `inboxCountsForUser` returns correct counts (or zeros as placeholder)

### Step 6: Mark Procedures as Taken

In your entity creation service, fire `WorkflowProcedureTaken` for each `createX`-form child:

```php
use Modules\ProcedureSetting\Events\WorkflowProcedureTaken;
use Modules\ProcedureSetting\Services\WorkflowEngine;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;

private function markCreateProceduresTaken(Invoice $invoice): void
{
    $parentSetting = $this->engine->resolveParentSetting(
        ProcedureSettingType::Invoice->value,
        $invoice->company_id,
        $invoice->branch_id !== null ? (string) $invoice->branch_id : null,
    );

    if ($parentSetting === null) {
        return;
    }

    $createSettings = ProcedureSetting::query()
        ->where('parent_id', $parentSetting->id)
        ->where('form', InternalProcessForm::CreateInvoice->value)
        ->where('is_active', true)
        ->pluck('id');

    foreach ($createSettings as $settingId) {
        event(new WorkflowProcedureTaken('invoice', $invoice->id, $settingId, $invoice->created_by_user_id));
    }
}
```

**Checklist**:
- [ ] Calls `WorkflowEngine::resolveParentSetting()` to find the parent
- [ ] Queries active children with the `createX` form key
- [ ] Fires `WorkflowProcedureTaken` event for each (listener auto-records in morph table)

### Step 7: Create the Available Actions Service (if module has lifecycle actions)

**File**: `modules/Invoice/Services/InvoiceAvailableActionsService.php`

```php
<?php

namespace Modules\Invoice\Services;

use Modules\ProcedureSetting\Services\InternalProcedureAvailableActionsService;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;

final class InvoiceAvailableActionsService
{
    public function __construct(
        private readonly InternalProcedureAvailableActionsService $actionsService,
    ) {}

    public function forInvoice(string $invoiceId): array
    {
        $invoice = Invoice::query()->findOrFail($invoiceId);
        return $this->actionsService->forProcessable(
            'invoice',
            $invoice->id,
            ProcedureSettingType::Invoice->value,
            $invoice->company_id,
            $invoice->branch_id !== null ? (string) $invoice->branch_id : null,
        );
    }
}
```

**Checklist**:
- [ ] Thin wrapper around `InternalProcedureAvailableActionsService::forProcessable()`
- [ ] Resolves entity context (company_id, branch_id)
- [ ] Returns array of available actions with ordering constraints applied

### Step 8: Add the API Endpoint

In your module's routes file:

```php
Route::get('/{id}/available-actions', [InvoiceController::class, 'availableActions']);
```

In the controller:
```php
public function availableActions(string $id, InvoiceAvailableActionsService $service): JsonResponse
{
    return response()->json($service->forInvoice($id));
}
```

**Checklist**:
- [ ] Route registered with correct permission
- [ ] Controller method delegates to available-actions service
- [ ] Returns same JSON structure as EmployeeTask available-actions

### Step 9: Add Inbox Support (if module has an inbox)

Use `WorkflowEngine::pendingProcessScopeForUser()` for the inbox query:

```php
// In your repository:
public function paginatedForInbox(array $filters, string $userId, int $perPage = 15): LengthAwarePaginator
{
    $query = Invoice::query()
        ->where('company_id', tenant('id'));

    // Apply non-status filters manually (do NOT use EloquentFilter if it filters by status)
    // ...

    // Core workflow inbox filter
    $query->whereHas(
        'processes',
        $this->engine->pendingProcessScopeForUser('invoice', $userId),
    );

    return $query->paginate($perPage);
}
```

For resolving pending process descriptors:
```php
$descriptors = $this->engine->resolvePendingProcessesForUser($invoice, $userId);
// Each: {process_id, procedure_setting_id, form, mobile_inbox_action_key, pending_step_id, pending_step_order}
```

**Checklist**:
- [ ] Injects `WorkflowEngine` into repository
- [ ] Uses `pendingProcessScopeForUser()` for the core filter
- [ ] Does NOT filter by top-level `status` column (any status with a pending step shows up)
- [ ] Eager-loads `processes.procedureSetting` and `processes.steps` for descriptor resolution

### Step 10: Run the Seeder and Test

```bash
php artisan tenant:seed --class=InternalProcedureSettingsSeeder
```

This creates default child rows for all `create*` and `end*` forms under each parent category.

**Test checklist**:
- [ ] Create an invoice → process created → first step pending
- [ ] Approve step → workflow advances or completes
- [ ] Reject step → process fails (or advances if job_role)
- [ ] Available-actions API returns correct list
- [ ] Inbox query shows items with pending steps for the user
- [ ] Notifications fire to authorized users

---

## 37. Debug Guide: Troubleshooting Every Part of the Workflow

> **When something goes wrong, follow these decision trees to find the root cause.** Each section starts with the symptom, then walks through the diagnostic steps.

### 37.1 "No workflow is created when I create an entity"

```
Symptom: Entity created, but no Process record exists in DB.
  │
  ├── Did WorkflowEngine::startWorkflow() return autoApprove = true?
  │    │
  │    ├── YES → No procedure setting was found for this company/branch/type/form.
  │    │         Check:
  │    │         1. Does a parent ProcedureSetting exist for this type + company?
  │    │            Query: SELECT * FROM procedure_settings WHERE type='employee_task' AND company_id='...' AND parent_id IS NULL
  │    │         2. Does a WorkFlow exist for this company + type?
  │    │            Query: SELECT * FROM work_flows WHERE company_id='...' AND type='employee_task'
  │    │         3. If formKey is set, does a child exist with that form?
  │    │            Query: SELECT * FROM procedure_settings WHERE parent_id='...' AND form='createTask'
  │    │         4. Is the child's work_flow_id matching the resolved workflow?
  │    │
  │    └── NO → Process was created. Check if ProcessStep was created.
  │              Query: SELECT * FROM process_steps WHERE process_id='...'
  │              If empty → template_snapshot was empty (all steps resolved to 0 users).
  │              Check ActionTakerResolver::resolveUsersForStep() for each step.
  │
  └── Was startWorkflow() called at all?
       Check the entity creation service — does it inject WorkflowEngine and call startWorkflow()?
```

### 37.2 "Step resolves to zero users (skipped)"

```
Symptom: Process created but has fewer steps than the template.
  │
  ├── action_taker_type = 'specific_user'
  │    └── Check: Does the step have action_taker_users pivot records?
  │         Query: SELECT * FROM action_taker_users WHERE procedure_setting_step_id = '...'
  │         If empty → no users were assigned in the admin UI.
  │
  ├── action_taker_type = 'management_hierarchy'
  │    ├── Check: Does the creator have professionalData?
  │    │         Query: SELECT * FROM user_professional_data WHERE user_id = 'creator_id'
  │    │         If null → creator has no branch/management → cannot resolve manager.
  │    ├── Check: Does the branch/management have a manager_id?
  │    │         Query: SELECT * FROM management_hierarchies WHERE id = 'branch_id'
  │    │         If manager_id is null → no manager assigned to this hierarchy node.
  │    ├── Check: For project_manager → does context['project_id'] exist?
  │    │         If not → project_manager row is silently skipped.
  │    └── Check: action_taker_management_hierarchies JSON column — is it populated?
  │
  ├── action_taker_type = 'specific_procedures'
  │    ├── Check: Are action_taker_specific_procedure_type and _id arrays populated?
  │    ├── Check: For 'branch' type → does ManagementHierarchy.find(id) exist?
  │    ├── Check: For 'job_title' type → do any users have this job_title_id?
  │    │         Query: SELECT * FROM user_professional_data WHERE job_title_id = '...'
  │    └── Check: For 'job_role' type → id=1 needs management managers, id=2 needs branch managers
  │
  ├── action_taker_type = 'himself'
  │    └── Check: Is createdByUserId null? If so, resolver returns [].
  │
  └── action_taker_type = 'assigned_user'
       └── Check: Does the entity have a user_id? (e.g., EmployeeTaskRequest.user_id)
```

### 37.3 "User can't see items in their inbox"

```
Symptom: User should have pending items but inbox query returns empty.
  │
  ├── Is there a Process with status = 'in_progress' for this entity?
  │    Query: SELECT * FROM processes WHERE processable_id='...' AND processable_type='...' AND status='in_progress'
  │
  ├── Is there a ProcessStep with status = 'pending'?
  │    Query: SELECT * FROM process_steps WHERE process_id='...' AND status='pending'
  │
  ├── Is the user in authorized_user_ids?
  │    Query: SELECT * FROM process_steps WHERE process_id='...' AND status='pending'
  │           AND (assigned_user_id = 'user_id' OR JSON_CONTAINS(authorized_user_ids, '"user_id"'))
  │    NOTE: whereJsonContains requires quoted strings for UUIDs.
  │
  ├── Is the inbox query using WorkflowEngine::pendingProcessScopeForUser()?
  │    If not → the query may be filtering by the wrong columns.
  │
  ├── Is the inbox query filtering by top-level status?
  │    If yes → remove the status filter. The inbox should show ANY entity with a pending step,
  │    regardless of the entity's own status field.
  │
  └── Is the processable_type string correct?
       'employee_task' (NOT 'employee_task_request' — deprecated)
       'project_notification_task' (NOT 'project_notification')
       'client_request'
```

### 37.4 "Condition check fails unexpectedly"

```
Symptom: EmployeeTaskException thrown at 422 when creating/starting/ending a task.
  │
  ├── Which form is being checked?
  │    ├── createTask → EmployeeTaskFormConditionService::checkCreateTaskConditions()
  │    ├── startTask → EmployeeTaskFormConditionService::checkStartTaskConditions()
  │    └── endTask → EmployeeTaskFormConditionService::checkEndTaskConditions()
  │
  ├── Which condition is failing?
  │    Check the exception message:
  │    ├── "not_allowed_during_shift" → user is inside shift but allow_during_shift = false
  │    ├── "not_allowed_outside_shift" → user is outside shift but allow_outside_shift = false
  │    ├── "not_allowed_on_holidays" → today is a holiday but allow_on_holidays = false
  │    ├── "cannot_end_task_outside_location" → outside geofence and can_exit_outside_location = false
  │    ├── "outside_shift_time_window" → current time outside configured window
  │    ├── "employee_has_no_attendance" → no active clock-in record
  │    ├── "task_not_approved" → task.status != 'approved'
  │    ├── "has_other_open_task" → another in_progress task exists for user
  │    ├── "cannot_start_task_outside_location" → outside inside_task_location radius
  │    ├── "outside_custom_locations" → task location outside configured polygons
  │    ├── "task_duration_exceeds_limit" → duration > max_task_duration
  │    └── "task_date_too_far_in_future" → scheduled date > max_scheduled_date_offset
  │
  ├── Is the condition stored in the DB?
  │    Query: SELECT conditions FROM procedure_settings WHERE form='createTask' AND parent_id='...'
  │    Check: Is the condition key present and is_active = true?
  │
  ├── Is the condition evaluator registered?
  │    Check: EmployeeTaskServiceProvider → ConditionEvaluatorRegistry constructor
  │    The evaluator class must be registered for the condition enum value.
  │
  └── Is attendance data available?
       ConditionEvaluationService uses AttendanceConstraintService::getTodaysWorkRulesForUser()
       If no attendance constraint is assigned → current_work_period = null, is_holiday = false
```

### 37.5 "Notifications not received"

```
Symptom: Step is pending but no one gets a notification.
  │
  ├── Is WorkflowStepActivated event fired?
  │    Check: ProcessWorkflowService::createProcessStep() calls event(new WorkflowStepActivated(...))
  │    This should fire automatically when a ProcessStep is created.
  │
  ├── Is SendWorkflowStepNotification listener registered?
  │    Check: ProcedureSettingServiceProvider::registerEventListeners()
  │    Should have: Event::listen(WorkflowStepActivated::class, SendWorkflowStepNotification::class)
  │
  ├── Is a WorkflowNotifier registered for this processable_type?
  │    Check: WorkflowNotifierRegistry::get('employee_task') should return EmployeeTaskWorkflowNotifier
  │    If null → no real-time broadcast happens.
  │
  ├── Are authorized_user_ids populated?
  │    If empty → no one to notify. Check ActionTakerResolver resolution.
  │
  ├── Is notify_by_email / notify_by_sms set on the step?
  │    If false → no email/SMS sent (real-time still fires).
  │
  └── For non-Process flows (extensions/approvals):
       Are they calling dispatchStepNotifications() manually?
       They don't create ProcessStep records, so WorkflowStepActivated is NOT fired.
```

### 37.6 "Available actions API returns empty"

```
Symptom: GET /{id}/available-actions returns [].
  │
  ├── Are there any active child procedure settings?
  │    Query: SELECT * FROM procedure_settings WHERE parent_id='...' AND is_active=true AND form IS NOT NULL
  │    If empty → no children configured. Run the seeder or create via API.
  │
  ├── Are all procedures hidden by appears_after_ids?
  │    Check: If a procedure has appears_after_ids = ['A'], 'A' must be in internal_procedure_takens.
  │    Query: SELECT * FROM internal_procedure_takens WHERE processable_type='...' AND processable_id='...'
  │
  ├── Are all procedures hidden by appears_before_ids?
  │    Check: If a procedure has appears_before_ids = ['B'], and 'B' is taken → hidden.
  │
  └── Is the correct parent being resolved?
       InternalProcedureAvailableActionsService::forProcessable() calls WorkflowEngine::resolveParentSetting()
       If parent is null → no children found → returns [].
```

### 37.7 "Process completes but procedure not marked as taken"

```
Symptom: Process.status = 'completed' but internal_procedure_takens has no row.
  │
  ├── Does the Process have procedure_setting_id set?
  │    Query: SELECT procedure_setting_id FROM processes WHERE id='...'
  │    If null → the setting had no form (parent-level process). No event fires.
  │    Only child settings (form != null) get procedure_setting_id populated.
  │
  ├── Is WorkflowProcedureTaken event fired?
  │    ProcessWorkflowService::fireProcedureTakenIfApplicable() fires it when:
  │    - process.procedure_setting_id is not empty
  │    - process.status is set to Completed
  │
  ├── Is RecordInternalProcedureTaken listener registered?
  │    Check: ProcedureSettingServiceProvider::registerEventListeners()
  │
  └── Is the morph type correct?
       The event uses process.processable_type and process.processable_id.
       These must match what the available-actions service queries.
```

### 37.8 "Rejection should advance but process fails instead"

```
Symptom: Step rejected, process.status = 'failed' but expected it to advance.
  │
  ├── Check: Is the step's specific_procedure_types array containing 'job_role'?
  │    Query: SELECT template_snapshot FROM processes WHERE id='...'
  │    Look at the snapshot row for this step: specific_procedure_types
  │    If it does NOT contain 'job_role' → rejection fails the process (correct behavior).
  │    If it DOES contain 'job_role' → rejection should advance. Check ProcessWorkflowService::rejectStep().
  │
  ├── Old snapshot format?
  │    If snapshot has 'specific_procedure_type' (singular string) instead of 'specific_procedure_types' (array),
  │    backward compat reads it. Check if the value is 'job_role'.
  │
  └── Multiple targets?
       If types = ['branch', 'job_role'] → has job_role → advances.
       If types = ['branch', 'management'] → no job_role → fails.
```

---

## 38. Documentation Maintenance Protocol (MANDATORY)

> **CRITICAL**: This section defines the rules that ANY AI session or developer MUST follow when modifying the procedure workflow system. If you change ANY file listed in this document, you MUST update this document in the same session.

### 38.1 What Triggers a Documentation Update

You MUST update this document when you do ANY of the following:

| Change | Which Section to Update |
|--------|------------------------|
| Add/remove a `ProcedureSettingType` enum case | §3 (Enums), §35 (Module Dependency Map) |
| Add/remove an `InternalProcessForm` enum case | §3 (Enums), §35 (Module Dependency Map) |
| Add/remove an `InternalProcessCondition` enum case | §3 (Enums), §28 (Condition Enforcement), §34 (Condition Evaluation) |
| Add/remove a method on `WorkflowEngine` | Quick Start, Complete Public API Reference (WorkflowEngine table) |
| Add/remove a method on `ProcessWorkflowService` | Complete Public API Reference (ProcessWorkflowService table) |
| Add/remove a method on `ProcedureWorkflowService` | Complete Public API Reference (ProcedureWorkflowService table) |
| Add/remove a method on `ActionTakerResolver` | Complete Public API Reference (ActionTakerResolver table), §5 |
| Add a new module that uses workflows | §35 (Module Dependency Map), §36 (Cookbook — verify steps still work) |
| Add a new `WorkflowNotifier` | §17.4 (Where to Add Module Real-Time Notifications), §35 (Event Flow Map) |
| Change the inbox query logic | §11 (Multi-User Authorization → Inbox Queries) |
| Add a new condition evaluator | §34 (Centralized Condition Evaluation), §28 (Form Condition Enforcement) |
| Change `action_taker_type` values or resolution | §4 (Action Taker Types), §5 (ActionTakerResolver), §16 (Decision Flowcharts) |
| Add/modify a migration | §14 (Migrations) or the relevant feature section |
| Change the process creation flow | §6 (Process Creation Flow), §24 (Data Flow Diagrams) |
| Change notification broadcasting | §12 (Notification Broadcasting), §17 (Complete Notification Architecture) |
| Refactor a service's dependencies | §18 (Complete Service Dependency Map), §35 (Service Injection Map) |
| Fix a bug in the workflow engine | Add a note to the changelog at the top of this document |

### 38.2 How to Update the Changelog

At the top of this document, add a new entry in reverse-chronological order:

```markdown
> **Last updated:** YYYY-MM-DD — Brief description of what changed. See §XX.
>
> **Previous:** YYYY-MM-DD — Previous change description.
```

### 38.3 Verification Checklist Before Finishing Any Session

Before you finish a session that modified the procedure workflow system, verify:

- [ ] The changelog at the top of this document has a new entry with today's date
- [ ] Every new/changed method is documented in the API Reference table
- [ ] Every new module dependency is added to §35 (Module Dependency Map)
- [ ] Every new file is added to §20 (File Reference Index)
- [ ] Every new trap/gotcha is documented in §15 (Traps & Rules) or §21 (Additional AI Traps)
- [ ] If you added a new condition, §28 and §34 are updated
- [ ] If you changed the inbox logic, §11 (Inbox Queries) is updated
- [ ] If you added a new form, §3 (InternalProcessForm enum) and §27 (Internal Procedure Settings) are updated
- [ ] `php -l` passes on all modified PHP files
- [ ] No existing documentation contradicts your changes (if it does, update it)

### 38.4 Anti-Patterns to Avoid

- **DO NOT** create a new service that duplicates `WorkflowEngine` logic. Always inject and delegate.
- **DO NOT** write inline process queries in repositories. Use `WorkflowEngine::pendingProcessScopeForUser()`.
- **DO NOT** manually fire `WorkflowStepActivated` — it's fired automatically by `ProcessWorkflowService::createProcessStep()`.
- **DO NOT** manually call `markProcedureTaken()` for Process-based flows — `ProcessWorkflowService::fireProcedureTakenIfApplicable()` handles it on process completion.
- **DO NOT** filter the inbox by the entity's top-level `status` column. The inbox shows any entity with a pending workflow step.
- **DO NOT** confuse `ProcedureWorkflowService` (template-step, non-Process) with `ProcessWorkflowService` (runtime, Process-based). See §21.8.
- **DO NOT** forget to pass `context = ['project_id' => ...]` when the workflow uses `project_manager` action taker type. See §15.5.
- **DO NOT** update code without updating this document in the same session.

---

## 39. Quick Reference Card (One-Page Cheat Sheet)

> **Print this section.** It contains the absolute minimum knowledge needed to work with the procedure workflow system.

### 39.1 The 5 Services You Need to Know

| Service | When to Use | Key Method |
|---------|------------|------------|
| `WorkflowEngine` | Starting workflows, previewing responsibles, inbox queries, lifecycle workflows | `startWorkflow()`, `previewResponsibles()`, `pendingProcessScopeForUser()`, `resolveLifecycleSetting()` |
| `ProcessWorkflowService` | Approving/rejecting Process steps (runtime) | `approveStep($id)`, `rejectStep($id)` |
| `ProcedureWorkflowService` | Non-Process workflows (extensions, approvals), marking procedures taken | `advance()`, `markProcedureTaken()`, `resolveInternalProcedureSettingByForm()` |
| `ActionTakerResolver` | Resolving WHO can act on a step (internal — rarely called directly) | `resolveUsersForStep($step, $createdByUserId, $context)` |
| `ConditionEvaluationService` | Evaluating form conditions before workflow starts | `evaluateAndThrow($conditions, $context, $resolver)` |

### 39.2 The 4-Step Workflow Lifecycle

```
1. PREVIEW  →  WorkflowEngine::previewResponsibles(type, form, company, branch, creator, context)
2. START    →  WorkflowEngine::startWorkflow(processableType, processableId, type, form, company, branch, creator, context)
3. ACT      →  ProcessWorkflowService::approveStep(stepId)  OR  rejectStep(stepId)
4. COMPLETE →  Process.status = Completed  →  WorkflowProcedureTaken event  →  available-actions unlocks next
```

### 39.3 The 3 Action Taker Types

| Type | How Users Are Resolved | Multi-User? |
|------|----------------------|-------------|
| `specific_user` | From `action_taker_users` pivot table | Yes (any can act) |
| `management_hierarchy` | From creator's org chart (branch/management/project manager) | Yes (with deputy_manager) |
| `specific_procedures` | By branch/management/job_title/job_role | Yes (all merged) |
| `himself` | The submitter (`createdByUserId`) | No (single) |

### 39.4 The 3 Key Database Tables

| Table | Purpose |
|-------|---------|
| `procedure_settings` | Templates (parent = category, child = form-specific procedure with steps + conditions) |
| `processes` | Runtime instances (has `template_snapshot` JSON, `status`, `procedure_setting_id`) |
| `process_steps` | Runtime steps (has `assigned_user_id`, `authorized_user_ids` JSON, `status`) |

### 39.5 The 3 Critical Traps

1. **Context**: Always pass `['project_id' => $id]` if the workflow uses `project_manager`. Without it, resolution fails silently.
2. **Inbox**: Never filter by the entity's `status` column. Use `WorkflowEngine::pendingProcessScopeForUser()`.
3. **Notifications**: `actionTakers` pivot is EMPTY for non-`specific_user` types. Always use `authorized_user_ids`.

### 39.6 The 3 Files to Edit for Common Tasks

| Task | File to Edit |
|------|-------------|
| Add a new action taker type | `modules/ProcedureSetting/Services/ActionTakerResolver.php` |
| Add a new form condition | `modules/Shared/InternalProcessType/Enums/InternalProcessCondition.php` + create evaluator in `modules/EmployeeTask/Conditions/` |
| Add a new module to workflows | Follow §36 (10-step cookbook) |

### 39.7 Debug Quick Lookup

| Symptom | First Place to Check |
|---------|---------------------|
| No process created | `procedure_settings` table — does a parent + child exist for this company/type/form? |
| Step has 0 users | `ActionTakerResolver::resolveUsersForStep()` — check creator's professionalData, branch, management |
| Inbox empty | `process_steps` table — is there a pending step with the user in `authorized_user_ids`? |
| Condition fails | `procedure_settings.conditions` JSON — is the condition key present and `is_active: true`? |
| No notifications | `WorkflowNotifierRegistry` — is a notifier registered for the `processable_type`? |
| Available actions empty | `internal_procedure_takens` table — are prerequisite procedures marked as taken? |

---

## 40. Module → Type → Forms → Conditions Lookup Table

> **The one table you need.** If a weak AI reads nothing else, read this. It maps every module to its `ProcedureSettingType`, every form that module uses, and every condition on each form — marked as **pre** (precondition, checked before the form is accepted) or **in** (in-form, validates individual input fields).

### 40.1 How to Read This Table

```
Module: The module you are working in (e.g., EmployeeTask, ProjectNotification)
Type: The ProcedureSettingType enum value → string stored in procedure_settings.type
       and used as processable_type in processes table
Forms: [formKey1, formKey2, ...] — all InternalProcessForm cases for this type
Conditions per form:
  ①  condition_key          pre  ← precondition (gatekeeping: shift, location, attendance)
  ②  condition_key          in   ← in-form (validates input fields: duration, date, attachments)
  (empty) = no conditions defined for this form
```

### 40.2 EmployeeTask Module

**Type**: `employee_task`
**Morph string**: `'employee_task'`
**Controller**: `EmployeeTaskController`
**Service**: `EmployeeTaskRequestService`, `EmployeeTaskLifecycleService`, `EmployeeTaskStartRequestService`, `EmployeeTaskEndRequestService`

| # | Form Key | Label AR | Conditions |
|---|----------|----------|------------|
| 1 | `createTask` | انشاء مهمة | ① `allow_during_shift` **pre** · ② `allow_outside_shift` **pre** · ③ `allow_on_holidays` **pre** · ④ `inside_custom_locations` **pre** · ⑤ `max_task_duration` **in** · ⑥ `max_scheduled_date_offset` **in** |
| 2 | `startTask` | بدء المهمة | ① `allow_on_holidays` **pre** |
| 3 | `endTask` | انهاء المهمة | _(none)_ |

**Unregistered forms** (used as raw strings, NOT in enum):
| - | `extendTaskTime` | تمديد وقت المهمة | _(none)_ |
| - | `sendForApproval` | ارسال للاعتماد | _(none)_ |

### 40.3 ProjectNotification Module (inside Project\ProjectManagement)

**Type**: `project_notification_task`
**Morph string**: `'project_notification_task'`
**Controller**: `ProjectNotificationController`
**Service**: `ProjectNotificationService` (delegates to `EmployeeTaskRequestService` for task creation)

| # | Form Key | Label AR | Conditions |
|---|----------|----------|------------|
| 1 | `createProjectNotificationTask` | إنشاء إشعار مشروع | ① `inside_custom_locations` **pre** |
| 2 | `confirmProjectNotificationPresence` | تأكيد استلام | _(none)_ |
| 3 | `updateProjectNotificationTask` | تحديث بيانات الإشعار | ① `inside_custom_locations` **pre** |
| 4 | `updateProjectNotificationSiteStatus` | التحديث الدوري لحالة الموقع | ① `inside_task_location` **pre** |
| 5 | `projectNotificationFine` | بنود الغرامة | ① `inside_task_location` **pre** |
| 6 | `confirmProjectNotificationLocation` | تأكيد التواجد في الموقع | ① `inside_task_location` **pre** |
| 7 | `projectNotificationWorkStoppageReport` | محضر إيقاف أعمال | _(none)_ |
| 8 | `projectNotificationWorkResumption` | استئناف الأعمال | _(none)_ |
| 9 | `projectNotificationTaskPostponement` | تأجيل المهمة | _(none)_ |
| 10 | `endProjectNotificationTask` | إنهاء المهمة | ① `inside_task_location` **pre** |

### 40.4 ClientRequest Module

**Type**: `client_request`
**Morph string**: `'client_request'`
**Controller**: `ClientRequestController`
**Service**: `ClientRequestCRUDService`, `ClientRequestWorkflowService`

| # | Form Key | Label AR | Conditions |
|---|----------|----------|------------|
| 1 | `createClientRequest` | إنشاء طلب عميل | _(none)_ |
| 2 | `endClientRequest` | انهاء طلب عميل | _(none)_ |
| 3 | `attachAttachments` | ارفاق مرفقات | ① `max_attachments` **in** |

### 40.5 PriceOffer Module (enum registered, not yet implemented)

**Type**: `price_offer`
**Morph string**: `'price_offer'` _(when implemented)_

| # | Form Key | Label AR | Conditions |
|---|----------|----------|------------|
| 1 | `createPriceOffer` | إنشاء عرض سعر | _(none)_ |
| 2 | `endPriceOffer` | انهاء عرض سعر | _(none)_ |

### 40.6 Contract Module (enum registered, not yet implemented)

**Type**: `contract`
**Morph string**: `'contract'` _(when implemented)_

| # | Form Key | Label AR | Conditions |
|---|----------|----------|------------|
| 1 | `createContract` | إنشاء عقد | _(none)_ |
| 2 | `endContract` | انهاء عقد | _(none)_ |

### 40.7 Meeting Module (enum registered, not yet implemented)

**Type**: `meeting`
**Morph string**: `'meeting'` _(when implemented)_

| # | Form Key | Label AR | Conditions |
|---|----------|----------|------------|
| 1 | `createMeeting` | إنشاء اجتماع | _(none)_ |
| 2 | `endMeeting` | انهاء اجتماع | _(none)_ |

### 40.8 All Conditions Reference (what each condition does)

| Condition Key | Group | Category | Label AR | What It Checks | Settings |
|---|---|---|---|---|---|
| `allow_during_shift` | **pre** | shift | موظف داخل الدوام | User must be inside scheduled shift period | `mode` (shift/specific_time), `start_time`, `end_time` |
| `allow_outside_shift` | **pre** | location | موظف خارج موقع الدوام | User must be outside shift (at task location) | _(none)_ |
| `allow_on_holidays` | **pre** | shift | مسموح في العطلات | Action allowed on holidays when active | _(none)_ |
| `inside_custom_locations` | **pre** | location | موقع المهمة داخل المناطق المخصصة | Task GPS must be inside configured polygons | `polygons` (map areas) |
| `inside_task_location` | **pre** | location | داخل موقع المهمة | User's current GPS within `radius_meters` of task location | `radius_meters` (default 100) |
| `inside_shift_time` | **pre** | time | داخل وقت الدوام | Current server time within `[start_time − tolerance, end_time − tolerance]` | `start_time`, `end_time`, `allow_before_start_minutes`, `allow_before_end_minutes` |
| `employee_has_attendance` | **pre** | attendance | الموظف مسجل حضور | User must have active clock-in (clock_out IS NULL) | _(none)_ |
| `task_is_approved` | **pre** | task_status | المهمة معتمدة | `task.status` must equal `approved` | _(none)_ |
| `no_open_task` | **pre** | open_task | لا يوجد مهمة مفتوحة | No other task for user with `status = in_progress` | _(none)_ |
| `can_exit_outside_location` | **pre** | location | يستطيع الخروج خارج الموقع | If false, employee must be within task geofence to end | _(none)_ |
| `must_be_in_location` | **pre** | location | يجب أن يكون داخل الموقع عند البدء | User must be at task location to start | _(none)_ |
| `max_task_duration` | **in** | duration | الحد الأقصى لمدة المهمة | Task duration ≤ `max_hours` | `max_hours` (default 8) |
| `max_scheduled_date_offset` | **in** | calendar | الحد الأقصى لتاريخ المهمة | Task date ≤ today + `max_days` (or ≤ contract end date) | `mode` (max_task_date/end_contract), `max_days` (default 30) |
| `has_task_duration` | **in** | duration | مدة المهمة | Task must have a duration set | _(none)_ |
| `max_duration_hours` | **in** | duration | أقصى مدة بالساعات | Duration ≤ configured max hours | integer value |
| `max_attachments` | **in** | attachment | أقصى عدد مرفقات | Attachment count ≤ configured max | integer value |

### 40.9 "I want to apply procedure to my module" — Quick Decision

```
Q: What module am I working in?
  │
  ├── EmployeeTask (regular tasks)
  │    → type = 'employee_task'
  │    → forms = [createTask, startTask, endTask, extendTaskTime, sendForApproval]
  │    → controller = EmployeeTaskController
  │    → service = EmployeeTaskRequestService
  │    → condition service = EmployeeTaskFormConditionService
  │
  ├── ProjectNotification (maintenance/emergency tasks)
  │    → type = 'project_notification_task'
  │    → forms = [createProjectNotificationTask, confirmProjectNotificationPresence,
  │               updateProjectNotificationTask, updateProjectNotificationSiteStatus,
  │               projectNotificationFine, confirmProjectNotificationLocation,
  │               projectNotificationWorkStoppageReport, projectNotificationWorkResumption,
  │               projectNotificationTaskPostponement, endProjectNotificationTask]
  │    → controller = ProjectNotificationController
  │    → service = ProjectNotificationService (injects WorkflowEngine)
  │    → condition service = EmployeeTaskFormConditionService (shared)
  │
  ├── ClientRequest
  │    → type = 'client_request'
  │    → forms = [createClientRequest, endClientRequest, attachAttachments]
  │    → controller = ClientRequestController
  │    → service = ClientRequestCRUDService + ClientRequestWorkflowService
  │
  ├── PriceOffer / Contract / Meeting
  │    → type = 'price_offer' / 'contract' / 'meeting'
  │    → forms = [create*, end*] (enum registered, services NOT yet implemented)
  │    → Follow §36 cookbook to implement
  │
  └── New module (not yet registered)
       → Follow §36 (10-step cookbook) from scratch
```

### 40.10 "I want to add a condition to a form" — Quick Decision

```
Q: Is the condition a GATEKEEPER (checked before the form is accepted)?
  │
  ├── YES → formGroup = 'precondition'
  │    Examples: shift checks, location checks, attendance checks, task status checks
  │    The condition runs BEFORE WorkflowEngine::startWorkflow()
  │    If it fails → HTTP 422, no Process is created
  │
  └── NO → formGroup = 'in_form'
       Examples: max duration, max date offset, max attachments
       The condition validates individual form INPUT FIELDS
       Backend enforcement varies (some are backend-enforced, some are client-enforced)

Q: Which form am I adding the condition to?
  │
  ├── createTask → add to InternalProcessForm::CreateTask::conditions()
  ├── startTask → add to InternalProcessForm::StartTask::conditions()
  ├── endTask → add to InternalProcessForm::EndTask::conditions()
  ├── any project_notification form → add to that form's conditions() entry
  └── any other form → add to that form's conditions() entry

Q: What do I need to do? (7 steps — see §28.6 / §34.9 for full details)
  1. Add enum case to InternalProcessCondition (with category(), labelAr(), formGroup(), settingsSchema())
  2. Register in InternalProcessForm::conditions() for the target form
  3. Add exception factory to EmployeeTaskException
  4. Create evaluator class implementing ConditionEvaluator
  5. Register evaluator in EmployeeTaskServiceProvider
  6. Add exception case to EmployeeTaskExceptionResolver::throwFromResult()
  7. Update §40.8 (this table) with the new condition
```
