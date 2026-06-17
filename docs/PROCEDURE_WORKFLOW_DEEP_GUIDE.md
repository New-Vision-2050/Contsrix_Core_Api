# Procedure Workflow Deep Guide

> Comprehensive implementation reference for AI assistants and developers.

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
| `advance($currentStepId, $procedureSettingId, $userId, $createdByUserId = null, $context = [])` | `ProcedureWorkflowResult` | `?int`, `?string`, `string`, `?string`, `array` | EmployeeTaskExtensionWorkflowService, EmployeeTaskApprovalService, EmployeeTaskRequestService |
| `assertCanReject($currentStepId, $userId, $createdByUserId = null, $context = [])` | `void` | Same as advance | EmployeeTaskExtensionWorkflowService, EmployeeTaskApprovalService, EmployeeTaskRequestService |
| `getApprovalResponsibles($procedureType, $createdByUserId = null, $context = [], $formKey = null)` | `array{auto_approve: bool, step: ?array, action_takers: array}` | `string`, `?string`, `array`, `?string` | ProcedureSettingController; delegates to `WorkflowEngine::previewResponsibles()` |
| `userCanActOnStep($step, $userId, $createdByUserId = null, $context = [])` | `bool` | `ProcedureSettingStep`, `string`, `?string`, `array` | Inbox filtering (read-only check) |
| `resolveActionTakerUserIdsForStep($step, $createdByUserId = null, $context = [])` | `array<string>` | `ProcedureSettingStep`, `?string`, `array` | Broadcasting (EmployeeTask services) |
| `resolveProcedureSettingForBranch($procedureType, $companyId, $branchId)` | `?ProcedureSetting` | `string`, `string`, `?string` | Delegates to `WorkflowEngine::resolveParentSetting()` |
| `resolveInternalProcedureSettingByForm($procedureCategoryType, $formKey, $companyId, $branchId = null)` | `?ProcedureSetting` | `string`, `string`, `string`, `?string` | EmployeeTask extension/approval creation; delegates to `WorkflowEngine::resolveSettingsForEntry()` |

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
| `create($dto)` | `EmployeeTaskRequest` | Resolves creator branch, previews via `WorkflowEngine`, starts workflow via `WorkflowEngine`, central event broadcasts |
| `approve($id, $adminId)` | `EmployeeTaskRequest` | findPendingStepForActor + advance with context |
| `reject($id, $adminId, $reason)` | `EmployeeTaskRequest` | findPendingStepForActor + assertCanReject with context |
| `broadcastTaskNotification($task, $currentStep, $userIds = [])` | `void` | **CHANGED**: now takes `array $userIds` instead of deriving from actionTakers |
| `broadcastInboxCounts($userIds, $filters = [])` | `void` | **CHANGED**: now takes `array $userIds` instead of `ProcedureSettingStep` |

### EmployeeTaskExtensionWorkflowService

| Method | Returns |
|--------|---------|
| `approve($extensionId, $adminId, $approvalNotes = null)` | `EmployeeTaskExtensionRequest` |
| `reject($extensionId, $adminId, $rejectionReason)` | `EmployeeTaskExtensionRequest` |

### EmployeeTaskExtensionService

| Method | Returns | Key Logic |
|--------|---------|-----------|
| `create($dto)` | `EmployeeTaskExtensionRequest` | Inherits procedure from parent task, resolves users with context, broadcasts |

### EmployeeTaskApprovalService

| Method | Returns | Key Logic |
|--------|---------|-----------|
| `create($taskId, $userId, $notes, $file)` | `EmployeeTaskApprovalRequest` | Resolves first step users with context, broadcasts to resolved IDs |
| `approve($approvalId, $adminId, $approvalNotes)` | `EmployeeTaskApprovalRequest` | advance with context |
| `reject($approvalId, $adminId, $rejectionReason)` | `EmployeeTaskApprovalRequest` | assertCanReject with context |

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
    case SpecificUser = 'specific_user';
    case ManagementHierarchy = 'management_hierarchy';
    case SpecificProcedures = 'specific_procedures';
}

