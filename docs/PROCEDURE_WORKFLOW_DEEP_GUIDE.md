# Procedure Workflow Deep Guide

> Comprehensive implementation reference for AI assistants and developers.
>
> **Last updated:** 2026-06-21 — Multi-module centralized taken-status expansion (§30); `appears_after_id` / `appears_before_id` changed from single UUID to **JSON arrays** supporting multiple prerequisites (§27.9, §30.5); migration `2026_06_21_000001` converts existing rows automatically.

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
- `WorkflowActionRequired` notification sends mail (via `toMail()`) and SMS (via `toSms()`).

**For Process-based workflows** (EmployeeTaskRequest, ClientRequest):
- `ProcessWorkflowService::createProcessStep()` automatically fires `WorkflowStepActivated`.
- The listener reads `notify_by_email` and `notify_by_sms` flags from `ProcedureSettingStep`.
- Real-time behavior is module-specific through the registered `WorkflowNotifier`.
- Do not manually broadcast in the create path or notifications will be duplicated.

**For non-Process workflows** (EmployeeTask extensions/approvals):
- These call `dispatchStepNotifications()` directly since they don't create `ProcessStep` records.
- Located in `EmployeeTaskExtensionService::create()` and `EmployeeTaskApprovalService::create()`.

### To Customize Email Content
1. Edit `resources/views/emails/workflowActionRequired.blade.php`.
2. The notification passes `stepName` and `stepOrder` variables.

### To Customize SMS Content
1. Edit `Modules\ProcedureSetting\Notifications\WorkflowActionRequired::toSms()`.
2. Uses `MoraSms` driver by default. Country-specific driver resolution is supported.

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

1. `createTask` — checks `allow_during_shift`, `allow_outside_shift`, `allow_on_holidays` against the user's current attendance work-rules (via `AttendanceConstraintService::getTodaysWorkRulesForUser()`).
2. `endTask` — checks `can_exit_outside_location` against the task geofence (via `EmployeeTaskLocationService::isWithinTaskRadius()`).
3. `startTask` — no conditions defined; check is skipped entirely.

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
| `startWorkflow($processableType, $processableId, $type, $formKey, $companyId, $branchId, $createdByUserId = null, $context = [])` | `WorkflowStartResult` | `string`, `string`, `string`, `?string`, `string`, `?string`, `?string`, `array` | EmployeeTaskRequestService, ClientRequestWorkflowService |

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
| `checkCreateTaskConditions($userId, $companyId, $branchId)` | `void` | `string`, `string`, `?string` | `EmployeeTaskRequestService::create()` |
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
    //   CreateTask        → AllowDuringShift, AllowOutsideShift, AllowOnHolidays
    //   StartTask         → [] (shift-period enforcement is owned by the Attendance
    //                         module's constraint system, not the procedure form)
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
    case HasTaskDuration        = 'has_task_duration';
    case MaxDurationHours       = 'max_duration_hours';
    case MaxAttachments         = 'max_attachments';
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
  'management_hierarchy'  → resolveManagementHierarchyUsers() → [single_id]
  'specific_procedures'   → resolveSpecificProcedureUsers()   → [many_ids...]
  default                 → resolveSpecificUserIds()          → [from actionTakers]
```

### Fallback Chain

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
| `createTask` | Always taken (the task itself exists) | `EmployeeTaskRequestService::create()` calls `markProcedureTaken()` for all active `createTask` procedures |
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
| `createTask` | `EmployeeTaskRequestService::markCreateTaskProceduresTaken()` on every task creation |
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

### 28.4 startTask

`InternalProcessForm::StartTask` has `conditions() = []` (empty). No condition check is performed for start. Timing enforcement for task starts is owned by the Attendance module's constraint system.

### 28.5 Exceptions Thrown

| Exception method | HTTP | Message |
|-----------------|------|---------|
| `EmployeeTaskException::notAllowedDuringShift()` | 422 | "This action is not allowed while you are within a work shift." |
| `EmployeeTaskException::notAllowedOutsideShift()` | 422 | "This action is only allowed during an active work shift." |
| `EmployeeTaskException::notAllowedOnHolidays()` | 422 | "This action is not allowed on holidays or non-working days." |
| `EmployeeTaskException::cannotEndTaskOutsideLocation()` | 422 | "You must be within the task location to end this task." |

### 28.6 How to Add Condition Enforcement to a New Form

1. Add the condition case to `InternalProcessCondition` if new.
2. Return it from `InternalProcessForm::conditions()` for the target form.
3. Update `InternalProcessCondition::defaultValuesForForm()` with sensible defaults.
4. Add a new `check*Conditions()` method to `EmployeeTaskFormConditionService` (or create a new service for a different module).
5. Call it from the relevant service method, **before** any workflow API call.
6. Add a corresponding `EmployeeTaskException::*()` factory for the HTTP 422 response.
7. Update §27.10.3 to document the new backend-enforced condition.

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

