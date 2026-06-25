# Condition Evaluator Implementation Guide

> **Complete guide for implementing condition evaluation from scratch and applying it in the API.**
>
> **Last updated:** 2026-06-25

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Core Components](#2-core-components)
3. [Step-by-Step: Implementing a New Condition from Scratch](#3-step-by-step-implementing-a-new-condition-from-scratch)
4. [How the Registry-Driven Dispatch Works](#4-how-the-registry-driven-dispatch-works)
5. [API Integration: How Conditions Are Applied](#5-api-integration-how-conditions-are-applied)
6. [ConditionContext: The Data Bag](#6-conditioncontext-the-data-bag)
7. [ConditionResult: The Evaluation Output](#7-conditionresult-the-evaluation-output)
8. [Exception Mapping](#8-exception-mapping)
9. [Backward Compatibility](#9-backward-compatibility)
10. [Testing Conditions](#10-testing-conditions)
11. [Complete File Reference](#11-complete-file-reference)

---

## 1. Architecture Overview

The condition evaluation system follows the **Open/Closed Principle** (OCP):
- **Open for extension** — new conditions are added by creating a new evaluator class and registering it.
- **Closed for modification** — the core dispatch logic never changes when conditions are added or removed.

### High-Level Flow

```
Admin configures conditions in ProcedureSetting UI
      │
      ▼
Conditions stored as JSON in procedure_settings.conditions
      │
      ▼
Employee submits task create/start request via API
      │
      ▼
EmployeeTaskFormConditionService::checkCreateTaskConditions()
      │
      ├── resolveConditionMap()  →  loads + normalizes conditions from DB
      │
      ├── builds ConditionContext  →  carries all request data
      │
      ├── evaluateAndThrow()  →  iterates condition map
      │       │
      │       ├── for each condition key:
      │       │     ├── look up evaluator in ConditionEvaluatorRegistry
      │       │     ├── evaluator->evaluate($condData, $ctx)
      │       │     └── if result->passed === false → throwFromResult()
      │       │
      │       └── all passed → return (no exception)
      │
      ▼
WorkflowEngine::previewResponsibles()  ← proceeds only if all conditions pass
```

### Design Pattern: Strategy + Registry

Each condition is a **Strategy** (implements `ConditionEvaluator` interface). The **Registry** (`ConditionEvaluatorRegistry`) maps condition enum values to their strategy implementations. The service dispatches dynamically — it never hard-codes which conditions to check.

---

## 2. Core Components

### 2.1 ConditionEvaluator (Interface)

**File:** `modules/EmployeeTask/Conditions/ConditionEvaluator.php`

```php
interface ConditionEvaluator
{
    public function condition(): InternalProcessCondition;

    /**
     * @param array{key: string, is_active: bool, sort_order: int, settings: array} $conditionData
     */
    public function evaluate(array $conditionData, ConditionContext $ctx): ?ConditionResult;
}
```

- `condition()` — returns the enum case this evaluator handles.
- `evaluate()` — returns `null` (condition not configured/not enforced) or a `ConditionResult` (pass/fail).

### 2.2 ConditionContext (DTO)

**File:** `modules/EmployeeTask/Conditions/ConditionContext.php`

Immutable bag carrying every piece of data a condition evaluator might need. This avoids changing method signatures when new conditions are added.

```php
final class ConditionContext
{
    public function __construct(
        public readonly string  $userId,
        public readonly string  $companyId,
        public readonly ?string $branchId,
        public readonly ?float  $currentLatitude = null,
        public readonly ?float  $currentLongitude = null,
        public readonly ?float  $taskLatitude = null,
        public readonly ?float  $taskLongitude = null,
        public readonly ?float  $durationHours = null,
        public readonly ?string $taskDate = null,
    ) {}
}
```

### 2.3 ConditionResult (DTO)

**File:** `modules/EmployeeTask/Conditions/ConditionResult.php`

```php
final class ConditionResult
{
    public function __construct(
        public readonly string  $key,
        public readonly string  $labelAr,
        public readonly bool    $passed,
        public readonly ?string $message = null,
        public readonly ?string $exception = null,   // exception key for throwFromResult()
        public readonly array   $context = [],       // extra values for exception factory
    ) {}
}
```

### 2.4 ConditionEvaluatorRegistry

**File:** `modules/EmployeeTask/Conditions/ConditionEvaluatorRegistry.php`

Maps `InternalProcessCondition` enum values to evaluator instances. Provides:
- `get(InternalProcessCondition)` — returns the evaluator for a condition (or null).
- `forFormGroup(string)` — returns all evaluators in a given group (`'precondition'` or `'in_form'`).
- `register(ConditionEvaluator)` — adds an evaluator at runtime.

### 2.5 ResolvesUserAttendance (Trait)

**File:** `modules/EmployeeTask/Conditions/ResolvesUserAttendance.php`

Shared logic for evaluators that need to:
- Load a user with branch/address/country/timezones relations.
- Resolve the user's branch timezone.
- Check if the current time falls within any scheduled work period.

Used by: `AllowDuringShiftEvaluator`, `AllowOnHolidaysEvaluator`, `AllowOutsideShiftEvaluator`.

---

## 3. Step-by-Step: Implementing a New Condition from Scratch

This section walks through adding a hypothetical new condition `MinTaskDuration` (minimum task duration in hours) end-to-end.

### Step 1: Add the Enum Case

**File:** `modules/Shared/InternalProcessType/Enums/InternalProcessCondition.php`

```php
case MinTaskDuration = 'min_task_duration';
```

Add it to `formGroup()`:
```php
self::MinTaskDuration => 'in_form',
```

Add `labelAr()`:
```php
self::MinTaskDuration => 'الحد الأدنى لمدة المهمة',
```

Add `category()`:
```php
self::MinTaskDuration => InternalProcessConditionCategory::Duration,
```

Add `settingsSchema()`:
```php
self::MinTaskDuration => [
    ['key' => 'min_hours', 'type' => 'int', 'label_ar' => 'الحد الأدنى (ساعات)', 'default' => 1],
],
```

### Step 2: Register in the Form

**File:** `modules/Shared/InternalProcessType/Enums/InternalProcessForm.php`

```php
self::CreateTask => [
    InternalProcessCondition::AllowDuringShift,
    InternalProcessCondition::AllowOutsideShift,
    InternalProcessCondition::AllowOnHolidays,
    InternalProcessCondition::InsideCustomLocations,
    InternalProcessCondition::MaxTaskDuration,
    InternalProcessCondition::MaxScheduledDateOffset,
    InternalProcessCondition::MinTaskDuration,   // ← new
],
```

### Step 3: Add the Exception Factory

**File:** `modules/EmployeeTask/Exceptions/EmployeeTaskException.php`

```php
public static function taskDurationBelowMinimum(int $minHours): self
{
    return new self(__("The task duration must be at least {$minHours} hours."), 422);
}
```

### Step 4: Create the Evaluator Class

**File:** `modules/EmployeeTask/Conditions/MinTaskDurationEvaluator.php`

```php
<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Conditions;

use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;

final class MinTaskDurationEvaluator implements ConditionEvaluator
{
    public function condition(): InternalProcessCondition
    {
        return InternalProcessCondition::MinTaskDuration;
    }

    public function evaluate(array $conditionData, ConditionContext $ctx): ?ConditionResult
    {
        // Not active → not enforced
        if (! ($conditionData['is_active'] ?? false)) {
            return null;
        }

        // No duration provided → skip
        if ($ctx->durationHours === null) {
            return null;
        }

        $minHours = (int) ($conditionData['settings']['min_hours'] ?? 1);

        if ($ctx->durationHours >= $minHours) {
            return new ConditionResult(
                key: $this->condition()->value,
                labelAr: $this->condition()->labelAr(),
                passed: true,
            );
        }

        return new ConditionResult(
            key: $this->condition()->value,
            labelAr: $this->condition()->labelAr(),
            passed: false,
            message: "The task duration must be at least {$minHours} hours.",
            exception: 'taskDurationBelowMinimum',
            context: ['minHours' => $minHours],
        );
    }
}
```

### Step 5: Register the Evaluator in the Service Provider

**File:** `modules/EmployeeTask/Providers/EmployeeTaskServiceProvider.php`

1. Add import:
```php
use Modules\EmployeeTask\Conditions\MinTaskDurationEvaluator;
```

2. Add to the registry constructor:
```php
$this->app->singleton(ConditionEvaluatorRegistry::class, function ($app) {
    return new ConditionEvaluatorRegistry([
        // ... existing evaluators ...
        $app->make(MinTaskDurationEvaluator::class),
    ]);
});
```

3. Register as singleton:
```php
$this->app->singleton(MinTaskDurationEvaluator::class);
```

### Step 6: Add Exception Mapping

**File:** `modules/EmployeeTask/Services/EmployeeTaskFormConditionService.php`

In the `throwFromResult()` method, add:
```php
'taskDurationBelowMinimum' => throw EmployeeTaskException::taskDurationBelowMinimum(
    (int) ($result->context['minHours'] ?? 0),
),
```

### Step 7: Verify

- **No changes needed** to `checkCreateTaskConditions()`, `getPreConditionResults()`, or `evaluateAndThrow()`.
- The new condition is automatically picked up because `evaluateAndThrow()` iterates all conditions in the map and dispatches to whatever evaluator is registered.
- Run `php -l` on all new/modified files.
- Test with a procedure setting that has `min_task_duration` active.

### Summary: What You Created vs. What You Didn't Touch

| Created/Modified | Did NOT touch |
|-----------------|---------------|
| `InternalProcessCondition.php` (add enum case) | `EmployeeTaskFormConditionService::checkCreateTaskConditions()` |
| `InternalProcessForm.php` (register in form) | `EmployeeTaskFormConditionService::evaluateAndThrow()` |
| `EmployeeTaskException.php` (add factory) | `EmployeeTaskFormConditionService::getPreConditionResults()` |
| `MinTaskDurationEvaluator.php` (new file) | Any controller or route |
| `EmployeeTaskServiceProvider.php` (register) | Any existing evaluator |
| `throwFromResult()` (add case) | |

---

## 4. How the Registry-Driven Dispatch Works

### 4.1 The Dispatch Loop

The core of the system is `EmployeeTaskFormConditionService::evaluateAndThrow()`:

```php
private function evaluateAndThrow(array $map, ConditionContext $ctx): void
{
    foreach ($map as $condKey => $condData) {
        $condEnum = InternalProcessCondition::tryFrom($condKey);
        if ($condEnum === null) {
            continue;  // unknown condition key in DB → skip
        }

        $evaluator = $this->registry->get($condEnum);
        if ($evaluator === null) {
            continue;  // no evaluator registered → skip
        }

        $result = $evaluator->evaluate($condData, $ctx);
        if ($result !== null && ! $result->passed) {
            $this->throwFromResult($result);
        }
    }
}
```

**Key properties:**
- Iterates **all** conditions stored in the DB map — not a hard-coded list.
- Dispatches dynamically via the registry — not `if`/`switch` statements.
- Fails fast — throws on the first failing condition.
- Unknown conditions (no evaluator registered) are silently skipped, not errored.

### 4.2 The Precondition Results Loop

`getPreConditionResults()` uses `forFormGroup('precondition')` to get only precondition evaluators:

```php
foreach ($this->registry->forFormGroup('precondition') as $condKey => $evaluator) {
    $condData = $map[$condKey] ?? null;
    $condEnum = InternalProcessCondition::tryFrom($condKey);

    $result = $condData !== null
        ? $evaluator->evaluate($condData, $ctx)
        : null;

    // If not configured, show as passed (green checkmark)
    if ($result === null) {
        $result = new ConditionResult(
            key: $condEnum->value,
            labelAr: $condEnum->labelAr(),
            passed: true,
        );
    }

    // ... append to results array ...
}
```

This returns **all** precondition evaluators (even unconfigured ones) so the mobile app can show a fixed checklist UI.

### 4.3 The In-Form Preview

`getInFormConditionsPreview()` does **not** use evaluators — it only shapes the stored condition data for frontend display. This is intentional: the preview shows what constraints are configured, not whether they pass right now.

---

## 5. API Integration: How Conditions Are Applied

### 5.1 Task Creation Flow

```
POST /api/v1/employee-tasks
      │
      ▼
EmployeeTaskController::store()
      │
      ▼
EmployeeTaskRequestService::create()
      │
      ├── $this->formConditionService->checkCreateTaskConditions(
      │       userId, companyId, branchId,
      │       durationHours, taskDate,
      │       taskLatitude, taskLongitude,
      │       currentLatitude, currentLongitude
      │   )
      │       │
      │       ├── resolveConditionMap('createTask', companyId, branchId)
      │       │       → loads from ProcedureSetting via WorkflowEngine
      │       │
      │       ├── builds ConditionContext with all request data
      │       │
      │       └── evaluateAndThrow($map, $ctx)
      │               → dispatches to all registered evaluators
      │               → throws EmployeeTaskException on first failure (HTTP 422)
      │
      ├── if conditions pass → WorkflowEngine::previewResponsibles()
      │
      └── creates EmployeeTaskRequest + starts workflow
```

### 5.2 Task Start Flow

```
POST /api/v1/employee-tasks/{id}/start
      │
      ▼
EmployeeTaskController::start()
      │
      ▼
EmployeeTaskLifecycleService::start()
      │
      ├── $this->formConditionService->checkStartTaskConditions(
      │       $task, $user, $latitude, $longitude
      │   )
      │       │
      │       ├── resolveConditionMap('startTask', companyId, branchId)
      │       │
      │       ├── builds ConditionContext
      │       │
      │       └── evaluateAndThrow($map, $ctx)
      │               → dispatches to registered evaluators
      │               → throws on failure
      │
      └── if conditions pass → starts the task
```

### 5.3 Pre-Condition Check API (Mobile)

```
GET /api/v1/employee-tasks/pre-conditions?lat=...&lng=...
      │
      ▼
EmployeeTaskController::preConditions()
      │
      ▼
EmployeeTaskFormConditionService::getPreConditionResults()
      │
      └── Returns:
          {
            "all_passed": true|false,
            "conditions": [
              { "key": "allow_during_shift", "label_ar": "...", "passed": true, "message": null },
              { "key": "allow_on_holidays", "label_ar": "...", "passed": true, "message": null },
              { "key": "location_inside_work_area", "label_ar": "...", "passed": false, "message": "..." }
            ]
          }
```

### 5.4 In-Form Conditions Preview API (Mobile)

```
GET /api/v1/employee-tasks/in-form-conditions
      │
      ▼
EmployeeTaskController::inFormConditions()
      │
      ▼
EmployeeTaskFormConditionService::getInFormConditionsPreview()
      │
      └── Returns active in_form conditions with their constraints:
          [
            { "key": "max_task_duration", "label_ar": "...", "is_active": true, "mode": null, "constraints": {"max_hours": 8} },
            { "key": "max_scheduled_date_offset", "label_ar": "...", "is_active": true, "mode": "max_task_date", "constraints": {"max_days": 30} }
          ]
```

### 5.5 Condition Discovery API (Admin Frontend)

```
GET /api/v1/admin/procedure-settings/forms-conditions?type=createTask
      │
      ▼
InternalProcedureSettingController::formsConditions()
      │
      └── Returns condition definitions with settings_schema
          (used by admin UI to render condition configuration forms)
```

---

## 6. ConditionContext: The Data Bag

The `ConditionContext` carries all data any evaluator might need. This is the key to the Open/Closed Principle — new evaluators can access any field without changing method signatures.

| Field | Type | Used by |
|-------|------|---------|
| `userId` | `string` | Shift, Holiday, Location evaluators (load user + attendance) |
| `companyId` | `string` | (future use, e.g. company-level constraints) |
| `branchId` | `?string` | (future use, e.g. branch-level constraints) |
| `currentLatitude` | `?float` | AllowOutsideShiftEvaluator (employee's GPS) |
| `currentLongitude` | `?float` | AllowOutsideShiftEvaluator |
| `taskLatitude` | `?float` | InsideCustomLocationsEvaluator (task's GPS) |
| `taskLongitude` | `?float` | InsideCustomLocationsEvaluator |
| `durationHours` | `?float` | MaxTaskDurationEvaluator |
| `taskDate` | `?string` | MaxScheduledDateOffsetEvaluator |

**To add a new field:** add it to the constructor, update all call sites that build `ConditionContext`. Evaluators that don't need it simply ignore it.

---

## 7. ConditionResult: The Evaluation Output

| Field | Type | Purpose |
|-------|------|---------|
| `key` | `string` | Condition key (e.g. `'allow_during_shift'`) |
| `labelAr` | `string` | Arabic label for mobile UI |
| `passed` | `bool` | Whether the condition passed |
| `message` | `?string` | Human-readable failure message |
| `exception` | `?string` | Exception key for `throwFromResult()` mapping |
| `context` | `array` | Extra values needed by the exception factory (e.g. `['maxHours' => 8]`) |

**Return values from `evaluate()`:**
- `null` → condition not configured or not enforced. Skip silently.
- `ConditionResult` with `passed = true` → condition passed. Continue.
- `ConditionResult` with `passed = false` → condition failed. Throw exception.

---

## 8. Exception Mapping

The `throwFromResult()` method in `EmployeeTaskFormConditionService` maps `ConditionResult::$exception` strings to `EmployeeTaskException` factory methods:

| Exception key | Factory method | Context values used |
|---------------|---------------|-------------------|
| `notAllowedDuringShift` | `notAllowedDuringShift()` | — |
| `outsideShiftTimeWindow` | `outsideShiftTimeWindow()` | — |
| `notAllowedOnHolidays` | `notAllowedOnHolidays()` | — |
| `notAllowedOutsideLocation` | `notAllowedOutsideLocation()` | — |
| `taskDurationExceedsLimit` | `taskDurationExceedsLimit(int $maxHours)` | `context['maxHours']` |
| `taskDateTooFarInFuture` | `taskDateTooFarInFuture(int $maxDays)` | `context['maxDays']` |
| `taskDateExceedsContractEndDate` | `taskDateExceedsContractEndDate()` | — |
| `outsideCustomLocations` | `outsideCustomLocations()` | — |
| _(default)_ | `new EmployeeTaskException($message, 422)` | — |

**When adding a new condition**, add a new case to this `match` statement and a new factory method to `EmployeeTaskException`.

---

## 9. Backward Compatibility

### 9.1 Dual-Format Condition Storage

The system supports two storage formats in the `conditions` JSON column:

**New (rich array) format:**
```json
[
  {"key": "allow_during_shift", "is_active": true, "sort_order": 1, "settings": {"mode": "shift"}},
  {"key": "max_task_duration", "is_active": true, "sort_order": 2, "settings": {"max_hours": 8}}
]
```

**Old (flat associative) format:**
```json
{"allow_during_shift": true, "allow_on_holidays": false}
```

The `indexConditions()` method normalizes both into a keyed map:
```php
['allow_during_shift' => ['key' => 'allow_during_shift', 'is_active' => true, 'sort_order' => 0, 'settings' => []]]
```

Old DB rows continue to work without migration.

### 9.2 Unknown Condition Keys

If the DB contains a condition key that has no registered evaluator (e.g. after a rollback), `evaluateAndThrow()` silently skips it. This prevents breakage during deployments.

---

## 10. Testing Conditions

### 10.1 Unit Testing an Evaluator

```php
public function test_max_task_duration_passes_when_under_limit(): void
{
    $evaluator = new MaxTaskDurationEvaluator();
    $ctx = new ConditionContext(
        userId: 'user-1',
        companyId: 'company-1',
        branchId: null,
        durationHours: 4.0,
    );

    $result = $evaluator->evaluate(
        ['key' => 'max_task_duration', 'is_active' => true, 'sort_order' => 1, 'settings' => ['max_hours' => 8]],
        $ctx,
    );

    $this->assertNotNull($result);
    $this->assertTrue($result->passed);
}

public function test_max_task_duration_fails_when_over_limit(): void
{
    $evaluator = new MaxTaskDurationEvaluator();
    $ctx = new ConditionContext(
        userId: 'user-1',
        companyId: 'company-1',
        branchId: null,
        durationHours: 12.0,
    );

    $result = $evaluator->evaluate(
        ['key' => 'max_task_duration', 'is_active' => true, 'sort_order' => 1, 'settings' => ['max_hours' => 8]],
        $ctx,
    );

    $this->assertNotNull($result);
    $this->assertFalse($result->passed);
    $this->assertSame('taskDurationExceedsLimit', $result->exception);
    $this->assertSame(8, $result->context['maxHours']);
}
```

### 10.2 Integration Testing via API

```php
public function test_create_task_rejected_when_duration_exceeds_limit(): void
{
    // 1. Create a procedure setting with max_task_duration active
    // 2. POST /employee-tasks with durationHours = 12 (exceeds max_hours = 8)
    // 3. Assert response status 422
    // 4. Assert response message contains "cannot exceed 8 hours"
}
```

---

## 11. Complete File Reference

### New Files Created

| File | Purpose |
|------|---------|
| `modules/EmployeeTask/Conditions/ConditionEvaluator.php` | Interface — strategy contract |
| `modules/EmployeeTask/Conditions/ConditionContext.php` | DTO — data bag for evaluators |
| `modules/EmployeeTask/Conditions/ConditionResult.php` | DTO — evaluation result |
| `modules/EmployeeTask/Conditions/ConditionEvaluatorRegistry.php` | Registry — maps condition enum → evaluator |
| `modules/EmployeeTask/Conditions/ResolvesUserAttendance.php` | Trait — shared user/timezone/attendance logic |
| `modules/EmployeeTask/Conditions/AllowDuringShiftEvaluator.php` | Evaluator — shift/specific_time gating |
| `modules/EmployeeTask/Conditions/AllowOnHolidaysEvaluator.php` | Evaluator — holiday gating |
| `modules/EmployeeTask/Conditions/AllowOutsideShiftEvaluator.php` | Evaluator — work area location check |
| `modules/EmployeeTask/Conditions/InsideCustomLocationsEvaluator.php` | Evaluator — custom polygon area check |
| `modules/EmployeeTask/Conditions/MaxTaskDurationEvaluator.php` | Evaluator — max task duration check |
| `modules/EmployeeTask/Conditions/MaxScheduledDateOffsetEvaluator.php` | Evaluator — max scheduled date / contract end check |

### Modified Files

| File | Change |
|------|--------|
| `modules/EmployeeTask/Services/EmployeeTaskFormConditionService.php` | Replaced scattered `if`/`assert*` calls with registry-driven `evaluateAndThrow()` dispatch; `getPreConditionResults()` now iterates registry's precondition evaluators; `getInFormConditionsPreview()` uses `resolveConditionMap()`; added `throwFromResult()` exception mapping |
| `modules/EmployeeTask/Providers/EmployeeTaskServiceProvider.php` | Registers `ConditionEvaluatorRegistry` with all 6 evaluators as singletons |

### Existing Evaluators (Registered)

| Evaluator | Condition | Form Group | Exception Key |
|-----------|-----------|------------|---------------|
| `AllowDuringShiftEvaluator` | `allow_during_shift` | precondition | `notAllowedDuringShift` / `outsideShiftTimeWindow` |
| `AllowOnHolidaysEvaluator` | `allow_on_holidays` | precondition | `notAllowedOnHolidays` |
| `AllowOutsideShiftEvaluator` | `allow_outside_shift` | precondition | `notAllowedOutsideLocation` |
| `InsideCustomLocationsEvaluator` | `inside_custom_locations` | in_form | `outsideCustomLocations` |
| `MaxTaskDurationEvaluator` | `max_task_duration` | in_form | `taskDurationExceedsLimit` |
| `MaxScheduledDateOffsetEvaluator` | `max_scheduled_date_offset` | in_form | `taskDateTooFarInFuture` / `taskDateExceedsContractEndDate` |

---

## Quick Recipe: Adding a New Condition

```
1. Add enum case to InternalProcessCondition
2. Register in InternalProcessForm::conditions()
3. Add exception factory to EmployeeTaskException
4. Create evaluator class implementing ConditionEvaluator
5. Register evaluator in EmployeeTaskServiceProvider
6. Add exception case to throwFromResult()
7. Test
```

**Zero changes to:**
- `EmployeeTaskFormConditionService::checkCreateTaskConditions()`
- `EmployeeTaskFormConditionService::evaluateAndThrow()`
- `EmployeeTaskFormConditionService::getPreConditionResults()`
- Any controller, route, or existing evaluator
