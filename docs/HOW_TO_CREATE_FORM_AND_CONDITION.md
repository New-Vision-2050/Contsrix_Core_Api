# How to Create a New Form, Condition & Apply in the API

> **Last updated:** 2026-06-24
> **Scope:** Backend-only guide. The frontend auto-consumes everything via `GET /procedure-settings/forms-conditions`.

---

## Table of Contents

1. [Overview](#1-overview)
2. [Step 1 — Add a New Form](#2-step-1--add-a-new-form)
3. [Step 2 — Add a New Condition](#3-step-2--add-a-new-condition)
4. [Step 3 — Attach Conditions to the Form](#4-step-3--attach-conditions-to-the-form)
5. [Step 4 — Implement Condition Logic in the Service](#5-step-4--implement-condition-logic-in-the-service)
6. [Step 5 — Wire DTO / Request / Controller / Service](#6-step-5--wire-dto--request--controller--service)
7. [Step 6 — Add the Exception (if needed)](#7-step-6--add-the-exception-if-needed)
8. [Step 7 — Update the Docs](#8-step-7--update-the-docs)
9. [Full Example: `CreateNewThing` Form + `MustBeVerified` Condition](#9-full-example--createnewthing-form--mustbeverified-condition)
10. [Frontend Behaviour (No code changes needed)](#10-frontend-behaviour-no-code-changes-needed)

---

## 1. Overview

The procedure-setting condition system is **fully dynamic** on the backend. To add a new form or condition you only change PHP files — the frontend reads the definitions automatically.

### Three core files

| File | Role |
|------|------|
| `InternalProcessForm.php` | Lists every form (e.g. `CreateTask`, `StartTask`) and which conditions each form carries. |
| `InternalProcessCondition.php` | Lists every condition, its Arabic label, category, form group, and `settings_schema`. |
| `EmployeeTaskFormConditionService.php` | Enforces the conditions at runtime. |

### Data flow

```
Frontend                              Backend
────────                              ───────
   │   GET /forms-conditions?type=xxx    │
   │ ─────────────────────────────────>  │
   │   JSON: definitions + schemas       │
   │ <─────────────────────────────────  │
   │                                     │
   │   POST /employee-tasks              │
   │ ─────────────────────────────────>  │
   │        (dto hits condition service) │
   │                                     │
   │   422 ── taskDateTooFarInFuture     │
   │ <─────────────────────────────────  │
```

---

## 2. Step 1 — Add a New Form

**File:** `modules/Shared/InternalProcessType/Enums/InternalProcessForm.php`

### 2.1 Add the enum case

```php
// After the existing cases
case CreateNewThing = 'createNewThing';
```

> The string value (`createNewThing`) is what the frontend sends as the `type` query parameter.

### 2.2 Add the `conditions()` mapping

```php
public function conditions(): array
{
    return match ($this) {
        self::CreateTask => [
            InternalProcessCondition::AllowDuringShift,
            InternalProcessCondition::AllowOutsideShift,
            InternalProcessCondition::AllowOnHolidays,
            InternalProcessCondition::MaxTaskDuration,
            InternalProcessCondition::MaxScheduledDateOffset,
        ],

        // ── NEW ─────────────────────────────────────────────
        self::CreateNewThing => [
            InternalProcessCondition::MustBeVerified,
            InternalProcessCondition::MaxAttachments,
        ],
        // ─────────────────────────────────────────────────────

        self::StartTask => [],
        self::EndTask   => [],
        default => [],
    };
}
```

### 2.3 Add a label (optional but recommended)

```php
public function labelAr(): string
{
    return match ($this) {
        self::CreateTask     => 'إنشاء مهمة',
        self::CreateNewThing => 'إنشاء شيء جديد',
        default              => '',
    };
}
```

---

## 3. Step 2 — Add a New Condition

**File:** `modules/Shared/InternalProcessType/Enums/InternalProcessCondition.php`

### 3.1 Add the enum case

```php
case MustBeVerified = 'must_be_verified';
```

### 3.2 Add `labelAr()`

```php
public function labelAr(): string
{
    return match ($this) {
        // ... existing cases ...
        self::MustBeVerified => 'يجب أن يكون موثق',
    };
}
```

### 3.3 Add `formGroup()`

Controls which tab the condition appears under in the frontend:

- `'precondition'` → **"شروط قبل النموذج"** (gatekeeping rules)
- `'in_form'` → **"شروط داخل النموذج"** (input constraints)

```php
public function formGroup(): string
{
    return match ($this) {
        // ... existing in_form cases ...
        self::MustBeVerified => 'precondition',
        default              => 'precondition',
    };
}
```

### 3.4 Add `category()`

```php
public function category(): InternalProcessConditionCategory
{
    return match ($this) {
        // ... existing cases ...
        self::MustBeVerified => InternalProcessConditionCategory::Attendance,
        default              => InternalProcessConditionCategory::Shift,
    };
}
```

> If none of the existing categories fit, add a new one in `InternalProcessConditionCategory.php` (see §2 of this guide).

### 3.5 Add `settingsSchema()` (if the condition needs configuration)

```php
public function settingsSchema(): array
{
    return match ($this) {
        // ... existing cases ...

        self::MustBeVerified => [],   // no settings needed

        self::MaxSomething => [
            ['key' => 'max_count', 'type' => 'int', 'label_ar' => 'الحد الأقصى', 'default' => 5],
        ],

        default => [],
    };
}
```

### Supported `settingsSchema` field types

| type | extra keys | stored as |
|------|-----------|-----------|
| `int` | `min`, `max` | integer |
| `string` | — | string |
| `bool` | — | boolean |
| `time` | — | `"HH:MM"` string |
| `select` | `options: [ {value, label_ar} ]` | selected `value` |

### Conditional visibility (`visible_when`)

Hide a field unless another field has a specific value:

```php
[
    'key'          => 'max_days',
    'type'         => 'int',
    'label_ar'     => 'الحد الأقصى للتاريخ (أيام)',
    'default'      => 30,
    'visible_when' => ['key' => 'mode', 'value' => 'max_task_date'],
],
```

### 3.6 `toDefinition()` (already automatic)

The `toDefinition()` method already includes `form_group` and `form_group_label_ar`. **No changes needed** — the frontend will receive the new fields automatically.

---

## 4. Step 3 — Attach Conditions to the Form

Already done in **Step 1.2** (`InternalProcessForm::conditions()`). Any condition listed there for a form will appear in the frontend when that form is selected.

---

## 5. Step 4 — Implement Condition Logic in the Service

**File:** `modules/EmployeeTask/Services/EmployeeTaskFormConditionService.php`

### 5.1 Add the check method in the main entry point

```php
public function checkCreateNewThingConditions(
    string $userId,
    string $companyId,
    ?string $branchId,
    array $conditionsMap,   // parsed from procedure settings
): void {
    $map = $this->toKeyedMap($conditionsMap);

    // ── must_be_verified ──────────────────────────────────
    $verifiedCond = $map[InternalProcessCondition::MustBeVerified->value] ?? null;
    if ($verifiedCond && ($verifiedCond['is_active'] ?? false)) {
        $this->assertMustBeVerified($userId);
    }

    // ── max_something ─────────────────────────────────────
    $maxCond = $map[InternalProcessCondition::MaxSomething->value] ?? null;
    if ($maxCond && ($maxCond['is_active'] ?? false)) {
        $this->assertMaxSomething($maxCond['settings'] ?? []);
    }
}
```

### 5.2 Write the assertion method

```php
/**
 * @throws EmployeeTaskException
 */
private function assertMustBeVerified(string $userId): void
{
    $user = User::find($userId);

    if ($user === null || ! $user->is_verified) {
        throw EmployeeTaskException::notVerified();
    }
}
```

### 5.3 Read settings inside assertions

```php
private function assertMaxSomething(array $settings): void
{
    $maxCount = (int) ($settings['max_count'] ?? 5);
    // ... your logic ...
}
```

---

## 6. Step 5 — Wire DTO / Request / Controller / Service

If your new form needs new input fields (e.g. `verification_code`), propagate them through the layers.

### 6.1 DTO
**File:** `modules/EmployeeTask/DTO/CreateNewThingRequestDTO.php`

```php
final readonly class CreateNewThingRequestDTO
{
    public function __construct(
        public string  $userId,
        public string  $companyId,
        public ?string $branchId,
        public ?string $verificationCode = null,
        // ... other fields ...
    ) {}
}
```

### 6.2 Form Request
**File:** `modules/EmployeeTask/Requests/CreateNewThingRequest.php`

```php
public function rules(): array
{
    return [
        'user_id'           => ['required', 'string', 'uuid', 'exists:users,id'],
        'verification_code' => ['nullable', 'string'],
        // ... other rules ...
    ];
}
```

### 6.3 Controller
**File:** `modules/EmployeeTask/Controllers/EmployeeTaskController.php`

```php
public function storeNewThing(CreateNewThingRequest $request): JsonResponse
{
    $dto = new CreateNewThingRequestDTO(
        userId:           $request->input('user_id'),
        companyId:        tenant('id'),
        branchId:         $request->input('branch_id'),
        verificationCode: $request->input('verification_code'),
    );

    $result = $this->requestService->createNewThing($dto);

    return $this->success($result);
}
```

### 6.4 Service
**File:** `modules/EmployeeTask/Services/EmployeeTaskRequestService.php`

```php
public function createNewThing(CreateNewThingRequestDTO $dto): array
{
    // 1. enforce conditions
    $conditionsMap = /* load from procedure settings */;
    $this->conditionService->checkCreateNewThingConditions(
        $dto->userId,
        $dto->companyId,
        $dto->branchId,
        $conditionsMap,
    );

    // 2. create the thing
    // ...
}
```

---

## 7. Step 6 — Add the Exception (if needed)

**File:** `modules/EmployeeTask/Exceptions/EmployeeTaskException.php`

```php
public static function notVerified(): self
{
    return new self(__('You must be verified to perform this action.'), 422);
}
```

---

## 8. Step 7 — Update the Docs

**File:** `docs/CONDITIONS_SYSTEM_GUIDE.md`

1. Add the new form to the "Available Forms" table.
2. Add the new condition to the "Available Conditions (createTask)" table.
3. Update the backend evaluation flow diagram.
4. If a new category was added, update the category table.

---

## 9. Full Example: `CreateNewThing` Form + `MustBeVerified` Condition

### `InternalProcessForm.php`

```php
enum InternalProcessForm: string
{
    case CreateTask      = 'createTask';
    case CreateNewThing  = 'createNewThing';
    case StartTask       = 'startTask';
    case EndTask         = 'endTask';

    public function conditions(): array
    {
        return match ($this) {
            self::CreateTask => [
                InternalProcessCondition::AllowDuringShift,
                InternalProcessCondition::AllowOutsideShift,
                InternalProcessCondition::AllowOnHolidays,
                InternalProcessCondition::MaxTaskDuration,
                InternalProcessCondition::MaxScheduledDateOffset,
            ],
            self::CreateNewThing => [
                InternalProcessCondition::MustBeVerified,
                InternalProcessCondition::MaxAttachments,
            ],
            default => [],
        };
    }
}
```

### `InternalProcessCondition.php`

```php
enum InternalProcessCondition: string
{
    // ... existing cases ...
    case MustBeVerified = 'must_be_verified';

    public function labelAr(): string
    {
        return match ($this) {
            // ... existing ...
            self::MustBeVerified => 'يجب أن يكون موثق',
        };
    }

    public function formGroup(): string
    {
        return match ($this) {
            // ... existing ...
            self::MustBeVerified => 'precondition',
            default              => 'precondition',
        };
    }

    public function category(): InternalProcessConditionCategory
    {
        return match ($this) {
            // ... existing ...
            self::MustBeVerified => InternalProcessConditionCategory::Attendance,
        };
    }

    public function settingsSchema(): array
    {
        return match ($this) {
            // ... existing ...
            self::MustBeVerified => [],   // no settings needed
            default => [],
        };
    }
}
```

### `EmployeeTaskFormConditionService.php`

```php
public function checkCreateNewThingConditions(
    string $userId,
    string $companyId,
    ?string $branchId,
    array $conditionsMap,
): void {
    $map = $this->toKeyedMap($conditionsMap);

    $verifiedCond = $map[InternalProcessCondition::MustBeVerified->value] ?? null;
    if ($verifiedCond && ($verifiedCond['is_active'] ?? false)) {
        $this->assertMustBeVerified($userId);
    }
}

private function assertMustBeVerified(string $userId): void
{
    $user = User::find($userId);
    if ($user === null || ! $user->is_verified) {
        throw EmployeeTaskException::notVerified();
    }
}
```

---

## 10. Frontend Behaviour (No code changes needed)

The frontend calls:

```
GET /api/v1/admin/procedure-settings/forms-conditions?type=createNewThing
```

and receives:

```json
{
  "data": [
    {
      "key": "must_be_verified",
      "type": "bool",
      "category": "attendance",
      "category_label_ar": "حضور",
      "label_ar": "يجب أن يكون موثق",
      "form_group": "precondition",
      "form_group_label_ar": "شروط قبل النموذج",
      "settings_schema": []
    }
  ]
}
```

It then:
1. **Splits conditions into tabs** automatically based on `form_group`.
2. **Renders each field** based on `settings_schema[].type`.
3. **Shows / hides fields** dynamically based on `visible_when`.

> **You do NOT need to write any frontend code.** Just add the condition on the backend with the correct `formGroup()`, and the frontend will place it in the right tab automatically.

---

## Quick Checklist

| Step | File | Action |
|------|------|--------|
| 1 | `InternalProcessForm.php` | Add enum case + `conditions()` mapping |
| 2 | `InternalProcessCondition.php` | Add enum case + label + group + category + schema |
| 3 | `EmployeeTaskFormConditionService.php` | Add check method + assertion logic |
| 4 | `EmployeeTaskException.php` | Add exception if throwing a new error |
| 5 | DTO / Request / Controller / Service | Wire new input fields (if any) |
| 6 | `CONDITIONS_SYSTEM_GUIDE.md` | Update docs |
| 7 | `php -l` | Syntax-check all changed files |
