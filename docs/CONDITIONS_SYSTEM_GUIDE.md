# Conditions System Guide
> Last updated: 2026-06-24  
> Applies to: `modules/ProcedureSetting` · `modules/EmployeeTask` · `modules/Shared/InternalProcessType`

---

## 1. Overview

Each **internal procedure setting** (`procedure_settings` rows with a `form` key) can carry a `conditions` JSON column that gates the corresponding lifecycle action.

There are **two formats** — the system supports both to allow backward-compatible migration:

| Format | Shape | Used by |
|--------|-------|---------|
| **Old (flat object)** | `{"allow_during_shift": true, ...}` | `createTask`, `endTask` |
| **New (rich array)** | `[{"key":"...", "is_active": bool, "sort_order": int, "settings": {...}}, ...]` | `startTask` and beyond |

---

## 2. New Rich-Array Format

### Stored in `procedure_settings.conditions`

```json
[
  {
    "key": "inside_shift_time",
    "is_active": true,
    "sort_order": 1,
    "settings": {
      "start_time": "08:00",
      "end_time": "17:00",
      "allow_before_start_minutes": 30,
      "allow_before_end_minutes": 15
    }
  },
  {
    "key": "inside_task_location",
    "is_active": true,
    "sort_order": 2,
    "settings": {
      "radius_meters": 100
    }
  },
  {
    "key": "employee_has_attendance",
    "is_active": true,
    "sort_order": 3,
    "settings": {}
  },
  {
    "key": "task_is_approved",
    "is_active": false,
    "sort_order": 4,
    "settings": {}
  },
  {
    "key": "no_open_task",
    "is_active": true,
    "sort_order": 5,
    "settings": {}
  }
]
```

### Field reference

| Field | Type | Description |
|-------|------|-------------|
| `key` | string | `InternalProcessCondition` enum value |
| `is_active` | bool | Whether this condition is currently enforced |
| `sort_order` | int | Display order in the UI (ascending) |
| `settings` | object | Condition-specific parameters (may be empty `{}`) |

---

## 2b. Settings-Schema Field Types

Each entry in a condition's `settings_schema` array has a `type` field:

| `type` | UI widget | Notes |
|--------|-----------|-------|
| `time` | Time picker (HH:mm) | Stored as `"HH:mm"` string |
| `int` | Number input | Stored as integer |
| `select` | Dropdown list | Has `options` array; stored value is one of `options[].value` |

### `select` schema entry

```json
{
  "key": "mode",
  "type": "select",
  "label_ar": "نوع الشرط",
  "default": "shift",
  "options": [
    { "value": "shift",         "label_ar": "داخل الدوام" },
    { "value": "specific_time", "label_ar": "وقت محدد" }
  ]
}
```

### `visible_when` — Conditional field display

Any settings-schema entry may include a `visible_when` object:

```json
{
  "key": "start_time",
  "type": "time",
  "label_ar": "من",
  "default": "08:00",
  "visible_when": { "key": "mode", "value": "specific_time" }
}
```

The frontend must **show this field only when** `settings[visible_when.key] === visible_when.value`. When the controlling field changes, re-evaluate visibility instantly (no submit required).

---

## 3. Available Conditions (createTask)

Returned by `GET /api/v1/admin/procedure-settings/forms-conditions?type=createTask`.

### `allow_during_shift`
- **Category**: `shift` (دوام)
- **Label**: موظف داخل الدوام
- **Form group**: `precondition`
- **Evaluation**: Controlled by `settings.mode`:
  - **`shift`** (default) — checks whether the current time falls inside the employee's active attendance period (via `AttendanceConstraintService`). Disabled → throws `notAllowedDuringShift`.
  - **`specific_time`** — checks whether the current time falls within the configured `start_time`–`end_time` window. Falls outside → throws `outsideShiftTimeWindow`.
- **Settings schema**:

