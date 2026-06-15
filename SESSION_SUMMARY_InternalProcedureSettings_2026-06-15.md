# Session Summary: Internal Procedure Settings Refactor
**Date:** June 15, 2026
**Status:** Complete

---

## Architecture (3 Levels)

All 3 levels live in the same table (`procedure_settings`), distinguished by `parent_id` and `form`:

| Level | Entity | `parent_id` | `form` | API Endpoint |
|-------|--------|-------------|--------|--------------|
| 1 | **Procedure Setting** (Parent Category) | `NULL` | absent | `POST/PUT /procedure-settings` |
| 2 | **Internal Procedure** (Child / Action) | `parent.id` | present | `POST /procedure-settings/{parent_id}/internal-procedures` |
| 3 | **Step** (Workflow Step) | — | absent | `GET/POST /procedure-settings/{id}/steps` |

### Visual Hierarchy (matching your UI)

```
Level 1 — Procedure Setting (Parent)
├── id, name="بداية مهمة العمل", type="employee_task"
│   └── parent_id = NULL  ← no parent
│   └── NO form
│
Level 2 — Internal Procedure (Child / Action Form)
├── id, name="بدء المهمة", form="start_task"
│   └── parent_id = Level-1.id
│   └── HAS form + conditions
│
Level 3 — Step (Workflow Stage)
├── step_id, name="المرحلة الأولى"
│   └── procedure_setting_id = Level-1.id OR Level-2.id
│   └── NO form (just step config: action takers, approval, etc.)
```

**In your UI screenshot:**
- The top "بداية مهمة العمل" row = **Procedure Setting** (parent)
- The tabs "المرحلة الأولى", "المرحلة الثانية" = **Steps** under that parent
- The gear/settings icon = click to configure that Procedure Setting's steps

---

## Files Modified

### 1. `modules/ProcedureSetting/Controllers/InternalProcedureSettingController.php`
- Added `showByForm(string $id, string $formKey)` — `GET /procedure-settings/{id}/internal-procedures/by-form/{formKey}`

### 2. `modules/ProcedureSetting/Resources/routes/api.php`
- Added route: `GET /{id}/internal-procedures/by-form/{formKey}`

### 3. `modules/ProcedureSetting/Repositories/ProcedureSettingRepository.php`
- Added `whereNull('parent_id')` to ALL `procedureSettings` eager-loads (4 places)
  - `listByWorkFlow()`, `getDefaultWorkFlowForList()`, `getDefaultWorkFlowByType()`, `toggleBranchDefaultWorkFlows()`
- **Reverted** auto-creation of children on parent create (not needed)

### 4. `modules/ProcedureSetting/Controllers/ProcedureSettingController.php`
- Updated `presentWorkFlow()` to filter `->whereNull('parent_id')->values()`
- Now lists **only parent rows** in WorkFlow response

### 5. `modules/ProcedureSetting/Presenters/ProcedureSettingPresenter.php`
- Added `is_internal_procedure` field to output

### 6. `modules/ProcedureSetting/Requests/CreateProcedureSettingRequest.php`
- Added `parent_id` validation: `nullable|uuid|exists:procedure_settings,id`

### 7. `modules/ProcedureSetting/DTO/CreateProcedureSettingDTO.php`
- Added `parent_id` parameter + conditionally includes it in `toArray()`

### 8. `modules/ProcedureSetting/Requests/UpdateProcedureSettingRequest.php`
- Added `parent_id` validation: `nullable|uuid|exists:procedure_settings,id`

### 9. `modules/ProcedureSetting/Commands/UpdateProcedureSettingCommand.php`
- Added `parent_id` to allowed update keys array

### 10. `ProcedureSetting_API.postman_collection.json` (v2.0.0)
- Updated collection info + `internal_procedure_setting_id` variable
- Updated `type` filter: all 5 categories (`employee_task`, `client_request`, `price_offer`, `contract`, `meeting`)
- Updated Create/Update descriptions with `parent_id` support
- **New folder "Internal Procedure Settings"** with 6 endpoints:
  - `GET /{id}/internal-procedures`
  - `GET /{id}/internal-procedures/by-form/{formKey}`
  - `POST /{id}/internal-procedures`
  - `PUT /{id}/internal-procedures/{child_id}`
  - `DELETE /{id}/internal-procedures/{child_id}`
  - `GET /{id}/available-forms`

### 11. `EmployeeTask_API.postman_collection.json`
- Added `internal_procedure_setting_id` variable
- Updated `approval-responsibles` to `type=employee_task&form_key=start_task`
- Added `GET /employee-tasks/{id}/available-actions`
- Added `POST /employee-tasks/{id}/request-approval`
- Renumbered folders (Admin=5, Attendance=6)

### 12. `docs/PROCEDURE_WORKFLOW_DEEP_GUIDE.md`
- **§3** — Added `ProcedureSettingType`, `InternalProcessForm`, `InternalProcessCondition` enums
- **§9** — Rewrote EmployeeTask integration with internal procedure settings
- **§21.10** — Updated polymorphic type: `employee_task_request` -> `employee_task`
- **§23** — Added full June 2026 changelog entry
- **§25** — Added 8 new glossary terms
- **§27** — Added complete "Internal Procedure Settings" reference (architecture, resolution, mobile flow, CRUD, conditions, traps)

---

## Key Concepts

- `procedure_setting_id` in URLs = **Parent UUID** (`parent_id = NULL`)
- `internal_procedure_setting_id` = **Child UUID** (auto-generated)
- **Forms can be duplicated** under the same parent (e.g., two `confirm_location` children)
- `GET /procedure-settings?type=employee_task` returns **only parents**; children excluded
- Children accessed only via `/procedure-settings/{parent_id}/internal-procedures`
- `is_internal_procedure: true/false` flag in API responses distinguishes parent vs child

---

## How to Create

### Parent Category
```
POST /procedure-settings
{
  "name": "إجراءات مهام العمال",
  "type": "employee_task",
  "execute_type": "sequence"
}
```

### Child Internal Procedure (Recommended)
```
POST /procedure-settings/{parent_id}/internal-procedures
{
  "name": "بدء المهمة",
  "form": "start_task",
  "conditions": [],
  "sort_order": 1
}
```

### Child via Main Endpoint (Advanced)
```
POST /procedure-settings
{
  "name": "بدء المهمة",
  "type": "employee_task",
  "parent_id": "parent-uuid-here"
}
```

---

## Validation
- Both `EmployeeTask_API.postman_collection.json` and `ProcedureSetting_API.postman_collection.json` validated successfully
- No syntax errors in any modified PHP files
