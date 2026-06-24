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
| `map_polygons` | Map with polygon drawing | Stored as array of polygons; each polygon is an ordered list of `{lat, lng}` vertices. Frontend renders an interactive map where the admin can draw multiple closed polygon areas. |

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

### `inside_custom_locations`
- **Category**: `location` (موقع)
- **Label**: موقع المهمة داخل المناطق المخصصة
- **Form group**: `in_form`
- **Evaluation**: If active, verifies that the **task's GPS coordinates** (`task_latitude`, `task_longitude` from the create-task form) lie inside at least one of the custom polygon areas configured in `settings.polygons`. If outside all polygons → throws `outsideCustomLocations`. **Note:** This checks the task location the employee enters when creating the task, NOT the employee's current GPS location.
- **Settings schema**:

| key | type | label_ar | default | extra |
|-----|------|----------|---------|-------|
| `polygons` | `map_polygons` | المواقع المحددة على الخريطة | `[]` | Array of polygons; each polygon = ordered list of `{lat, lng}` vertices |

> **Frontend AI note:** `type: "map_polygons"` is a custom schema type. Render an interactive map component that allows the admin to draw multiple closed polygon shapes. Store the result as `settings.polygons: [ [ {lat, lng}, ... ], [ {lat, lng}, ... ] ]`.

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
- **Category**: `calendar` (تقويم)
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

### `startTask`

| Condition | Category | Form Group | Label (AR) | Settings Schema | Evaluation |
|-----------|----------|------------|------------|-----------------|------------|
| `allow_on_holidays` | `shift` | `precondition` | مسموح في العطلات | — (bool) | If `is_active=false`, starting a task on a holiday throws `notAllowedOnHolidays` (422). |

### `endTask`

> **No conditions are defined for `endTask`.**  
> `checkEndTaskConditions()` is a no-op.

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

## 6. Backend Evaluation Flow (EmployeeTask — startTask)

```
EmployeeTaskController::start()
  └── EmployeeTaskFormConditionService::checkStartTaskConditions($task, $user, $latitude, $longitude)
        ├── resolveConditions('startTask', companyId, branchId)
        │     returns ProcedureSetting|null
        ├── indexConditions($conditions)
        │
        └── evaluateHolidayCondition($map, $userId)
              if today is a holiday AND allow_on_holidays is inactive
              → throws notAllowedOnHolidays (422)
```

## 7. Backend Evaluation Flow (EmployeeTask — endTask)

```
EmployeeTaskLifecycleService::end()
  └── EmployeeTaskFormConditionService::checkEndTaskConditions(...)    ← no-op, returns immediately
```

---