| key | type | label_ar | default | extra |
|-----|------|----------|---------|-------|
| `mode` | `select` | نوع الشرط | `"shift"` | options: `shift`, `specific_time` |
| `start_time` | `time` | من | `"08:00"` | `visible_when: {key: mode, value: specific_time}` |
| `end_time` | `time` | إلى | `"17:00"` | `visible_when: {key: mode, value: specific_time}` |

### `allow_outside_shift`
- **Category**: `location` (موقع)
- **Label**: موظف خارج موقع الدوام
- **Form group**: `precondition`
- **Evaluation**: If this condition is **inactive** (`is_active = false`) and the employee's current GPS coordinates are **outside** all configured work locations (from `AttendanceConstraintService`), throws `notAllowedOutsideLocation`. Uses each location's own `radius` from the attendance constraint; no separate radius setting on the condition.
- **Settings**: none

### `allow_on_holidays`
- **Category**: `shift` (دوام)
- **Label**: مسموح في العطلات
- **Form group**: `precondition`
- **Evaluation**: In `shift` mode, if today is a holiday/non-working day and this flag is disabled → throws `notAllowedOnHolidays`.
- **Settings**: none

### `max_task_duration`
- **Category**: `duration` (مدة)
- **Label**: الحد الأقصى لمدة المهمة
- **Form group**: `in_form`
- **Evaluation**: `dto.durationHours > settings.max_hours` → throws `taskDurationExceedsLimit(maxHours)` (422)
- **Settings schema**:

| key | type | label_ar | default |
|-----|------|----------|---------|
| `max_hours` | `int` | الحد الأقصى للمدة (ساعة) | `8` |

### `max_scheduled_date_offset`
- **Category**: `time` (وقت)
- **Label**: الحد الأقصى لتاريخ المهمة
- **Form group**: `in_form`
- **Evaluation**: Controlled by `settings.mode`:
  - **`max_task_date`** (default) — `dto.taskDate > today + max_days` → throws `taskDateTooFarInFuture(maxDays)` (422)
  - **`end_contract`** — `dto.taskDate > employee contract end date` → throws `taskDateExceedsContractEndDate()` (422). Contract end date is calculated from `EmploymentContract.start_date + contract_duration` (using the linked `TimeUnit` code: day/month/year). No additional settings needed for this mode.
- **Settings schema**:

| key | type | label_ar | default | extra |
|-----|------|----------|---------|-------|
| `mode` | `select` | نوع الشرط | `"max_task_date"` | options: `max_task_date`, `end_contract` |
| `max_days` | `int` | الحد الأقصى للتاريخ (أيام) | `30` | `visible_when: {key: mode, value: max_task_date}` |

---

## 4. Available Conditions (startTask / endTask)

> **No conditions are defined for `startTask` or `endTask`.**  
> `GET /api/v1/admin/procedure-settings/forms-conditions?type=startTask` returns an empty `data` array.  
> The check methods `checkStartTaskConditions()` and `checkEndTaskConditions()` are no-ops.

---

## 5. Frontend Integration

### Step 1 — Fetch condition definitions

```
GET /api/v1/admin/procedure-settings/forms-conditions?type=createTask
Authorization: Bearer <token>
```

**Response** (createTask):
```json
{
  "data": [
    {
      "key": "allow_during_shift",
      "type": "bool",
      "category": "shift",
      "category_label_ar": "دوام",
      "label_ar": "موظف داخل الدوام",
      "settings_schema": [
        {
          "key": "mode",
          "type": "select",
          "label_ar": "نوع الشرط",
          "default": "shift",
          "options": [
            { "value": "shift",         "label_ar": "داخل الدوام" },
            { "value": "specific_time", "label_ar": "وقت محدد" }
          ]
        },
        {
          "key": "start_time",
          "type": "time",
          "label_ar": "من",
          "default": "08:00",
          "visible_when": { "key": "mode", "value": "specific_time" }
        },
        {
          "key": "end_time",
          "type": "time",
          "label_ar": "إلى",
          "default": "17:00",
          "visible_when": { "key": "mode", "value": "specific_time" }
        }
      ]
    },
    {
      "key": "allow_outside_shift",
      "type": "bool",
      "category": "shift",
      "category_label_ar": "دوام",
      "label_ar": "موظف خارج الدوام",
      "settings_schema": []
    },
    {
      "key": "allow_on_holidays",
      "type": "bool",
      "category": "shift",
      "category_label_ar": "دوام",
      "label_ar": "مسموح في العطلات",
      "settings_schema": []
    }
  ]
}
```

