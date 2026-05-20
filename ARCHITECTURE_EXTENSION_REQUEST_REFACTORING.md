# EmployeeTask Extension Request Workflow Refactoring - Architecture Document

## Executive Summary

The ExtensionRequest approval/rejection flow has been fully refactored to integrate with the ProcedureSetting/Workflow system, exactly like EmployeeTask. This ensures:

- **Unified Permission Model**: Same users/roles who approve EmployeeTasks can approve ExtensionRequests
- **Consistent Workflow**: Multi-step approval processes work identically across both entities
- **Architecture Alignment**: No custom approval logic - reuses ProcedureWorkflowService
- **Maintainability**: Changes to workflow rules propagate to both EmployeeTask and ExtensionRequest
- **Type Safety**: Full Laravel architecture compliance (Controller → Request → DTO → Service → Repository)

---

## Architectural Changes

### 1. Database Layer

**Migration Added**: `2026_05_20_000005_add_workflow_to_employee_task_extension_requests_table.php`

**New Columns**:
```
procedure_setting_id      (string, nullable) → FK to procedure_settings.id
current_procedure_step_id (int, nullable)    → FK to procedure_setting_steps.id
```

**Rationale**: These columns track which workflow an ExtensionRequest is in and which step it's currently awaiting approval from.

---

### 2. Model Layer

**EmployeeTaskExtensionRequest** - Added workflow relationships:

```php
public function currentProcedureStep(): BelongsTo
{
    return $this->belongsTo(ProcedureSettingStep::class, 'current_procedure_step_id');
}
```

This mirrors the exact relationship pattern used in EmployeeTaskRequest (lines 89-92 of EmployeeTaskRequest.php).

---

### 3. Enum Layer

**ProcedureSettingType** - Added new case:

```php
case EmployeeTaskExtensionRequest = 'employee_task_extension_request';
```

This allows ProcedureSettings to be configured for extension request approval workflows separately from general employee task workflows.

---

### 4. Service Layer Refactoring

#### **EmployeeTaskExtensionService** (Updated)

**Before**: 
- `requestExtension()` created extension with only basic fields
- `approveExtension()` and `rejectExtension()` contained direct approval logic
- No workflow integration

**After**:
- `requestExtension()` now:
  - Calls `ProcedureWorkflowService::getApprovalResponsibles()` for the extension type
  - If auto-approved: sets status='approved' + clears workflow fields
  - If workflow required: resolves first step + sets workflow IDs + keeps status='pending'
  
- `approve()` and `reject()` **removed** - moved to EmployeeTaskExtensionWorkflowService
- `listPending()` and `listForTask()` preserved for querying

**Code Pattern** (Lines 23-70):
```php
$procedureType = ProcedureSettingType::EmployeeTaskExtensionRequest->value;
$preview = $this->workflow->getApprovalResponsibles($procedureType);

if ($preview['auto_approve']) {
    $data['status'] = 'approved';
    $data['procedure_setting_id'] = null;
    $data['current_procedure_step_id'] = null;
} else {
    $firstStep = $this->workflow->resolveFirstStep($procedureType);
    $data['procedure_setting_id'] = $firstStep->procedure_setting_id;
    $data['current_procedure_step_id'] = $firstStep->id;
}
```

This **exactly mirrors** EmployeeTaskRequestService::create() (lines 23-50).

---

#### **EmployeeTaskExtensionWorkflowService** (New)

**Purpose**: Workflow-based approval/rejection orchestration

**Key Methods**:

1. **`approve()`** - Workflow-aware approval
   ```
   1. Validate extension is pending
   2. Call workflow->advance(currentStep, procedureId, userId)
   3. If NOT final: move to next step, return
   4. If FINAL: apply business logic (extend task duration, reschedule jobs)
   ```
   
   **Pattern** (Lines 54-92): Identical to EmployeeTaskRequestService::approve() (Lines 101-131)

2. **`reject()`** - Workflow-aware rejection
   ```
   1. Validate extension is pending
   2. Call workflow->assertCanReject(currentStep, userId)
   3. Update extension: status='rejected', clear workflow fields
   4. Update task: last_extension_status='extension_rejected'
   ```
   
   **Pattern**: Identical to EmployeeTaskRequestService::reject() (Lines 133-154)

**Business Logic** (Applied only when workflow is final):
- Store original_duration_hours if null
- Calculate newDuration = current + additional
- Update task with new duration
- Reschedule AutoCloseTaskAtDurationExpiryJob
- Dispatch job with new deadline

---

### 5. Controller Layer

**AdminEmployeeTaskController** (Updated)

