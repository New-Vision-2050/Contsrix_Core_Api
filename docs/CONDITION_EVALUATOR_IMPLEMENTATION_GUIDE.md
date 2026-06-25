# Condition Evaluator Implementation Guide

> **Complete guide for implementing condition evaluation from scratch and applying it in the API.**
>
> **Last updated:** 2026-06-25 (rev 2 — centralized to `ProcedureSetting\Conditions`)
>
> **Rev 2 changes:** Core infrastructure (interface, DTOs, registry, evaluation engine) moved to `modules/ProcedureSetting/Conditions/` so any module can reuse it. `ExceptionResolver` interface added for module-specific exception mapping. `getInFormConditionsPreview()` now auto-generates from `InternalProcessCondition::toPreview()`.

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Core Components (Shared Infrastructure)](#2-core-components-shared-infrastructure)
3. [Module-Specific Components (EmployeeTask)](#2b-module-specific-components-employeetask)
4. [Step-by-Step: Implementing a New Condition from Scratch](#3-step-by-step-implementing-a-new-condition-from-scratch)
5. [How the Central Dispatch Works](#4-how-the-central-dispatch-works)
6. [Automatic In-Form Preview via toPreview()](#4b-automatic-in-form-preview-via-topreview)
7. [API Integration: How Conditions Are Applied](#5-api-integration-how-conditions-are-applied)
8. [ConditionContext: The Data Bag](#6-conditioncontext-the-data-bag)
9. [ConditionResult: The Evaluation Output](#7-conditionresult-the-evaluation-output)
10. [Exception Mapping](#8-exception-mapping)
11. [Backward Compatibility](#9-backward-compatibility)
12. [Reusing the Engine in Other Modules](#9b-reusing-the-engine-in-other-modules)
13. [Testing Conditions](#10-testing-conditions)
14. [Complete File Reference](#11-complete-file-reference)

---

## 1. Architecture Overview

The condition evaluation system follows the **Open/Closed Principle** (OCP):
- **Open for extension** — new conditions are added by creating a new evaluator class and registering it.
- **Closed for modification** — the core dispatch logic never changes when conditions are added or removed.

### Two-Layer Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│  modules/ProcedureSetting/Conditions/  (SHARED INFRASTRUCTURE)  │
│                                                                 │
│  ConditionEvaluator        (interface)                          │
│  ConditionContext          (DTO — data bag)                     │
│  ConditionResult           (DTO — evaluation result)            │
│  ConditionEvaluatorRegistry (condition enum → evaluator map)    │
│  ExceptionResolver         (interface — module-specific throws) │
│  ConditionEvaluationService (central engine — evaluateAndThrow) │
└─────────────────────────────────────────────────────────────────┘
                              ▲
                              │ uses
                              │
┌─────────────────────────────────────────────────────────────────┐
│  modules/EmployeeTask/Conditions/  (MODULE-SPECIFIC)            │
│                                                                 │
│  AllowDuringShiftEvaluator     ─┐                               │
│  AllowOnHolidaysEvaluator       │  implement ConditionEvaluator │
│  AllowOutsideShiftEvaluator     │                               │
│  InsideCustomLocationsEvaluator │                               │
│  MaxTaskDurationEvaluator       │                               │
│  MaxScheduledDateOffsetEvaluator┘                               │
│  EmployeeTaskExceptionResolver  ── implements ExceptionResolver │
│  ResolvesUserAttendance        ── trait (shared user logic)     │
│  ConditionEvaluator/Context/Result/Registry ── deprecated stubs │
└─────────────────────────────────────────────────────────────────┘
```

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
      ├── builds ConditionContext  →  carries all request data
      └── ConditionEvaluationService::evaluateAndThrow($registry, $map, $ctx, $resolver)
              │
              ├── for each condition key:
              │     ├── look up evaluator in ConditionEvaluatorRegistry
              │     ├── evaluator->evaluate($condData, $ctx)
              │     └── if result->passed === false → resolver->throwFromResult()
              │
              └── all passed → return (no exception)
      │
      ▼
WorkflowEngine::previewResponsibles()  ← proceeds only if all conditions pass
```

### Design Pattern: Strategy + Registry + Central Engine

Each condition is a **Strategy** (implements `ConditionEvaluator` interface). The **Registry** (`ConditionEvaluatorRegistry`) maps condition enum values to their strategy implementations. The **Central Engine** (`ConditionEvaluationService`) runs the dispatch loop — it's module-agnostic and reusable. Each module provides its own registry + exception resolver.

---

## 2. Core Components (Shared Infrastructure)

> All files in `modules/ProcedureSetting/Conditions/`. These are module-agnostic — any module can use them.

### 2.1 ConditionEvaluator (Interface)

**File:** `modules/ProcedureSetting/Conditions/ConditionEvaluator.php`

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

**File:** `modules/ProcedureSetting/Conditions/ConditionContext.php`

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

**File:** `modules/ProcedureSetting/Conditions/ConditionResult.php`

```php
final class ConditionResult
{
    public function __construct(
        public readonly string  $key,
        public readonly string  $labelAr,
        public readonly bool    $passed,
        public readonly ?string $message = null,
        public readonly ?string $exception = null,   // exception key for ExceptionResolver
        public readonly array   $context = [],       // extra values for exception factory
    ) {}
}
```

### 2.4 ConditionEvaluatorRegistry

**File:** `modules/ProcedureSetting/Conditions/ConditionEvaluatorRegistry.php`

Maps `InternalProcessCondition` enum values to evaluator instances. Provides:
- `get(InternalProcessCondition)` — returns the evaluator for a condition (or null).
- `forFormGroup(string)` — returns all evaluators in a given group (`'precondition'` or `'in_form'`).
- `register(ConditionEvaluator)` — adds an evaluator at runtime.

### 2.5 ExceptionResolver (Interface)

**File:** `modules/ProcedureSetting/Conditions/ExceptionResolver.php`

```php
interface ExceptionResolver
{
    /**
     * Throw the appropriate exception for a failed condition result.
     *
     * @throws \Throwable (always — this method never returns)
     */
    public function throwFromResult(ConditionResult $result): never;
}
```

Each module implements this to map `ConditionResult::$exception` keys to its own exception classes. This keeps the central engine decoupled from any module's exception hierarchy.

### 2.6 ConditionEvaluationService (Central Engine)

**File:** `modules/ProcedureSetting/Conditions/ConditionEvaluationService.php`

Module-agnostic dispatch engine. Registered as a singleton in `ProcedureSettingServiceProvider`. Two methods:

- `evaluateAndThrow($registry, $map, $ctx, $resolver)` — iterates all conditions, dispatches to evaluators, throws on first failure via the `ExceptionResolver`.
- `evaluateForResults($registry, $map, $ctx, $formGroup)` — iterates evaluators in a form group, returns `{all_passed, conditions[]}` without throwing (for mobile precondition checklists).

```php
final class ConditionEvaluationService
{
    public function evaluateAndThrow(
        ConditionEvaluatorRegistry $registry,
        array $map,
        ConditionContext $ctx,
        ExceptionResolver $resolver,
    ): void {
        foreach ($map as $condKey => $condData) {
            $condEnum = InternalProcessCondition::tryFrom($condKey);
            if ($condEnum === null) continue;

            $evaluator = $registry->get($condEnum);
            if ($evaluator === null) continue;

            $result = $evaluator->evaluate($condData, $ctx);
            if ($result !== null && ! $result->passed) {
                $resolver->throwFromResult($result);
            }
        }
    }

    public function evaluateForResults(
        ConditionEvaluatorRegistry $registry,
        array $map,
        ConditionContext $ctx,
        string $formGroup,
    ): array {
        // iterates $registry->forFormGroup($formGroup)
        // returns ['all_passed' => bool, 'conditions' => [...]]
    }
}
```

**Key properties:**
- Holds no state — registry and resolver are passed per call so each module gets isolated dispatch.
- Unknown conditions (no evaluator registered) are silently skipped.
- Fails fast — throws on the first failing condition.

---

## 2b. Module-Specific Components (EmployeeTask)

> These files live in `modules/EmployeeTask/Conditions/`. They contain EmployeeTask-specific logic only.

### 2b.1 EmployeeTaskExceptionResolver

**File:** `modules/EmployeeTask/Conditions/EmployeeTaskExceptionResolver.php`

Implements `ExceptionResolver`. Maps `ConditionResult::$exception` keys to `EmployeeTaskException` factory methods:

```php
final class EmployeeTaskExceptionResolver implements ExceptionResolver
{
    public function throwFromResult(ConditionResult $result): never
    {
        match ($result->exception) {
            'notAllowedDuringShift'          => throw EmployeeTaskException::notAllowedDuringShift(),
            'outsideShiftTimeWindow'         => throw EmployeeTaskException::outsideShiftTimeWindow(),
            'notAllowedOnHolidays'           => throw EmployeeTaskException::notAllowedOnHolidays(),
            'notAllowedOutsideLocation'      => throw EmployeeTaskException::notAllowedOutsideLocation(),
            'taskDurationExceedsLimit'       => throw EmployeeTaskException::taskDurationExceedsLimit(
                (int) ($result->context['maxHours'] ?? 0),
            ),
            'taskDateTooFarInFuture'         => throw EmployeeTaskException::taskDateTooFarInFuture(
                (int) ($result->context['maxDays'] ?? 0),
            ),
            'taskDateExceedsContractEndDate' => throw EmployeeTaskException::taskDateExceedsContractEndDate(),
            'outsideCustomLocations'         => throw EmployeeTaskException::outsideCustomLocations(),
            default                          => throw new EmployeeTaskException($result->message ?? 'Condition failed.', 422),
        };
    }
}
```

### 2b.2 ResolvesUserAttendance (Trait)

**File:** `modules/EmployeeTask/Conditions/ResolvesUserAttendance.php`

Shared logic for evaluators that need to:
- Load a user with branch/address/country/timezones relations.
- Resolve the user's branch timezone.
- Check if the current time falls within any scheduled work period.

Used by: `AllowDuringShiftEvaluator`, `AllowOnHolidaysEvaluator`, `AllowOutsideShiftEvaluator`.

### 2b.3 Deprecated Backward-Compat Stubs

The following files in `modules/EmployeeTask/Conditions/` are **deprecated stubs** that extend the shared classes. They exist so old imports don't break during transition. New code should import directly from `Modules\ProcedureSetting\Conditions`.

| Stub | Extends |
|------|---------|
| `ConditionEvaluator.php` | `ProcedureSetting\Conditions\ConditionEvaluator` (interface) |
| `ConditionContext.php` | `ProcedureSetting\Conditions\ConditionContext` |
| `ConditionResult.php` | `ProcedureSetting\Conditions\ConditionResult` |
| `ConditionEvaluatorRegistry.php` | `ProcedureSetting\Conditions\ConditionEvaluatorRegistry` |

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

use Modules\ProcedureSetting\Conditions\ConditionContext;
use Modules\ProcedureSetting\Conditions\ConditionEvaluator;
use Modules\ProcedureSetting\Conditions\ConditionResult;
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

**File:** `modules/EmployeeTask/Conditions/EmployeeTaskExceptionResolver.php`

In the `throwFromResult()` method, add:
```php
'taskDurationBelowMinimum' => throw EmployeeTaskException::taskDurationBelowMinimum(
    (int) ($result->context['minHours'] ?? 0),
),
```

### Step 7: Verify

- **No changes needed** to `checkCreateTaskConditions()`, `getPreConditionResults()`, `ConditionEvaluationService`, or any existing evaluator.
- The new condition is automatically picked up because `ConditionEvaluationService::evaluateAndThrow()` iterates all conditions in the map and dispatches to whatever evaluator is registered.
- The new condition's in-form preview is automatically generated from `settingsSchema()` via `toPreview()` — no match block to update.
- Run `php -l` on all new/modified files.
- Test with a procedure setting that has `min_task_duration` active.

### Summary: What You Created vs. What You Didn't Touch

| Created/Modified | Did NOT touch |
|-----------------|---------------|
| `InternalProcessCondition.php` (add enum case + settingsSchema) | `EmployeeTaskFormConditionService::checkCreateTaskConditions()` |
| `InternalProcessForm.php` (register in form) | `ConditionEvaluationService::evaluateAndThrow()` |
| `EmployeeTaskException.php` (add factory) | `ConditionEvaluationService::evaluateForResults()` |
| `MinTaskDurationEvaluator.php` (new file) | `EmployeeTaskFormConditionService::getInFormConditionsPreview()` |
| `EmployeeTaskServiceProvider.php` (register evaluator) | Any controller or route |
| `EmployeeTaskExceptionResolver::throwFromResult()` (add case) | Any existing evaluator |
| `ProcedureSettingServiceProvider.php` (already registers engine) | Any shared infrastructure file |

---

## 4. How the Central Dispatch Works

### 4.1 The Dispatch Loop (evaluateAndThrow)

The core of the system is `ConditionEvaluationService::evaluateAndThrow()` — a module-agnostic method that lives in `ProcedureSetting\Conditions`:

```php
public function evaluateAndThrow(
    ConditionEvaluatorRegistry $registry,
    array $map,
    ConditionContext $ctx,
    ExceptionResolver $resolver,
): void {
    foreach ($map as $condKey => $condData) {
        $condEnum = InternalProcessCondition::tryFrom($condKey);
        if ($condEnum === null) {
            continue;  // unknown condition key in DB → skip
        }

        $evaluator = $registry->get($condEnum);
        if ($evaluator === null) {
            continue;  // no evaluator registered → skip
        }

        $result = $evaluator->evaluate($condData, $ctx);
        if ($result !== null && ! $result->passed) {
            $resolver->throwFromResult($result);
        }
    }
}
```

`EmployeeTaskFormConditionService` calls it like this:

```php
$this->evaluationService->evaluateAndThrow($this->registry, $map, $ctx, $this->resolver);
```

**Key properties:**
- Iterates **all** conditions stored in the DB map — not a hard-coded list.
- Dispatches dynamically via the registry — not `if`/`switch` statements.
- Fails fast — throws on the first failing condition.
- Unknown conditions (no evaluator registered) are silently skipped, not errored.
- The engine doesn't know about `EmployeeTaskException` — it delegates to `$resolver`.

### 4.2 The Precondition Results Loop (evaluateForResults)

`ConditionEvaluationService::evaluateForResults()` iterates evaluators in a form group and returns pass/fail results without throwing:

```php
public function evaluateForResults(
    ConditionEvaluatorRegistry $registry,
    array $map,
    ConditionContext $ctx,
    string $formGroup,
): array {
    foreach ($registry->forFormGroup($formGroup) as $condKey => $evaluator) {
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

    return ['all_passed' => $allPassed, 'conditions' => $results];
}
```

`EmployeeTaskFormConditionService` calls it like this:

```php
return $this->evaluationService->evaluateForResults(
    $this->registry, $map, $ctx, 'precondition',
);
```

This returns **all** precondition evaluators (even unconfigured ones) so the mobile app can show a fixed checklist UI.

---

## 4b. Automatic In-Form Preview via toPreview()

### The Problem (Before)

`getInFormConditionsPreview()` had a hard-coded `match` block that needed updating every time a new in_form condition was added:

```php
$preview = match ($condEnum) {
    InternalProcessCondition::MaxTaskDuration => ['mode' => null, 'constraints' => ['max_hours' => ...]],
    InternalProcessCondition::MaxScheduledDateOffset => ['mode' => ..., 'constraints' => ...],
    InternalProcessCondition::InsideCustomLocations => ['mode' => null, 'constraints' => ['polygons' => ...]],
    // ... had to add every new condition here ...
    default => ['mode' => null, 'constraints' => []],
};
```

### The Solution (After)

`InternalProcessCondition::toPreview()` auto-generates the preview from `settingsSchema()` + stored settings:

```php
$preview = $condEnum->toPreview($item['settings'] ?? []);
```

### How toPreview() Works

```php
public function toPreview(array $settings): array
{
    $schema = $this->settingsSchema();

    // 1. Extract mode (any schema field with key='mode')
    $mode = null;
    foreach ($schema as $field) {
        if (($field['key'] ?? null) === 'mode') {
            $mode = $settings['mode'] ?? $field['default'] ?? null;
            break;
        }
    }

    // 2. Build constraints from schema (excluding mode itself)
    $constraints = [];
    foreach ($schema as $field) {
        $key = $field['key'] ?? null;
        if ($key === null || $key === 'mode') continue;

        // Skip fields visible only in a different mode
        if (isset($field['visible_when']) && ($field['visible_when']['value'] ?? null) !== $mode) {
            continue;
        }

        $constraints[$key] = $settings[$key] ?? $field['default'] ?? null;
    }

    // 3. Legacy conditions with no schema: pass through raw settings
    if ($schema === [] && $settings !== []) {
        $constraints = $settings;
    }

    return ['mode' => $mode, 'constraints' => $constraints];
}
```

**What this means:** Adding a new in_form condition with a `settingsSchema()` automatically makes it appear in the preview API — **zero changes** to `getInFormConditionsPreview()`.

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
      │       └── ConditionEvaluationService::evaluateAndThrow($registry, $map, $ctx, $resolver)
      │               → dispatches to all registered evaluators
      │               → throws EmployeeTaskException via $resolver on first failure (HTTP 422)
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
      │       └── ConditionEvaluationService::evaluateAndThrow($registry, $map, $ctx, $resolver)
      │               → dispatches to registered evaluators
      │               → throws on failure via $resolver
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
      └── For each active in_form condition:
          ├── $condEnum->toPreview($settings)  ← auto-generated from settingsSchema()
          └── Returns:
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

The `EmployeeTaskExceptionResolver::throwFromResult()` method maps `ConditionResult::$exception` strings to `EmployeeTaskException` factory methods:

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

## 9b. Reusing the Engine in Other Modules

Any module (ClientRequest, future modules) can reuse the central condition evaluation engine with 5 steps:

### Step 1: Create evaluators

Each evaluator implements `Modules\ProcedureSetting\Conditions\ConditionEvaluator` and lives in your module's `Conditions/` directory.

```php
namespace Modules\ClientRequest\Conditions;

use Modules\ProcedureSetting\Conditions\ConditionEvaluator;
use Modules\ProcedureSetting\Conditions\ConditionContext;
use Modules\ProcedureSetting\Conditions\ConditionResult;

final class YourEvaluator implements ConditionEvaluator
{
    public function condition(): InternalProcessCondition { ... }
    public function evaluate(array $data, ConditionContext $ctx): ?ConditionResult { ... }
}
```

### Step 2: Create an ExceptionResolver

```php
namespace Modules\ClientRequest\Conditions;

use Modules\ProcedureSetting\Conditions\ExceptionResolver;
use Modules\ProcedureSetting\Conditions\ConditionResult;

final class ClientRequestExceptionResolver implements ExceptionResolver
{
    public function throwFromResult(ConditionResult $result): never
    {
        match ($result->exception) {
            'yourExceptionKey' => throw ClientRequestException::yourError(...),
            default => throw new ClientRequestException($result->message ?? 'Condition failed.', 422),
        };
    }
}
```

### Step 3: Register in your service provider

```php
// In YourModuleServiceProvider::register()

use Modules\ProcedureSetting\Conditions\ConditionEvaluatorRegistry;
use Modules\ProcedureSetting\Conditions\ConditionEvaluationService;

$this->app->singleton(ConditionEvaluatorRegistry::class, function ($app) {
    return new ConditionEvaluatorRegistry([
        $app->make(YourEvaluator::class),
    ]);
});

$this->app->singleton(YourEvaluator::class);
$this->app->singleton(ClientRequestExceptionResolver::class);
// ConditionEvaluationService is already registered by ProcedureSettingServiceProvider
```

### Step 4: Build a thin condition service

```php
final class ClientRequestFormConditionService
{
    public function __construct(
        private readonly ConditionEvaluatorRegistry   $registry,
        private readonly ConditionEvaluationService   $evaluationService,
        private readonly ClientRequestExceptionResolver $resolver,
        private readonly WorkflowEngine               $engine,
    ) {}

    public function checkCreateConditions(string $userId, string $companyId, ?string $branchId): void
    {
        $map = $this->resolveConditionMap('createClientRequest', $companyId, $branchId);
        if ($map === null) return;

        $ctx = new ConditionContext(userId: $userId, companyId: $companyId, branchId: $branchId);

        $this->evaluationService->evaluateAndThrow($this->registry, $map, $ctx, $this->resolver);
    }
}
```

### Step 5: Call from your controller/service

```php
$this->formConditionService->checkCreateConditions($userId, $companyId, $branchId);
```

**Zero changes to `ProcedureSetting` module needed** — the central engine is already module-agnostic.

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

If the DB contains a condition key that has no registered evaluator (e.g. after a rollback), `ConditionEvaluationService::evaluateAndThrow()` silently skips it. This prevents breakage during deployments.

### 9.3 Deprecated EmployeeTask Stubs

The old files in `modules/EmployeeTask/Conditions/` (`ConditionEvaluator.php`, `ConditionContext.php`, `ConditionResult.php`, `ConditionEvaluatorRegistry.php`) are now **deprecated stubs** that extend the shared classes in `ProcedureSetting\Conditions`. They exist so existing imports don't break. New code should import directly from `Modules\ProcedureSetting\Conditions`.

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

### Shared Infrastructure (ProcedureSetting\Conditions)

| File | Purpose |
|------|---------|
| `modules/ProcedureSetting/Conditions/ConditionEvaluator.php` | **New** — interface (strategy contract) |
| `modules/ProcedureSetting/Conditions/ConditionContext.php` | **New** — DTO (data bag for evaluators) |
| `modules/ProcedureSetting/Conditions/ConditionResult.php` | **New** — DTO (evaluation result) |
| `modules/ProcedureSetting/Conditions/ConditionEvaluatorRegistry.php` | **New** — registry (maps condition enum → evaluator) |
| `modules/ProcedureSetting/Conditions/ExceptionResolver.php` | **New** — interface (module-specific exception mapping) |
| `modules/ProcedureSetting/Conditions/ConditionEvaluationService.php` | **New** — central engine (evaluateAndThrow + evaluateForResults) |

### Module-Specific (EmployeeTask\Conditions)

| File | Purpose |
|------|---------|
| `modules/EmployeeTask/Conditions/EmployeeTaskExceptionResolver.php` | **New** — maps ConditionResult → EmployeeTaskException |
| `modules/EmployeeTask/Conditions/ResolvesUserAttendance.php` | Trait — shared user/timezone/attendance logic |
| `modules/EmployeeTask/Conditions/AllowDuringShiftEvaluator.php` | Evaluator — shift/specific_time gating |
| `modules/EmployeeTask/Conditions/AllowOnHolidaysEvaluator.php` | Evaluator — holiday gating |
| `modules/EmployeeTask/Conditions/AllowOutsideShiftEvaluator.php` | Evaluator — work area location check |
| `modules/EmployeeTask/Conditions/InsideCustomLocationsEvaluator.php` | Evaluator — custom polygon area check |
| `modules/EmployeeTask/Conditions/MaxTaskDurationEvaluator.php` | Evaluator — max task duration check |
| `modules/EmployeeTask/Conditions/MaxScheduledDateOffsetEvaluator.php` | Evaluator — max scheduled date / contract end check |
| `modules/EmployeeTask/Conditions/ConditionEvaluator.php` | **Deprecated stub** — extends shared interface |
| `modules/EmployeeTask/Conditions/ConditionContext.php` | **Deprecated stub** — extends shared DTO |
| `modules/EmployeeTask/Conditions/ConditionResult.php` | **Deprecated stub** — extends shared DTO |
| `modules/EmployeeTask/Conditions/ConditionEvaluatorRegistry.php` | **Deprecated stub** — extends shared registry |

### Modified Files

| File | Change |
|------|--------|
| `modules/EmployeeTask/Services/EmployeeTaskFormConditionService.php` | Delegates to `ConditionEvaluationService::evaluateAndThrow()` + `evaluateForResults()`; `getInFormConditionsPreview()` now uses `InternalProcessCondition::toPreview()` (no more match block); removed `evaluateAndThrow()` + `throwFromResult()` (moved to central engine + resolver) |
| `modules/EmployeeTask/Providers/EmployeeTaskServiceProvider.php` | Registers `ConditionEvaluatorRegistry` (from `ProcedureSetting\Conditions`) + all 6 evaluators + `EmployeeTaskExceptionResolver` as singletons |
| `modules/ProcedureSetting/Providers/ProcedureSettingServiceProvider.php` | Registers `ConditionEvaluationService` as singleton |
| `modules/Shared/InternalProcessType/Enums/InternalProcessCondition.php` | Added `toPreview()` method — auto-generates `{mode, constraints}` from `settingsSchema()` + stored settings |

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
1. Add enum case to InternalProcessCondition (with labelAr, category, formGroup, settingsSchema)
2. Register in InternalProcessForm::conditions()
3. Add exception factory to EmployeeTaskException
4. Create evaluator class implementing ConditionEvaluator
   (import from Modules\ProcedureSetting\Conditions)
5. Register evaluator in EmployeeTaskServiceProvider
6. Add exception case to EmployeeTaskExceptionResolver::throwFromResult()
7. Test
```

**Zero changes to:**
- `EmployeeTaskFormConditionService::checkCreateTaskConditions()`
- `ConditionEvaluationService::evaluateAndThrow()` or `evaluateForResults()`
- `EmployeeTaskFormConditionService::getInFormConditionsPreview()` (auto via `toPreview()`)
- `EmployeeTaskFormConditionService::getPreConditionResults()`
- Any controller, route, or existing evaluator
- Any shared infrastructure file in `ProcedureSetting\Conditions`
