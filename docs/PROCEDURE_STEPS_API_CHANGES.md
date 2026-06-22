# Procedure Steps API — Backend Changes Applied

> **Date:** 2026-06-22  
> **Scope:** Refactor `action_taker_management_hierarchies` support in the Procedure Steps API.

---

## Summary of Changes

The backend has been updated to support the new `action_taker_management_hierarchies` field — an array of `{action_taker_management_hierarchy_type, is_Deputy_Director}` objects — replacing the deprecated `action_taker_management_hierarchy_type` (single string) and `action_taker_alternative_management_hierarchy_type` (array of strings) fields.

### Key Decisions
- **Legacy fields remain in the database and model** for backward compatibility (reading old data).
- **New writes** should use `action_taker_management_hierarchies`. The old fields are still accepted in requests but are no longer required.
- **`deputy_manager` is no longer a valid type value** for new data. Instead, set `is_Deputy_Director: true` on any row.
- **GET endpoints** return both the old fields (for backward compat) and the new `action_taker_management_hierarchies` array (canonical format).

---

## Files Modified

### 1. Migration — New Column
**File:** `modules/ProcedureSetting/Database/Migrations/2026_06_22_000001_add_action_taker_management_hierarchies_to_procedure_setting_steps.php`

- Adds `action_taker_management_hierarchies` TEXT column (nullable) to `procedure_setting_steps` table.
- Stores a JSON-encoded array of `{action_taker_management_hierarchy_type, is_Deputy_Director}` objects.

### 2. Model — `ProcedureSettingStep`
**File:** `modules/ProcedureSetting/Models/ProcedureSettingStep.php`

- Added `action_taker_management_hierarchies` to `$fillable`.
- Added cast: `'action_taker_management_hierarchies' => 'array'`.
- Legacy fields (`action_taker_management_hierarchy_type`, `action_taker_alternative_management_hierarchy_type`) remain in `$fillable` and `$casts` for backward compatibility.

### 3. Enum — `ActionTakerManagementHierarchyType`
**File:** `modules/ProcedureSetting/Enums/ActionTakerManagementHierarchyType.php`

- **No changes made.** `DeputyManager` case kept for backward compatibility (reading legacy data).
- New validation rules reject `deputy_manager` as a type value in `action_taker_management_hierarchies`.

### 4. Create Request — `CreateProcedureSettingStepRequest`
**File:** `modules/ProcedureSetting/Requests/CreateProcedureSettingStepRequest.php`

- Added validation for `action_taker_management_hierarchies`:
  - `nullable|array|max:3`
  - `required_if:action_taker_type,management_hierarchy`
  - `prohibited_unless:action_taker_type,management_hierarchy`
- Each `action_taker_management_hierarchy_type` must be `in:project_manager,branch_manager,management_manager`
- Each `is_Deputy_Director` must be `nullable|boolean`
- Legacy `action_taker_management_hierarchy_type` field: removed `required_if` rule (now optional), kept `prohibited_unless`.
- Updated `createCreateProcedureSettingStepDTO()` to pass `action_taker_management_hierarchies` to the DTO.

### 5. Update Request — `UpdateProcedureSettingStepRequest`
**File:** `modules/ProcedureSetting/Requests/UpdateProcedureSettingStepRequest.php`

- Same validation rules as create, but with `sometimes` prefix for partial updates.
- Legacy `action_taker_management_hierarchy_type` field: removed `required_if` rule.

### 6. DTO — `CreateProcedureSettingStepDTO`
**File:** `modules/ProcedureSetting/DTO/CreateProcedureSettingStepDTO.php`

- Added `?array $action_taker_management_hierarchies` constructor parameter.
- Added to `toArray()` output.

### 7. Presenter — `ProcedureSettingStepPresenter`
**File:** `modules/ProcedureSetting/Presenters/ProcedureSettingStepPresenter.php`

- Added `action_taker_management_hierarchies` to the response output via `resolveActionTakerManagementHierarchies()`.
- **Backward compatibility:** If the new column is empty, the method builds the array from legacy fields:
  - `action_taker_management_hierarchy_type` → first row (with `is_Deputy_Director: true` if type was `deputy_manager`)
  - `action_taker_alternative_management_hierarchy_type` → subsequent rows
- Legacy fields (`action_taker_management_hierarchy_type`, `action_taker_alternative_management_hierarchy_type`) still returned in the response for backward compat.