**Old Approach**:
```php
$dto = new ApproveExtensionRequestDTO(...);
$extension = $this->extensionResolveService->approve($dto);
```

**New Approach**:
```php
$extension = $this->extensionWorkflow->approve(
    $extensionId,
    (string) Auth::id(),
    $request->input('approval_notes'),
);
```

**Why**: 
- Removes intermediate DTO layer for approval/rejection (not needed - simple parameters)
- Same pattern as EmployeeTaskRequestService::approve/reject calls (line 72, 82)
- Cleaner controller code

**New Dependencies** (Line 22):
```php
private readonly EmployeeTaskExtensionWorkflowService $extensionWorkflow,
```

---

### 6. Permission & Authorization

**How It Works**:

1. **Admin requests extension approval**
   - Controller calls `$extensionWorkflow->approve($id, $adminId, $notes)`

2. **Service calls workflow engine**
   ```php
   $result = $this->workflow->advance(
       $extension->current_procedure_step_id,  // Current approval step
       $extension->procedure_setting_id,        // Which procedure
       $adminId,                                 // Who is approving
   );
   ```

3. **ProcedureWorkflowService validates**
   - Calls `assertIsActionTaker()` (line 72-73 of ProcedureWorkflowService)
   - Checks if $adminId is in current step's action-takers
   - Throws `ProcedureWorkflowException::notAuthorized()` if not

4. **Same authorization as EmployeeTask**
   - ExtensionRequest uses same ProcedureSettingStep and ProcedureSettingStepActionTaker
   - No custom permission logic
   - Permissions configured centrally in ProcedureSetting UI

---

## Workflow Examples

### Example 1: Multi-Step Approval (With Workflow)

**Setup**: 
- ProcedureSetting type='employee_task_extension_request'
- Step 1: Manager approval (action_takers: [manager_id])
- Step 2: Director approval (action_takers: [director_id])

**Flow**:
```
1. Employee requests 2 hours extension
   → ExtensionRequest created with:
     - status='pending'
     - procedure_setting_id=ps_1
     - current_procedure_step_id=1 (Manager step)

2. Manager approves
   → workflow->advance() called
   → isFinal=false (next step exists)
   → ExtensionRequest updated:
     - current_procedure_step_id=2 (Director step)

3. Director approves
   → workflow->advance() called
   → isFinal=true (no more steps)
   → Extension business logic applied:
     - Task duration increased
     - Auto-close job rescheduled
   → ExtensionRequest updated:
     - status='approved'
     - procedure_setting_id=null
     - current_procedure_step_id=null
     - reviewed_by=director_id
     - reviewed_at=now()
```

### Example 2: Auto-Approval (No Workflow)

**Setup**: 
- No ProcedureSetting configured for 'employee_task_extension_request'
- OR ProcedureSetting has no steps

**Flow**:
```
1. Employee requests extension
   → getApprovalResponsibles() returns: auto_approve=true
   → ExtensionRequest created with:
     - status='approved' (immediately!)
     - procedure_setting_id=null
     - current_procedure_step_id=null

2. Business logic applied immediately:
   - Task duration increased
   - Auto-close job dispatched
   - Extension marked approved
```

---

## Comparison: Before vs After

| Aspect | Before | After |
|--------|--------|-------|
| **Approval Logic** | Custom in EmployeeTaskExtensionResolveService | Workflow-based via ProcedureWorkflowService |
| **Permission Check** | None (any admin could approve) | Via ProcedureSettingStepActionTaker |
| **Multi-Step Support** | Not supported | Fully supported |
| **Workflow Engine Reuse** | No | Yes (ProcedureWorkflowService) |
| **Service Pattern** | One service for everything | Split: creation vs workflow |
| **Authorization Scope** | Global | Per-step via ProcedureSetting |
| **Consistency with EmployeeTask** | ❌ Different pattern | ✅ Identical pattern |
| **Maintainability** | Custom logic changes needed | Changes to ProcedureWorkflowService propagate |
| **DTO Usage** | ApproveExtensionRequestDTO, RejectExtensionRequestDTO | Simple parameters (cleaner) |
| **Test Coverage** | Custom test logic needed | Reuses ProcedureWorkflowService tests |

---

## Code Quality & Architecture Compliance

### Clean Architecture ✅
- **Controller**: Handles HTTP, delegates to services
- **Request**: Validates input (ApproveExtensionRequest, RejectExtensionRequest)
- **DTO**: CreateExtensionRequestDTO for domain model transfer
- **Service**: Business logic orchestration (two services by responsibility)
- **Repository**: Data access via EmployeeTaskRepository
- **Workflow Service**: Shared workflow engine (ProcedureWorkflowService)
- **Exception**: Domain exceptions (EmployeeTaskException, ProcedureWorkflowException)