## 8. Backend Evaluation Flow (EmployeeTask — createTask)

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
        ├─ in_form checks ───────────────────────────────────────────────────────
        │
        ├── [max_task_duration is_active=true]
        │     assertMaxTaskDuration($durationHours, $settings)
        │       durationHours > settings.max_hours
        │       throws taskDurationExceedsLimit(maxHours) (422)
        │
        ├── [inside_custom_locations is_active=true]
        │     assertCustomLocationConditions($map, $taskLat, $taskLng)
        │       load settings.polygons[]
        │       if task GPS is outside ALL polygons → throws outsideCustomLocations (422)
        │       (checks task location from form, NOT employee's current GPS)
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

## 9. Precondition Check API (Mobile)

Before opening the create-task form, the mobile app should call:

```
GET /api/v1/employee-tasks/pre-conditions?current_latitude={lat}&current_longitude={lng}
Authorization: Bearer <token>
```

**Query params:**
- `current_latitude` (optional, float) — employee's current GPS latitude
- `current_longitude` (optional, float) — employee's current GPS longitude

**Response — ALWAYS returns all 3 preconditions (fixed checklist for mobile UI):**
```json
{
  "status": true,
  "message": "Preconditions retrieved successfully",
  "data": {
    "all_passed": false,
    "conditions": [
      {
        "key": "allow_during_shift",
        "label_ar": "موظف داخل الدوام",
        "passed": false,
        "message": "You are not currently within your work shift."
      },
      {
        "key": "allow_on_holidays",
        "label_ar": "مسموح في العطلات",
        "passed": true,
        "message": null
      },
      {
        "key": "location_inside_work_area",
        "label_ar": "التواجد داخل نطاق العمل",
        "passed": false,
        "message": "You are outside the designated work area."
      }
    ]
  }
}
```

**Important:** The API always returns exactly these 3 items so the mobile app can render a fixed checklist UI. When a precondition is **not configured** in the admin panel, it shows `passed: true` (green checkmark) because the admin is not enforcing it.

| Condition | `key` | `passed: true` means | `passed: false` means |
|-----------|-------|----------------------|------------------------|
| Shift | `allow_during_shift` | Employee is inside work shift (or shift check not configured) | Employee is outside work shift AND admin requires shift time |
| Holiday | `allow_on_holidays` | Today is not a holiday, or holidays are allowed | Today is a holiday AND admin blocks holidays |
| Work area | `location_inside_work_area` | Employee GPS is inside a work area (or location check not configured) | Employee GPS is outside all work areas AND admin enforces location |

**Mobile behaviour:**
- If `all_passed` is `true` → show the create-task form.
- If `all_passed` is `false` → show a modal like the image with red X marks for each `passed: false` condition and the corresponding `label_ar` / `message`.

---

## 10. In-Form Conditions Preview API (Mobile)

After preconditions pass, the mobile app should call this to know what in-form constraints to display inside the create-task form:

```
GET /api/v1/employee-tasks/in-form-conditions
Authorization: Bearer <token>
```

**Response (NORMALIZED — every item has the same shape):**
```json
{
  "status": true,
  "message": "In-form conditions retrieved successfully",
  "data": {
    "conditions": [
      {
        "key": "max_task_duration",
        "label_ar": "الحد الأقصى لمدة المهمة",
        "is_active": true,
        "mode": null,
        "constraints": {
          "max_hours": 8
        }
      },
      {
        "key": "max_scheduled_date_offset",
        "label_ar": "الحد الأقصى لتاريخ المهمة",
        "is_active": true,
        "mode": "max_task_date",
        "constraints": {
          "max_days": 30
        }
      },
      {
        "key": "inside_custom_locations",
        "label_ar": "موقع المهمة داخل المناطق المخصصة",
        "is_active": true,
        "mode": null,
        "constraints": {
          "polygons": [
            [
              {"lat": 24.7136, "lng": 46.6753},
              {"lat": 24.7140, "lng": 46.6760},
              {"lat": 24.7130, "lng": 46.6765}
            ]
          ]
        }
      }
    ]
  }
}
```

| Field | Type | Description |
|-------|------|-------------|
| `key` | `string` | Condition identifier (same as enum value) |
| `label_ar` | `string` | Arabic human-readable label |
| `is_active` | `bool` | Always `true` in this endpoint (inactive conditions are hidden) |
| `mode` | `string\|null` | Sub-mode of the condition; `null` when the condition has no modes |
| `constraints` | `object` | Flattened settings relevant to the active mode; empty `{}` when no extra data needed |

**Mode reference:**

| Condition | `mode` values | `constraints` when active |
|-----------|---------------|---------------------------|
| `max_task_duration` | `null` | `{ max_hours: int }` |
| `max_scheduled_date_offset` | `"max_task_date"` \| `"end_contract"` | `{ max_days: int }` (only when `mode = max_task_date`) |
| `inside_custom_locations` | `null` | `{ polygons: array[] }` |
| `has_task_duration` | `null` | `{ required: true }` |
| `max_duration_hours` | `null` | `{ max_hours: int }` |
| `max_attachments` | `null` | `{ max_count: int }` |

**Mobile behaviour:**
- Render each condition as a hint / constraint inside the form:
  - `max_task_duration` → show "Max hours: 8" next to duration field, validate client-side
  - `inside_custom_locations` → draw polygons on map, validate task pin is inside one
  - `max_scheduled_date_offset` + `mode: max_task_date` → disable dates beyond `max_days` in date picker
  - `max_scheduled_date_offset` + `mode: end_contract` → show hint "Date must be before contract end"

---

## 11. Adding New Condition Types

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

## 12. Files Changed in This Feature

| File | Change |
|------|--------|
| `modules/Shared/InternalProcessType/Enums/InternalProcessConditionCategory.php` | **New** — category enum (time, location, attendance, task_status, open_task, shift, duration, attachment) |
| `modules/Shared/InternalProcessType/Enums/InternalProcessConditionType.php` | Added `Time = 'time'` case |
| `modules/Shared/InternalProcessType/Enums/InternalProcessCondition.php` | Added `formGroup()`, `formGroupLabelAr()`, updated `toDefinition()` to emit `form_group` + `form_group_label_ar`; `category()` and `settingsSchema()` updated for location-based `allow_outside_shift` |
| `modules/Shared/InternalProcessType/Enums/InternalProcessForm.php` | `CreateTask` conditions expanded with 5 new rich cases; `StartTask` conditions now include `AllowOnHolidays`; `EndTask` conditions → `[]` |
| `modules/ProcedureSetting/Controllers/InternalProcedureSettingController.php` | Added `formsConditions()` method |
| `modules/ProcedureSetting/Resources/routes/api.php` | Added `GET /forms-conditions` route |
| `modules/Shared/InternalProcessType/Enums/InternalProcessConditionType.php` | Added `Select = 'select'` case |
| `modules/Shared/InternalProcessType/Enums/InternalProcessCondition.php` | Added `settingsSchema()` for `AllowDuringShift` (mode selector + visible_when time fields) |
| `modules/EmployeeTask/Services/EmployeeTaskFormConditionService.php` | `assertShiftConditions()` no longer evaluates `allow_outside_shift`; new `assertLocationConditions()` performs GPS check against attendance-constraint locations; `checkCreateTaskConditions` accepts `$currentLatitude` / `$currentLongitude`; `checkStartTaskConditions` now evaluates `AllowOnHolidays`; `indexConditions()` helper for dual-format support |
| `modules/ProcedureSetting/Requests/CreateInternalProcedureSettingRequest.php` | Removed old `prepareForValidation()` normalization; `validationRulesForForm()` now validates rich array format |
| `modules/ProcedureSetting/Requests/UpdateInternalProcedureSettingRequest.php` | Same as above |
| `modules/EmployeeTask/Exceptions/EmployeeTaskException.php` | Added `outsideShiftTimeWindow`, `employeeHasNoAttendance`, `taskNotApproved`, `hasOtherOpenTask` |