enum ActionTakerManagementHierarchyType: string
{
    case BranchManager = 'branch_manager';
    case ManagementManager = 'management_manager';
    case ProjectManager = 'project_manager';
}

enum ActionTakerSpecificProcedureType: string
{
    case Branch = 'branch';       // Manager of specific branch_id
    case Management = 'management'; // Manager of specific management_id
    case JobTitle = 'job_title';   // ALL users with job_title_id
    case JobRole = 'job_role';     // 1 = all mgmt managers, 2 = all branch managers
}

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
    case CreateClientRequest = 'createClientRequest';
    case CreatePriceOffer    = 'createPriceOffer';
    case CreateContract      = 'createContract';
    case CreateMeeting       = 'createMeeting';
    case CreateTask          = 'createTask';
    case StartTask           = 'startTask';
    case AssignOtherEmployee = 'assignOtherEmployee';
    case ExtendTaskTime      = 'extendTaskTime';
    case SendForApproval     = 'sendForApproval';
    case CancelTask          = 'cancelTask';
    case ConfirmLocation     = 'confirmLocation';
    case EndTask             = 'endTask';
    case AttachAttachments   = 'attachAttachments';

    public function applicableTypes(): array
    {
        return match ($this) {
            self::CreateClientRequest => ['client_request'],
            self::CreatePriceOffer    => ['price_offer'],
            self::CreateContract      => ['contract'],
            self::CreateMeeting       => ['meeting'],
            self::CreateTask,
            self::StartTask,
            self::ExtendTaskTime,
            self::ConfirmLocation,
            self::AssignOtherEmployee,
            self::EndTask             => ['employee_task'],
            self::CancelTask,
            self::SendForApproval     => ['employee_task', 'client_request'],
            self::AttachAttachments   => ['employee_task', 'client_request', 'price_offer', 'contract'],
        };
    }

    public static function forType(string $procedureType): array;
    public function labelAr(): string;
    public function conditions(): array;
    public function toDefinition(): array;
    public static function values(): array;
}

enum InternalProcessCondition: string
{
    case AllowDuringShift   = 'AllowDuringShift';
    case AllowOutsideShift  = 'AllowOutsideShift';
    case AllowOnHolidays    = 'AllowOnHolidays';
    case ApplyToAllBranches = 'ApplyToAllBranches';
    case HasTaskDuration    = 'HasTaskDuration';
    case MaxDurationHours   = 'MaxDurationHours';
    case MaxAttachments     = 'MaxAttachments';
}
```

---

## 4. Action Taker Types

### 4.1 `specific_user`
- Step has explicit `actionTakers` pivot records with `user_id`.
- Any listed user can act. One acting advances the step.
- **Trap**: `actionTakers` is empty for other types. Code that iterates it without fallback fails silently.

### 4.2 `management_hierarchy`
- Assigned user resolved from **CREATOR'S** org chart.
- Chain: creator → `UserProfessionalData` → `branch_id`/`management_id` → `ManagementHierarchy` → `manager_id`.
- If any link fails → fallback to `action_taker_alternative_management_hierarchy_type`.
- **Project Manager**: If type is `project_manager`, reads `context['project_id']` → `ProjectManagement.find(project_id).manager_id`. If no context or no manager → fallback.

### 4.3 `specific_procedures`
- No explicit `actionTakers`. Resolved dynamically.

| Sub-type | Resolution |
|----------|-----------|
| `branch` | `ManagementHierarchy.find(id).manager_id` |
| `management` | `ManagementHierarchy.find(id).manager_id` |
| `job_title` | ALL users where `professionalData.job_title_id = id` |
| `job_role` | `id=1` → all management managers; `id=2` → all branch managers |

**Rejection Behavior**:
- `job_role`: Rejection **advances** the workflow (does NOT fail).
- `job_title`, `branch`, `management`: Rejection **fails** the process.
- All other types: Rejection fails the process.

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
- `appears_before_id` / `appears_after_id` (ordering constraints)
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

Returns all active child internal procedure settings for the task, ordered by constraints:

```json
[
  {
    "id": "child-uuid-1",
    "name": "تأكيد دخول الموقع",
    "form": { "key": "confirmLocation", "label_ar": "تأكيد الموقع" },
    "conditions": [...],
    "appears_before_id": "child-uuid-2",
    "sort_order": 1
  },
  {
    "id": "child-uuid-2",
    "name": "تأكيد خروج الموقع",
    "form": { "key": "confirmLocation", "label_ar": "تأكيد الموقع" },
    "conditions": [...],
    "appears_after_id": "child-uuid-1",
    "sort_order": 2
  }
]
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
- Each child has its own `name`, `steps`, `conditions`, `appears_before_id`, `appears_after_id`, `sort_order`