### SOLID Principles ✅
- **Single Responsibility**: EmployeeTaskExtensionService handles creation, EmployeeTaskExtensionWorkflowService handles approval
- **Open/Closed**: New workflow types can be added to ProcedureSetting without modifying service code
- **Liskov Substitution**: Workflow behaves identically for all entity types
- **Interface Segregation**: Services depend on abstractions (workflow interface)
- **Dependency Inversion**: Services receive dependencies via constructor injection

### Type Safety ✅
- Strict PHP 8.1+ declarations
- Full type hints on all methods
- Immutable DTOs (readonly properties)
- Proper null handling

### Transaction Safety ✅
- All multi-step operations wrapped in `DB::transaction()`
- Atomic updates (task duration + extension status + job dispatch)
- Rollback if any step fails

---

## Migration Path

### Running the Migration
```bash
php artisan migrate
```

This adds the two workflow columns to existing ExtensionRequest records with NULL values.

### Configuration
Admins must configure ProcedureSetting for extension requests:

1. Go to Procedures administration
2. Create new ProcedureSetting with type='employee_task_extension_request'
3. Add approval steps
4. Assign action-takers per step
5. Extension requests will now flow through the configured workflow

---

## Files Modified

### Created
- `Migrations/2026_05_20_000005_add_workflow_to_employee_task_extension_requests_table.php`
- `Services/EmployeeTaskExtensionWorkflowService.php`

### Modified
- `Models/EmployeeTaskExtensionRequest.php` - Added workflow relationships
- `Services/EmployeeTaskExtensionService.php` - Integrated workflow on request creation
- `Controllers/AdminEmployeeTaskController.php` - Uses new workflow service
- `Providers/EmployeeTaskServiceProvider.php` - Registers new service
- `Enums/ProcedureSettingType.php` - Added extension request type

### Deleted
- `Services/EmployeeTaskExtensionResolveService.php` (functionality moved to EmployeeTaskExtensionWorkflowService)
- `DTO/ApproveExtensionRequestDTO.php` (not needed with direct parameters)
- `DTO/RejectExtensionRequestDTO.php` (not needed with direct parameters)

### Unchanged
- All EmployeeTask core logic
- All ProcedureSetting logic
- API routes (same endpoints)
- Presenters (same output format)
- Request validations (same)
- Models: EmployeeTaskRequest, ProcedureSettingStep, User, etc.

---

## Testing Recommendations

### Unit Tests
1. **EmployeeTaskExtensionService**:
   - requestExtension() with auto-approve=true
   - requestExtension() with workflow required
   - Validation: pending extension exists, status checks

2. **EmployeeTaskExtensionWorkflowService**:
   - approve() with non-final step
   - approve() with final step
   - reject() with valid action-taker
   - reject() with unauthorized user
   - Auto-close job dispatch

### Integration Tests
1. **Multi-step workflow**:
   - Create extension → Manager approves → Director approves → Task duration updated
2. **Permission validation**:
   - Unauthorized user attempts approval → ProcedureWorkflowException
3. **Task state transitions**:
   - Extension in progress → Approval flow → Task updated
   - Verify last_extension_status reflects workflow state

### Edge Cases
- Multiple pending extensions (should fail on second request)
- Approving already-resolved extension (should fail)
- Rejecting then approving (status should stay rejected)
- Workflow with 0 action-takers (should auto-approve)

---

## Summary

The ExtensionRequest approval flow is now **architecturally identical** to EmployeeTask:

1. **Same workflow engine**: ProcedureWorkflowService
2. **Same permission model**: ProcedureSettingStepActionTaker
3. **Same step progression**: advance() with isFinal logic
4. **Same service pattern**: Creation + Workflow separation
5. **Same error handling**: ProcedureWorkflowException
6. **Same consistency**: Changes to workflow logic apply to both

This refactoring eliminates custom approval logic and makes the codebase more maintainable, testable, and aligned with project architecture.

---

## Questions & Edge Cases

**Q: Can a user approve the same extension twice?**
A: No. The workflow clears current_procedure_step_id after final approval, and service validates status='pending'.

**Q: What happens if ProcedureSetting is deleted?**
A: Workflow queries will fail (FK reference). Recommend soft-deletes for procedure settings.

**Q: Can rejections go through multiple steps?**
A: No. Rejection always terminates immediately via assertCanReject().

**Q: How do existing extensions migrate?**
A: They'll have NULL procedure_setting_id and current_procedure_step_id. Treat them as already-resolved.