### 8. Action Taker Resolver — `ActionTakerResolver`
**File:** `modules/ProcedureSetting/Services/ActionTakerResolver.php`

- `resolveManagementHierarchyUsers()` now checks `action_taker_management_hierarchies` first:
  - Iterates each row, resolves the manager for the type
  - If `is_Deputy_Director` is true, also resolves all deputy managers for that hierarchy
  - Merges and de-duplicates all user IDs
- `resolveManagerFromCreatorHierarchy()` now checks `action_taker_management_hierarchies` first:
  - Uses the first row's type as primary
  - Falls back to remaining rows if primary fails to resolve
- New private methods:
  - `resolveFromManagementHierarchiesArray()` — iterates array, resolves managers + deputies
  - `resolveManagerByType()` — resolves a single manager by type string
  - `resolveDeputyManagersForType()` — resolves all deputy managers for a given hierarchy type
- **Legacy fallback:** If `action_taker_management_hierarchies` is empty, falls back to old single-type + alternatives logic.

---

## API Contract

### POST `/api/v1/procedure-settings/{procedureSettingId}/steps`

#### `action_taker_type: "management_hierarchy"` — Request Body

```json
{
  "action_taker_type": "management_hierarchy",
  "action_taker_management_hierarchies": [
    {
      "action_taker_management_hierarchy_type": "branch_manager",
      "is_Deputy_Director": false
    },
    {
      "action_taker_management_hierarchy_type": "management_manager",
      "is_Deputy_Director": true
    }
  ]
}
```

**Validation rules:**
- Array length: 1–3
- `action_taker_management_hierarchy_type`: required, one of `project_manager`, `branch_manager`, `management_manager`
- `is_Deputy_Director`: nullable boolean
- Duplicate types within the same step are rejected by frontend; backend enforces `max:3`

### GET `/api/v1/procedure-settings/{procedureSettingId}/steps` — Response

```json
{
  "action_taker_type": "management_hierarchy",
  "action_taker_management_hierarchies": [
    {
      "action_taker_management_hierarchy_type": "branch_manager",
      "is_Deputy_Director": false
    },
    {
      "action_taker_management_hierarchy_type": "management_manager",
      "is_Deputy_Director": true
    }
  ],
  "action_taker_management_hierarchy_type": "branch_manager",
  "action_taker_alternative_management_hierarchy_type": ["management_manager"]
}
```

- `action_taker_management_hierarchies` is always present (built from new column or legacy fields).
- Legacy fields still returned for backward compatibility.

---

## Deprecated Fields

| Field | Status | Notes |
|-------|--------|-------|
| `action_taker_management_hierarchy_type` | Deprecated | Still accepted in requests, still returned in responses. Use `action_taker_management_hierarchies` instead. |
| `action_taker_alternative_management_hierarchy_type` | Deprecated | Still accepted in requests, still returned in responses. Use `action_taker_management_hierarchies` instead. |
| `deputy_manager` (as hierarchy type value) | Deprecated | Use `is_Deputy_Director: true` on any row in `action_taker_management_hierarchies`. |

---

## Backend Validation Checklist

- [x] `name` is nullable, string, max 255
- [x] `action_taker_type` is one of: `specific_user`, `management_hierarchy`, `specific_procedures`, `himself`
- [x] For `specific_user`: `action_taker_user_ids.length >= 1`
- [x] For `management_hierarchy`: `action_taker_management_hierarchies` present, max 3, valid enum values
- [x] `action_taker_management_hierarchies.*.action_taker_management_hierarchy_type` in `project_manager,branch_manager,management_manager`
- [x] `deputy_manager` rejected as type value in new array
- [x] `is_Deputy_Director` accepted as boolean per row
- [x] For `specific_procedures`: parallel arrays same length, valid types
- [x] For `himself`: no action-taker sub-structures required
- [x] Legacy fields still accepted for backward compatibility

---

## Migration Instructions

1. Run the migration:
   ```bash
   php artisan migrate
   ```
2. The new column `action_taker_management_hierarchies` will be added to the `procedure_setting_steps` table.
3. Existing data in `action_taker_management_hierarchy_type` and `action_taker_alternative_management_hierarchy_type` remains untouched.
4. GET endpoints will automatically build `action_taker_management_hierarchies` from legacy fields if the new column is empty.
5. New creates/updates should send `action_taker_management_hierarchies` in the request body.