### Step 2 — Render the conditions table

Use `settings_schema` to render input fields per condition. Column mapping:

| UI Column | Data field |
|-----------|-----------|
| ترتيب | `sort_order` (editable integer) |
| الحالة | `is_active` (toggle) |
| الشرط | `label_ar` (static label) — if the condition's `settings_schema` contains a `select` field, render a **dropdown** in this column using that field's `options` |
| نوع الشرط | `category_label_ar` (from definition) |
| إعدادات الشرط | render each `settings_schema` field; **hide** fields whose `visible_when` condition is not satisfied by the current `settings` values |

### Tabbed grouping (dynamic)

Each condition definition now includes `form_group` and `form_group_label_ar`:

| Field | Example | Meaning |
|-------|---------|---------|
| `form_group` | `"precondition"` | Logical UI group key |
| `form_group_label_ar` | `"شروط قبل النموذج"` | Arabic tab title |

**Frontend rule**: After fetching conditions from `GET /forms-conditions`, **group them by `form_group`** and render each group as a separate tab (or section). Do **not** hard-code condition keys into tabs.

```js
// Example grouping logic (no hard-coded condition keys)
const groups = Object.groupBy(
  conditions,
  c => c.form_group          // "precondition" | "in_form"
);

// Tabs:
//   "شروط قبل النموذج"   → groups['precondition']
//   "شروط داخل النموذج"  → groups['in_form']
```

> Adding a new condition to the backend with the correct `formGroup()` value is sufficient; the frontend will automatically place it in the right tab.

#### Rendering `select` settings fields

When a settings-schema entry has `"type": "select"`:
1. Render a dropdown using `options[]` (`value`/`label_ar`).
2. Store the selected `value` in `settings[key]`.
3. Re-evaluate `visible_when` for all sibling fields immediately on change.

#### Rendering `visible_when` conditional fields

```js
// Show field only if the controlling sibling has the required value
const isVisible = (field, currentSettings) => {
  if (!field.visible_when) return true;
  return currentSettings[field.visible_when.key] === field.visible_when.value;
};
```

### Step 3 — Send on save (POST / PUT)

Include `conditions` as a JSON array. **Both forms (`createTask` and `startTask`) use the same rich-array format.**

**createTask example** — all five conditions active:

```json
{
  "form": "createTask",
  "type": "employee_task",
  "conditions": [
    {
      "key": "allow_during_shift",
      "is_active": true,
      "sort_order": 1,
      "settings": {
        "mode": "specific_time",
        "start_time": "08:00",
        "end_time": "17:00"
      }
    },
    {
      "key": "allow_outside_shift",
      "is_active": false,
      "sort_order": 2,
      "settings": {}
    },
    {
      "key": "allow_on_holidays",
      "is_active": true,
      "sort_order": 3,
      "settings": {}
    },
    {
      "key": "max_task_duration",
      "is_active": true,
      "sort_order": 4,
      "settings": { "max_hours": 12 }
    },
    {
      "key": "max_scheduled_date_offset",
      "is_active": true,
      "sort_order": 5,
      "settings": { "max_days": 20 }
    }
  ]
}
```

**createTask example** — `allow_during_shift` in default `shift` mode (start_time/end_time are ignored by the backend):

```json
{
  "conditions": [
    {
      "key": "allow_during_shift",
      "is_active": true,
      "sort_order": 1,
      "settings": { "mode": "shift" }
    }
  ]
}
```

**startTask example**:

