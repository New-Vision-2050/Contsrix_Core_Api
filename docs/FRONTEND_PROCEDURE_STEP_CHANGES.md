# Frontend Changes Required – Procedure Step Form

> Backend PR: procedure-step enhancements (deputy manager, himself type, array alternatives, array specific-procedures)
> Date: 2026-06-19

---

## 1. `action_taker_type` – New value: `himself`

### What changed
A new option **"نفسه" (Himself)** was added to the action-taker type selector.

| Value | Label (AR) | Label (EN) |
|---|---|---|
| `specific_user` | مستخدم محدد | Specific User |
| `management_hierarchy` | الهيكل التنظيمي | Management Hierarchy |
| `specific_procedures` | إجراءات محددة | Specific Procedures |
| `himself` ⭐ **NEW** | نفسه | Himself |

### UI behaviour
- When `himself` is selected:
  - Hide the action-taker user/hierarchy/procedure pickers entirely.
  - In **النماذج التنظيمية (forms)** section, show **only** `نموذج الموافقه (approve)` as the available option and pre-select it.  All other form options (`accept`, `financial`, etc.) must be hidden or disabled.
  - The step will automatically route the action request back to the person who submitted the request.

---

## 2. `action_taker_management_hierarchy_type` – New value: `deputy_manager`

### What changed
A new option **"نائب المدير" (Deputy Manager)** was added for primary and alternative hierarchy types.

| Value | Label (AR) | Label (EN) |
|---|---|---|
| `branch_manager` | مدير الفرع | Branch Manager |
| `management_manager` | مدير الإدارة | Management Manager |
| `project_manager` | مدير المشروع | Project Manager |
| `deputy_manager` ⭐ **NEW** | نائب المدير | Deputy Manager |

### When to show `deputy_manager`
Show it only when the selected branch or management **has at least one deputy manager** configured. You can check this via the management hierarchy API response (`deputy_managers` array or `has_deputy_manager` flag if available).

---

## 3. `action_taker_alternative_management_hierarchy_type` – Changed to **array**

### What changed
This field was previously a **single string**. It is now a **JSON array** of strings so multiple fallback types can be configured.

### Old shape (deprecated)
```json
"action_taker_alternative_management_hierarchy_type": "branch_manager"
```

### New shape
```json
"action_taker_alternative_management_hierarchy_type": ["branch_manager", "deputy_manager"]
```

### API contract (send)
```json
{
  "action_taker_alternative_management_hierarchy_type": ["branch_manager", "deputy_manager"]
}
```
Validation: each element must be one of `branch_manager`, `management_manager`, `deputy_manager`.  
The field is only allowed when `action_taker_type` is `management_hierarchy`.

### API contract (receive)
```json
{
  "action_taker_alternative_management_hierarchy_type": ["branch_manager", "deputy_manager"],
  "action_taker_alternative_management_hierarchy_type_labels": ["Branch Manager", "Deputy Manager"]
}
```

### UI behaviour
- Replace the single dropdown for "الهيكل التنظيمي البديل" with a **multi-select** or a list of addable tags.
- Alternatives are tried **in order** when the primary type resolves to null.
- The `deputy_manager` option should only appear when the relevant hierarchy has a deputy manager.

---

## 4. `action_taker_specific_procedure_type` + `action_taker_specific_procedure_id` – Changed to **parallel arrays**

### What changed
Both fields were previously single strings. They are now **JSON arrays** forming parallel pairs:  
`type[i]` + `id[i]` → one specific-procedure target.

### Old shape (deprecated)
```json
"action_taker_specific_procedure_type": "branch",
"action_taker_specific_procedure_id": "5"
```

### New shape
```json
"action_taker_specific_procedure_type": ["branch", "management"],
"action_taker_specific_procedure_id":   ["5", "12"]
```

### New convenience field (read-only, provided by API)
```json
"action_taker_specific_procedures": [
  { "type": "branch",      "id": "5"  },
  { "type": "management",  "id": "12" }
]
```
> Use `action_taker_specific_procedures` for **displaying** existing data.  
> Use the parallel arrays (`action_taker_specific_procedure_type[]` + `action_taker_specific_procedure_id[]`) when **sending** data.

### API contract (send)
```json
{
  "action_taker_specific_procedure_type": ["branch", "management"],
  "action_taker_specific_procedure_id":   ["5", "12"]
}
```
Both arrays must have the **same length** (validated server-side).  
Allowed type values: `branch`, `management`, `job_title`, `job_role`.

### UI behaviour
- Replace the single type+id picker for "إجراءات محددة" with a **repeatable row** component:
  - Each row = `{ type: select, id: picker }`.
  - User can add / remove rows.
  - At least one row is required when `action_taker_type` is `specific_procedures`.
- When serialising to API, split the rows into the two parallel arrays.

---

## 5. Summary of API response shape for a step

```json
{
  "action_taker_type": "management_hierarchy",
  "action_taker_type_label": "Management Hierarchy",

  "action_taker_management_hierarchy_type": "branch_manager",
  "action_taker_management_hierarchy_type_label": "Branch Manager",

  "action_taker_alternative_management_hierarchy_type": ["deputy_manager"],
  "action_taker_alternative_management_hierarchy_type_labels": ["Deputy Manager"],

  "action_taker_specific_procedure_type": [],
  "action_taker_specific_procedure_id":   [],
  "action_taker_specific_procedures":     [],

  "action_taker_hierarchy": {
    "type":  "branch_manager",
    "label": "Branch Manager"
  }
}
```

---

## 6. Migration note

The backend ran a migration that widens the three columns to `TEXT` to store JSON.  
**Existing rows** with the old single-string values will be automatically handled because the model now casts these columns as arrays — an old `"branch_manager"` string stored in the DB will be decoded as the string `"branch_manager"` (not an array).  
Frontend should gracefully handle both the old scalar and the new array when displaying data from cached/old records until a data-normalisation script is run.

---

## 7. Forms field restriction summary

| `action_taker_type` | Allowed `forms` values |
|---|---|
| `specific_user` | `approve`, `accept`, `financial` |
| `management_hierarchy` | `approve`, `accept`, `financial` |
| `specific_procedures` | `approve`, `accept`, `financial` |
| `himself` ⭐ | **`approve` only** |
