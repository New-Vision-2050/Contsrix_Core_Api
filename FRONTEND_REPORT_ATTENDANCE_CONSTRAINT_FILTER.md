# Report create — attendance constraint filter

> **Who this is for:** web frontend creating a report via `POST /api/v1/reports`.  
> **What changed:** Step 2 of the wizard now accepts attendance constraint IDs. The generated report includes only employees assigned to those constraints.

---

## TL;DR

1. Load the constraint dropdown from `GET /api/v1/reports/lookups` → `payload.attendance_constraints`.
2. On create, send the selected UUIDs as `config.step2.attendance_constraint_ids`.
3. Omit the field (or send `[]`) to keep the old behaviour: no constraint filter.

---

## 1. Get the constraint list

### Preferred (reports wizard)

```
GET /api/v1/reports/lookups
```

Same headers as every other API call:

| Header | Example |
|---|---|
| `Authorization` | `Bearer <access_token>` |
| `x-domain` | `vd.constrix-nv.com` |
| `Accept` | `application/json` |

`payload.attendance_constraints` is an **unpaginated** list for the current tenant. Use `id` as the option value and `constraint_name` (or `label.ar` / `label.en`) as the label.

```json
{
  "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT",
  "payload": {
    "attendance_constraints": [
      {
        "id": "9f2c1a10-4b3e-4d21-9c8a-1a2b3c4d5e6f",
        "constraint_name": "دوام الرياض - صباحي",
        "is_active": true,
        "label": {
          "ar": "دوام الرياض - صباحي",
          "en": "دوام الرياض - صباحي"
        }
      }
    ]
  }
}
```

| Field | Type | Use |
|---|---|---|
| `id` | UUID | Value sent in `attendance_constraint_ids` |
| `constraint_name` | string | Display name |
| `is_active` | bool | Optional: hide inactive rows in the dropdown |
| `label.ar` / `label.en` | string | Same name (constraints are not bilingual) |

This list is **not** a static enum. Fetch it when the wizard opens (or when Step 2 mounts) so new constraints appear without a deploy.

### Alternative (paginated constraint admin list)

If you already load constraints from HR attendance, you can reuse:

```
GET /api/v1/attendance/constraints/list?per_page=100&is_active=1
```

Requires `human-resources.attendance*attendance-constraints.view`. Each item is `{ id, constraint_name }`.

For a dropdown, prefer `/api/v1/reports/lookups` — it is unpaginated and does not need the constraint-admin permission.

---

## 2. Add IDs to the create payload

```
POST /api/v1/reports
```

Permission: `human-resources.reports*reports.create`.

Put the selected UUIDs on **Step 2** (employee filters), next to `branch_id` / `employee_user_ids`:

```json
{
  "name": {
    "ar": "تقرير حضور الوردية الصباحية",
    "en": "Morning shift attendance report"
  },
  "config": {
    "step1": {
      "reportTypeIds": ["attendance_absence"],
      "periodType": "monthly",
      "year": 2026,
      "month": 8,
      "exportFormat": "pdf",
      "reportLanguage": "ar",
      "paperSize": "A4",
      "printOrientation": "portrait"
    },
    "step2": {
      "employee_scope": "all",
      "employee_user_ids": [],
      "branch_id": null,
      "management_id": null,
      "department": null,
      "job_title": null,
      "contractTypeIds": [],
      "nationality": null,
      "gender": null,
      "attendance_constraint_ids": [
        "9f2c1a10-4b3e-4d21-9c8a-1a2b3c4d5e6f"
      ]
    },
    "step3": {
      "attendanceDataTypeIds": ["day", "actual_in", "actual_out", "delay", "total_hours"],
      "display_mode": "employee_per_page",
      "attendancePattern": "all",
      "attendanceRateMin": "no_filter",
      "delayLimitMinutes": "no_filter",
      "minOvertime": "no_filter",
      "includeEntryExitTime": true,
      "includeShiftName": true,
      "includeAttendanceNotes": false,
      "calculateTotalWorkHours": true,
      "showPreviousMonthComparison": false
    }
  }
}
```

`attendanceConstraintIds` (camelCase) is also accepted and stored as `attendance_constraint_ids`.

The same field is valid on:

- `POST /api/v1/reports/templates` (save a template)
- `POST /api/v1/reports/templates/{id}` (update a template)

---

## 3. How the filter works

| Payload | Employees in the report |
|---|---|
| Field omitted or `[]` | No extra restriction (same as before) |
| One or more UUIDs | Employees whose **main** constraint (`user_professional_datas.attendance_constraint_id`) **or** an **additional** constraint (`attendance_constraint_user`) matches any selected ID |

It combines with the other Step 2 filters (branch, department, specific employees, …) with AND.

Unknown / other-tenant / deleted UUIDs fail validation (`422`).

---

## 4. Suggested UI

1. On wizard load, call `GET /api/v1/reports/lookups` once (you already do this for the other dropdowns).
2. Bind a multi-select to `payload.attendance_constraints`.
3. Optionally hide rows where `is_active === false`.
4. Write the selected `id`s to `config.step2.attendance_constraint_ids`.
5. When editing a saved template or re-opening a generated report, read `config.step2.attendance_constraint_ids` and pre-select those options.

```ts
type AttendanceConstraintOption = {
  id: string;
  constraint_name: string;
  is_active: boolean;
  label: { ar: string; en: string };
};

// from GET /api/v1/reports/lookups
const options: AttendanceConstraintOption[] =
  data.payload.attendance_constraints ?? [];

// into POST /api/v1/reports
config.step2.attendance_constraint_ids = selectedIds;
```

---

## What NOT to do

- Do not hard-code constraint IDs or names. They are tenant data.
- Do not send constraint **names** in the create payload — only UUIDs.
- Do not put the IDs on Step 3. Step 3 is attendance-pattern filters (`attendancePattern`, `delayLimitMinutes`, …).