#### Enum Changes
- `ProcedureSettingType` simplified to categories only (`employee_task`, `client_request`, `price_offer`, `contract`, `meeting`)
- Removed: `EmployeeTaskRequest`, `EmployeeTaskExtension`, `EmployeeTaskCompletionApproval` cases
- `InternalProcessForm` enum added under `Modules\Shared\InternalProcessType\Enums` with `applicableTypes()`, `forType()`, `labelAr()`, `conditions()`, `toDefinition()`, and `values()`
- `InternalProcessCondition` enum added for per-form condition definitions

#### Database Changes
- Added to `procedure_settings`: `parent_id` (UUID, nullable, FK to self), `form` (string, nullable), `conditions` (JSON, nullable), `appears_before_id` (UUID, nullable), `appears_after_id` (UUID, nullable)
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
| **appears_before_id** | Ordering constraint: this child must appear BEFORE the referenced child in available-actions. |
| **appears_after_id** | Ordering constraint: this child must appear AFTER the referenced child in available-actions. |
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
| `appears_before_id` | This child must appear BEFORE the referenced child |
| `appears_after_id` | This child must appear AFTER the referenced child |
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
  "appears_before_id": null,
  "appears_after_id": null,
  "sort_order": 1,
  "is_active": true
}

Response: { "id": "auto-generated-child-uuid", ... }
```

- **`name`**: Display name (e.g., "بدء مهمة العمل")
- **`form`**: Must be a valid `InternalProcessForm` case (e.g., `startTask`)
- **`conditions`**: JSON array of condition objects
- **`appears_before_id`** / **`appears_after_id`**: Ordering constraints (optional)
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

`InternalProcedureSettingsSeeder` auto-creates default children under each parent category:
- `employee_task`: createTask, startTask, extendTaskTime, sendForApproval, cancelTask, confirmLocation, assignOtherEmployee, attachAttachments
- Other categories: no defaults (extendable in future)

### 27.8 Conditions Schema

Each condition is stored as a JSON object:

```json
[
  { "key": "AllowDuringShift", "value": true },
  { "key": "ApplyToAllBranches", "value": false },
  { "key": "MaxDurationHours", "value": 8 }
]
```

The `key` must match an `InternalProcessCondition` case. The `value` type depends on the condition:
- Boolean conditions: `true`/`false`
- Number conditions: integer

Conditions are read by the frontend/mobile app. The backend does NOT enforce them (they are UI/UX hints).

### 27.9 Ordering Constraints

`appears_before_id` and `appears_after_id` create a DAG of ordering:

```
Child B (appears_before_id = Child C.id)
Child C (appears_after_id = Child B.id)
```

This means: Child B must appear BEFORE Child C in the available-actions list. The `sort_order` column is a fallback.

### 27.10 Traps

#### 27.10.1 `resolveInternalProcedureSettingByForm` Returns First Match

If multiple children share the same form, this method returns only the first one. Always prefer explicit ID resolution for user-triggered actions.

#### 27.10.2 Parent Steps vs Child Steps

Parent rows can have steps too (for Process-based workflows like task creation). Child rows have their own steps (for non-Process workflows like extensions/approvals). Do not confuse them.

#### 27.10.3 Conditions Are UI Hints

The `conditions` JSON is stored and returned to clients but is NOT validated or enforced by the backend. The mobile/frontend app must read and apply them.

#### 27.10.4 `form` Must Match `InternalProcessForm` Cases

When creating a child, the `form` value must be a valid `InternalProcessForm` case. Invalid values are rejected at the API level.

