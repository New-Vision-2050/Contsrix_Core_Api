# Conditions System Guide
> Last updated: 2026-06-21  
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

## 3. Available Conditions (startTask)

Returned by `GET /api/v1/admin/procedure-settings/forms-conditions?type=startTask`.

### `inside_shift_time`
- **Category**: `time` (وقت)
- **Label**: داخل وقت الدوام
- **Evaluation**: current server time must be within `[start_time − allow_before_start_minutes, end_time − allow_before_end_minutes]`
- **Settings schema**:

| key | type | label_ar | default |
|-----|------|----------|---------|
| `start_time` | `time` | من | `"08:00"` |
| `end_time` | `time` | إلى | `"17:00"` |
| `allow_before_start_minutes` | `int` | يسمح قبل بداية الدوام بـ (دقيقة) | `0` |
| `allow_before_end_minutes` | `int` | يسمح قبل نهاية الدوام بـ (دقيقة) | `0` |

### `inside_task_location`
- **Category**: `location` (موقع)
- **Label**: داخل موقع المهمة
- **Evaluation**: Haversine distance from employee GPS to task coordinates ≤ `radius_meters`
- **Settings schema**:

| key | type | label_ar | default |
|-----|------|----------|---------|
| `radius_meters` | `int` | نطاق السماح (متر) | `100` |

### `employee_has_attendance`
- **Category**: `attendance` (حضور)
- **Label**: الموظف مسجل حضور
- **Evaluation**: Employee must have an active clock-in attendance record (`attendance.clock_out IS NULL`)
- **Settings**: none

### `task_is_approved`
- **Category**: `task_status` (حالة المهمة)
- **Label**: المهمة معتمدة
- **Evaluation**: Task `status` must equal `approved`
- **Settings**: none

### `no_open_task`
- **Category**: `open_task` (مهمة مفتوحة)
- **Label**: لا يوجد مهمة مفتوحة
- **Evaluation**: Employee must have no other task with `status = in_progress`
- **Settings**: none

---

## 4. Frontend Integration

### Step 1 — Fetch condition definitions

```
GET /api/v1/admin/procedure-settings/forms-conditions?type=startTask
Authorization: Bearer <token>
```

**Response**:
```json
{
  "data": [
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
    },
    {
      "key": "inside_task_location",
      "type": "bool",
      "category": "location",
      "category_label_ar": "موقع",
      "label_ar": "داخل موقع المهمة",
      "settings_schema": [
        { "key": "radius_meters", "type": "int", "label_ar": "نطاق السماح (متر)", "default": 100 }
      ]
    },
    {
      "key": "employee_has_attendance",
      "type": "bool",
      "category": "attendance",
      "category_label_ar": "حضور",
      "label_ar": "الموظف مسجل حضور",
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
| الشرط | `label_ar` (from definition) |
| نوع الشرط | `category_label_ar` (from definition) |
| إعدادات الشرط | render each `settings_schema` field |

### Step 3 — Send on save (POST / PUT)

Include `conditions` as a JSON array in the request body:

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
      "settings": {
        "radius_meters": 200
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
}
```

> **Note**: You only need to send conditions you want to configure. Omit conditions not relevant to the UI; the backend will only enforce what is stored.

---

## 5. Backend Evaluation Flow (EmployeeTask — startTask)

```
EmployeeTaskController::start()
  └── EmployeeTaskFormConditionService::checkStartTaskConditions($task, $user, $lat, $lon)
        ├── resolveConditions('startTask', companyId, branchId)
        │     └── ProcedureWorkflowService::resolveInternalProcedureSettingByForm()
        │           returns ProcedureSetting|null  (conditions = array or null)
        ├── indexConditions($conditions)         ← normalises both old+new formats
        │     returns ['condition_key' => {key, is_active, sort_order, settings}]
        │
        ├── [inside_shift_time is_active=true]
        │     assertInsideShiftTime($settings)
        │       Carbon::now() must be in [start_time−toleranceA, end_time−toleranceB]
        │       throws outsideShiftTimeWindow (422)
        │
        ├── [inside_task_location is_active=true]
        │     assertInsideTaskLocation($task, $user, $lat, $lon, $radius)
        │       GeoDistance::metres(...) ≤ radius_meters
        │       throws cannotStartTaskOutsideLocation (422)
        │
        ├── [employee_has_attendance is_active=true]
        │     assertEmployeeHasAttendance($userId)
        │       AttendanceRepository::getCurrentAttendance() must not be null
        │       throws employeeHasNoAttendance (422)
        │
        ├── [task_is_approved is_active=true]
        │     assertTaskIsApproved($task)
        │       task->status must equal 'approved'
        │       throws taskNotApproved (422)
        │
        └── [no_open_task is_active=true]
              assertNoOpenTask($userId, excludeTaskId)
                no other task for user with status=in_progress
                throws hasOtherOpenTask (422)
```

---

## 6. Adding New Condition Types

### Step 1 — Add enum case
```php
// modules/Shared/InternalProcessType/Enums/InternalProcessCondition.php
case YourCondition = 'your_condition';
```

### Step 2 — Add to `category()`, `labelAr()`, `settingsSchema()`
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

## 7. Files Changed in This Feature

| File | Change |
|------|--------|
| `modules/Shared/InternalProcessType/Enums/InternalProcessConditionCategory.php` | **New** — category enum (time, location, attendance, task_status, open_task, shift, duration, attachment) |
| `modules/Shared/InternalProcessType/Enums/InternalProcessConditionType.php` | Added `Time = 'time'` case |
| `modules/Shared/InternalProcessType/Enums/InternalProcessCondition.php` | Added 5 new cases, `category()`, `settingsSchema()`, updated `toDefinition()`, `validationRulesForForm()`, `defaultValuesForForm()` |
| `modules/Shared/InternalProcessType/Enums/InternalProcessForm.php` | Updated `startTask` conditions to use 5 new rich cases |
| `modules/ProcedureSetting/Controllers/InternalProcedureSettingController.php` | Added `formsConditions()` method |
| `modules/ProcedureSetting/Resources/routes/api.php` | Added `GET /forms-conditions` route |
| `modules/ProcedureSetting/Requests/CreateInternalProcedureSettingRequest.php` | Removed old `prepareForValidation()` normalization; `validationRulesForForm()` now validates rich array format |
| `modules/ProcedureSetting/Requests/UpdateInternalProcedureSettingRequest.php` | Same as above |
| `modules/EmployeeTask/Exceptions/EmployeeTaskException.php` | Added `outsideShiftTimeWindow`, `employeeHasNoAttendance`, `taskNotApproved`, `hasOtherOpenTask` |
| `modules/EmployeeTask/Services/EmployeeTaskFormConditionService.php` | Full rewrite — evaluates new rich conditions, `indexConditions()` helper for dual-format support, new private assertion methods |