```json
{
  "form": "startTask",
  "type": "employee_task",
  "conditions": [
    {
      "key": "inside_shift_time",
      "is_active": true,
      "sort_order": 1,
      "settings": {
        "start_time": "08:00",
        "end_time": "17:00",
        "allow_before_start_minutes": 30,
        "allow_before_end_minutes": 15
      }
    },
    {
      "key": "inside_task_location",
      "is_active": true,
      "sort_order": 2,
      "settings": { "radius_meters": 200 }
    },
    {
      "key": "employee_has_attendance",
      "is_active": true,
      "sort_order": 3,
      "settings": {}
    },
    {
      "key": "task_is_approved",
      "is_active": false,
      "sort_order": 4,
      "settings": {}
    },
    {
      "key": "no_open_task",
      "is_active": true,
      "sort_order": 5,
      "settings": {}
    }
  ]
}
```

> **Note**: You only need to send conditions you want to configure. Omit conditions not relevant to the UI; the backend will only enforce what is stored.

---

## 6. Backend Evaluation Flow (EmployeeTask — startTask / endTask)

```
EmployeeTaskController::start()
  └── EmployeeTaskFormConditionService::checkStartTaskConditions(...)  ← no-op, returns immediately

EmployeeTaskLifecycleService::end()
  └── EmployeeTaskFormConditionService::checkEndTaskConditions(...)    ← no-op, returns immediately
```

---

## 7. Backend Evaluation Flow (EmployeeTask — createTask)

```
EmployeeTaskRequestService::create()
  └── EmployeeTaskFormConditionService::checkCreateTaskConditions($userId, $companyId, $branchId,
                                                                    $dto->durationHours, $dto->taskDate)
        ├── resolveConditions('createTask', companyId, branchId)
        │     returns ProcedureSetting|null  (conditions = array or null)
        ├── indexConditions($conditions)   ← normalises both old+new formats
        │
        ├── assertShiftConditions($map, $userId)
        │     ├── [allow_during_shift is_active=true, settings.mode='specific_time']
        │     │     assertInsideSpecificTimeWindow($settings)
        │     │       Carbon::now() must be in [start_time, end_time]
        │     │       throws outsideShiftTimeWindow (422)
        │     │       return  (holiday checks skipped)
        │     │
        │     └── [shift mode (default)]
        │           load User + attendanceConstraint
        │           getTodaysWorkRulesForUser($user)
        │           ├── [is_holiday=true]
        │           │     check allow_on_holidays.is_active
        │           │     throws notAllowedOnHolidays (422)
        │           └── [isDuringShift=true]
        │                 check allow_during_shift.is_active
        │                 throws notAllowedDuringShift (422)
        │
        ├── assertLocationConditions($map, $userId, $lat, $lng)
        │     [allow_outside_shift is_active=false]
        │       getTodaysWorkRulesForUser($user)
        │       collect location_work + additional_locations
        │       if current GPS is outside ALL locations → throws notAllowedOutsideLocation (422)
        │       (radius comes from each location's attendance constraint config)
        │
        ├── [max_task_duration is_active=true]
        │     assertMaxTaskDuration($durationHours, $settings)
        │       durationHours > settings.max_hours
        │       throws taskDurationExceedsLimit(maxHours) (422)
        │
        └── [max_scheduled_date_offset is_active=true]
              assertMaxScheduledDateOffset($userId, $taskDate, $settings)
                mode = settings.mode ?? 'max_task_date'
                ├── [mode = 'max_task_date']
                │     Carbon::parse(taskDate) > Carbon::today()->addDays(max_days)
                │     throws taskDateTooFarInFuture(maxDays) (422)
                └── [mode = 'end_contract']
                      load User + companyUser.employmentContract.contractDurationUnit
                      contractEnd = start_date + contract_duration (by TimeUnit code: day/month/year)
                      Carbon::parse(taskDate) > contractEnd
                      throws taskDateExceedsContractEndDate() (422)
```

---

## 8. Adding New Condition Types

### Step 1 — Add enum case
```php
// modules/Shared/InternalProcessType/Enums/InternalProcessCondition.php
case YourCondition = 'your_condition';
```

### Step 2 — Add to `category()`, `labelAr()`, `formGroup()`, `settingsSchema()`
```php
public function category(): InternalProcessConditionCategory
{
    return match ($this) {
        self::YourCondition => InternalProcessConditionCategory::SomeCategory,
        // ...
    };
}

public function labelAr(): string
{
    return match ($this) {
        self::YourCondition => 'Arabic label here',
        // ...
    };
}

public function formGroup(): string
{
    return match ($this) {
        self::YourCondition => 'precondition',  // or 'in_form'
        // ...
    };
}

public function settingsSchema(): array
{
    return match ($this) {
        self::YourCondition => [
            ['key' => 'my_param', 'type' => 'int', 'label_ar' => 'My param', 'default' => 0],
        ],
        // ...
    };
}
```

### Step 3 — Register on the form
```php
// modules/Shared/InternalProcessType/Enums/InternalProcessForm.php
self::StartTask => [
    // existing...
    InternalProcessCondition::YourCondition,
],
```

### Step 4 — Add evaluator in `EmployeeTaskFormConditionService`
```php
$yourCond = $map[InternalProcessCondition::YourCondition->value] ?? null;
if ($yourCond && ($yourCond['is_active'] ?? false)) {
    $this->assertYourCondition($yourCond['settings'] ?? []);
}
```

### Step 5 — Add exception factory in `EmployeeTaskException`
```php
public static function yourConditionFailed(): self
{
    return new self(__('...'), 422);
}
```

---

## 9. Files Changed in This Feature

| File | Change |
|------|--------|
| `modules/Shared/InternalProcessType/Enums/InternalProcessConditionCategory.php` | **New** — category enum (time, location, attendance, task_status, open_task, shift, duration, attachment) |
| `modules/Shared/InternalProcessType/Enums/InternalProcessConditionType.php` | Added `Time = 'time'` case |
| `modules/Shared/InternalProcessType/Enums/InternalProcessCondition.php` | Added `formGroup()`, `formGroupLabelAr()`, updated `toDefinition()` to emit `form_group` + `form_group_label_ar`; `category()` and `settingsSchema()` updated for location-based `allow_outside_shift` |
| `modules/Shared/InternalProcessType/Enums/InternalProcessForm.php` | Updated `startTask` conditions to use 5 new rich cases |
| `modules/ProcedureSetting/Controllers/InternalProcedureSettingController.php` | Added `formsConditions()` method |
| `modules/ProcedureSetting/Resources/routes/api.php` | Added `GET /forms-conditions` route |
| `modules/Shared/InternalProcessType/Enums/InternalProcessConditionType.php` | Added `Select = 'select'` case |
| `modules/Shared/InternalProcessType/Enums/InternalProcessCondition.php` | Added `settingsSchema()` for `AllowDuringShift` (mode selector + visible_when time fields) |
| `modules/Shared/InternalProcessType/Enums/InternalProcessForm.php` | `StartTask` and `EndTask` conditions → `[]` |
| `modules/EmployeeTask/Services/EmployeeTaskFormConditionService.php` | `assertShiftConditions()` no longer evaluates `allow_outside_shift`; new `assertLocationConditions()` performs GPS check against attendance-constraint locations; `checkCreateTaskConditions` accepts `$currentLatitude` / `$currentLongitude` |
| `modules/ProcedureSetting/Requests/CreateInternalProcedureSettingRequest.php` | Removed old `prepareForValidation()` normalization; `validationRulesForForm()` now validates rich array format |
| `modules/ProcedureSetting/Requests/UpdateInternalProcedureSettingRequest.php` | Same as above |
| `modules/EmployeeTask/Exceptions/EmployeeTaskException.php` | Added `outsideShiftTimeWindow`, `employeeHasNoAttendance`, `taskNotApproved`, `hasOtherOpenTask` |
| `modules/EmployeeTask/Services/EmployeeTaskFormConditionService.php` | Full rewrite — evaluates new rich conditions, `indexConditions()` helper for dual-format support, new private assertion methods |
