# Attendance Module — Complete Technical Reference

> **Purpose of this document:** A self-contained playbook for any developer or AI assistant working inside the Attendance module. Every class, interface, constant, relationship, business rule, and data-flow is documented here with the full implementation details needed to safely read, modify, or extend the module without needing to open every file individually.
>
> **Last updated:** 2026-06-18 — Added §24.11: Procedure & Condition Integration — documents how `EmployeeTaskFormConditionService` consumes `AttendanceConstraintService::getTodaysWorkRulesForUser()` to gate `createTask` on shift/holiday status, and how `EmployeeTaskLocationService::isWithinTaskRadius()` gates `endTask` on GPS location. Added INV-T11.

---

## Table of Contents

1. [Module Location & Loading](#1-module-location--loading)
2. [Architecture Overview](#2-architecture-overview)
3. [Database Schema](#3-database-schema)
   - [3.6 `attendance_constraint_locations` table](#36-attendance_constraint_locations-table) ← **NEW**
4. [Domain Layer (Pure Logic)](#4-domain-layer-pure-logic)
5. [Models](#5-models)
   - [5.4 AttendanceConstraint Model (extended)](#54-attendanceconstraint-model-extended) ← **NEW**
   - [5.5 AttendanceConstraintLocation Model](#55-attendanceconstraintlocation-model) ← **NEW**
6. [Services — Application Layer](#6-services--application-layer)
   - [6.7 AttendanceConstraintService — Location Merging](#67-attendanceconstraintservice--location-merging-updated) ← **UPDATED**
7. [DTOs (Data Transfer Objects)](#7-dtos-data-transfer-objects)
8. [Controllers](#8-controllers)
   - [8.3 AttendanceConstraintController — New Methods](#83-attendanceconstraintcontroller--new-methods-2026-05-14) ← **NEW** (getShifts, assignShifts, updateRules added)
9. [Jobs](#9-jobs)
10. [Events & Listeners](#10-events--listeners)
11. [Exceptions](#11-exceptions)
12. [Presenters](#12-presenters)
13. [HTTP Layer — Requests & Routes](#13-http-layer--requests--routes)
14. [Console Commands & Schedules](#14-console-commands--schedules)
15. [Service Container & Octane Safety](#15-service-container--octane-safety)
16. [Business Rules Encyclopaedia](#16-business-rules-encyclopaedia)
17. [Full Clock-In Flow (Step-by-Step)](#17-full-clock-in-flow-step-by-step)
18. [Full Clock-Out Flow (Step-by-Step)](#18-full-clock-out-flow-step-by-step)
19. [Auto-Close Flow (Step-by-Step)](#19-auto-close-flow-step-by-step)
20. [Timezone Strategy](#20-timezone-strategy)
21. [Concurrency & Race Conditions](#21-concurrency--race-conditions)
22. [Test Suite Map](#22-test-suite-map)
23. [Invariants Checklist (Dangerous Traps)](#23-invariants-checklist-dangerous-traps)
    - [INV-13](#inv-13-always-parse-attendance-datetime-strings-with-the-branch-timezone-as-second-argument): Carbon parsing trap — never `->setTimezone()` on DB strings
    - [INV-14](#inv-14-scheduleautocloseatmaxovertime-separates-trigger-time-from-stored-time): `scheduleAutoCloseAtMaxOvertime` — trigger time ≠ stored `clock_out_time`
    - [INV-15](#inv-15-always-use-toiso8601string-when-passing-datetimes-through-job-constructors): Always use `->toIso8601String()` in job constructors, never `->format('Y-m-d H:i:s')`
    - [INV-16](#inv-16-all-attendance-hour-fields-leaving-the-api-must-be-hhmm-strings-via-hoursformatter): Hour/minute fields in API payloads must be HH:MM strings (via `HoursFormatter`), never raw decimals
    - [INV-17](#inv-17-gettodaysworkrulesforuser-must-use-the-current-time-on-today-never-midnight): `getTodaysWorkRulesForUser` must use current time on today — never midnight from a bare date string
24. [EmployeeTask Module Integration](#24-employeetask-module-integration) ← **NEW 2026-05-18**
    - [24.1 What Was Built](#241-what-was-built)
    - [24.2 Architectural Boundary (What Was NOT Changed)](#242-architectural-boundary-what-was-not-changed)
    - [24.3 How Task Hours Integrate With Attendance Hours](#243-how-task-hours-integrate-with-attendance-hours)
    - [24.4 Radius Snapshot Pattern](#244-radius-snapshot-pattern-mirrors-attendance-convention)
    - [24.5 New Tables](#245-new-tables)
    - [24.6 Auto-Close Jobs](#246-auto-close-jobs)
    - [24.7 Status State Machine](#247-status-state-machine)
    - [24.8 API Endpoints (17 total)](#248-api-endpoints-17-total)
    - [24.9 Key Invariants (INV-T1 through INV-T10)](#249-key-invariants-employeetask-specific)
    - [24.10 Permissions](#2410-permissions)
    - [24.11 Procedure & Condition Integration](#2411-procedure--condition-integration) ← **NEW 2026-06-18**
    - [INV-18](#inv-18-re-clock-in-lateness-anchor-must-filter-previous-attendances-by-scheduled-period): Re-clock-in lateness anchor must filter previous rows by scheduled period (`start_time` + `end_time`), not by date alone
    - [INV-19](#inv-19-lateness-grace-lookup-reads-per-day-rules-from-weekly_scheduledaylateness_rules): Lateness grace-period lookup must read per-day rules from `weekly_schedule.{day}.lateness_rules`, not `time_rules.lateness_rules`
    - [INV-20](#inv-20-additional_locations-in-user-constrainttoday--mirrors-the-location-validation-used-at-clock-in): `additional_locations` in `user-constraint/today` — mirrors the location validation used at clock-in
    - [INV-T11](#inv-t11--condition-check-must-fire-before-workflow-start): Condition check must fire before workflow start

---

## 1. Module Location & Loading

```
/modules/Attendance/
├── Config/
│   ├── config.php            ← module config (constraint defaults, location radius, etc.)
│   └── permissions.php       ← permission → route name mappings
├── Controllers/
├── Database/migrations/
├── Domain/
│   ├── Breaks/
│   ├── Calculator/
│   └── Time/
├── DTO/
├── Events/
├── Exceptions/
├── Jobs/
├── Listeners/
├── Models/
├── Presenters/
├── Providers/
│   ├── AttendanceServiceProvider.php   ← main provider; registers everything
│   ├── ConstraintServiceProvider.php   ← registers constraint services
│   └── RouteServiceProvider.php        ← loads routes
├── Repositories/
├── Routes/
├── Services/
└── Tests/
```

**Registration chain:**
`config/app.php` → `Modules\Attendance\Providers\AttendanceServiceProvider` →
registers `RouteServiceProvider` + `ConstraintServiceProvider` + all domain/application singletons.

---

## 2. Architecture Overview

The module is intentionally layered. The golden rule is: **each layer may only depend on layers below it**.

```
HTTP Request
    │
    ▼
Form Request (validation only)
    │
    ▼
Controller  (≤20 LOC; orchestrates only)
    │
    ├──▶ Use-Case Service  (ClockInService / ClockOutService)
    │         │
    │         ▼
    │    AttendanceService  (main orchestrator; DB writes)
    │         │
    │         ├──▶ AttendanceCalculator  (pure domain; no DB)
    │         ├──▶ AttendanceRepository  (query layer)
    │         ├──▶ AttendanceConstraintService  (constraint evaluation)
    │         └──▶ Jobs (dispatched with future delay)
    │
    └──▶ Presenter  (formats Eloquent model → JSON shape)
```

**Layer contracts:**
- **Domain layer** (`Domain/`): pure PHP. Zero IO. No Eloquent. No facades. No `Carbon::now()` stored in instance state. Safe as Octane singleton.
- **Application services** (`Services/`): may use Eloquent and dispatch jobs. Must be stateless (no mutable instance state).
- **Controllers**: inject services via constructor. Do not run queries. Do not contain business logic.

---

## 3. Database Schema

### 3.1 `attendances` table

This is the primary table. Every clock-in creates exactly one row per work period per day.

| Column | Type | Notes |
|---|---|---|
| `id` | char(36) UUID | Primary key, not auto-increment |
| `user_id` | char(36) UUID | FK → users |
| `company_id` | char(36) UUID | FK → companies (multi-tenant) |
| `clock_in_time` | datetime NULL | First clock-in; NEVER overwritten once set |
| `clock_out_time` | datetime NULL | Latest clock-out; overwritten on re-clock-in then re-clock-out |
| `start_time` | datetime | Scheduled period start (in branch TZ) |
| `end_time` | datetime | Scheduled period end (in branch TZ) |
| `total_work_hours` | decimal(8,2) | Net worked hours (minutes minus breaks, / 60) |
| `total_break_hours` | decimal(8,2) | Sum of break durations / 60 |
| `overtime_hours` | decimal(8,2) | Capped by `max_over_time` |
| `max_over_time` | decimal(4,1) | Snapshot from constraint at clock-in (HOURS, e.g. 4.5) |
| `is_late` | tinyint(1) | 1 when clock_in > scheduledStart + grace |
| `is_early_departure` | tinyint(1) | 1 when clock_out < scheduledEnd |
| `is_absent` | tinyint(1) | 1 for synthetic absence rows |
| `is_holiday` | tinyint(1) | 1 for holiday rows |
| `late_minutes` | int | Full minutes past scheduledStart (not past grace) |
| `early_departure_minutes` | int | Minutes before scheduledEnd |
| `status` | varchar | `waiting\|active\|completed\|pending_approval\|approved\|rejected` |
| `day_status` | varchar | `work_day\|weekend\|holiday\|clocked_out\|in_location` |
| `business_date` | date | Calendar day in branch TZ (indexable; NEW column) |
| `shift_end_method` | varchar NULL | `manual\|auto_next_shift\|auto_max_ot\|auto_radius` |
| `timezone` | varchar | IANA identifier frozen at clock-in (e.g. `Asia/Riyadh`) |
| `clock_in_location` | json NULL | `{latitude, longitude, accuracy, …}` |
| `clock_out_location` | json NULL | Same structure |
| `location_tracking` | json NULL | Array of GPS points during shift |
| `notes` | text NULL | Appended (never overwritten) on each event |
| `ip_address` | varchar NULL | Request IP at clock-in |
| `user_agent` | varchar NULL | Browser/device string |
| `verification_data` | json NULL | Biometric or device fingerprint |
| `approved_by` | char(36) UUID NULL | FK → users |
| `approved_at` | datetime NULL | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Critical note:** `start_time`, `end_time`, `clock_in_time`, `clock_out_time` are stored in the **branch timezone**, NOT in UTC. This is a historical decision. Do NOT add UTC-converting accessors — doing so would re-interpret the stored value as UTC, shifting all time math by the branch offset.

### 3.2 `attendance_breaks` table

| Column | Type | Notes |
|---|---|---|
| `id` | char(36) UUID | |
| `attendance_id` | char(36) UUID | FK → attendances |
| `company_id` | char(36) UUID | Multi-tenant |
| `start_time` | datetime | When break started |
| `end_time` | datetime NULL | When break ended (null = break still active) |
| `duration_minutes` | int NULL | Set when `end_time` is recorded |
| `source` | varchar | `auto_gap` (automatically created) or `manual` |
| `notes` | text NULL | |

**Naming convention for source:**
- `auto_gap` — break created automatically when employee re-clocks-in after a gap (the gap between last clock-out and new clock-in becomes a break)
- `manual` — future: break started/ended explicitly via API

### 3.3 `applied_attendance_constraints` table

Snapshot of the constraint settings that were active when the employee clocked in. Used by all post-clock-in calculations so that constraint changes don't retroactively alter completed records.

| Column | Notes |
|---|---|
| `attendance_id` | 1-to-1 with attendances |
| `constraint_id` | FK → attendance_constraints |
| `constraint_snapshot` | JSON — full serialised constraint config at clock-in time |

### 3.4 `attendance_constraint_violations` table

One row per violation detected at clock-in or clock-out.

| Column | Notes |
|---|---|
| `attendance_id` | |
| `constraint_id` | |
| `constraint_type` | `time\|location\|device\|role\|behavioral\|security\|compliance` |
| `severity` | `soft\|hard` |
| `message` | Human-readable description |
| `details` | JSON — context data |
| `blocks_attendance` | bool — true = hard block (HTTP 422) |

### 3.5 Indexes

| Table | Index | Purpose |
|---|---|---|
| `attendances` | `(company_id, business_date)` | Team view GROUP BY |
| `attendances` | `(user_id, business_date)` | Per-user daily lookup |
| `attendances` | `(company_id, status, start_time)` | Active filter |
| `attendances` | `(company_id, is_late, start_time)` | Late arrivals report |

### 3.6 `attendance_constraint_locations` table

**Added:** 2026-05-14  
**Migration:** `modules/Attendance/Database/migrations/2026_05_14_000001_create_attendance_constraint_locations_table.php`

Stores explicit GPS locations (additional locations) for a constraint. This is the **new preferred way** to attach multiple GPS locations to a constraint for the additional-locations feature — it gives each location a stable UUID, enables individual CRUD operations, and keeps `branch_locations` JSON (which encodes branch-linked locations) unchanged.

| Column | Type | Notes |
|---|---|---|
| `id` | char(36) UUID | Primary key |
| `attendance_constraint_id` | char(36) UUID | FK → `attendance_constraints.id` (cascade delete) |
| `company_id` | char(36) UUID | Multi-tenant |
| `name` | varchar NULL | Display name for the location |
| `latitude` | decimal(10,7) | GPS latitude |
| `longitude` | decimal(10,7) | GPS longitude |
| `radius` | int, default 100 | Geofence radius in metres |
| `created_by` | char(36) UUID NULL | FK → users |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Indexes:** `acl_constraint_id_index` on `attendance_constraint_id`, `acl_company_id_index` on `company_id`.

**Cascade:** deleting a constraint hard-deletes all its location rows automatically.

**Design note:** This table is **additive** — it does not replace the existing `branch_locations` JSON column on `attendance_constraints`. Both sources are merged at runtime by `AttendanceConstraintService` (see §6.7 and §INV-20).

---

## 4. Domain Layer (Pure Logic)

**Location:** `modules/Attendance/Domain/`

These classes have **zero dependencies** on the framework, Eloquent, facades, or external state. They can be unit-tested without a database. They are safe as Octane singletons because they carry no mutable state.

### 4.1 Clock Abstraction

**`Domain/Time/Clock.php` (interface)**
```php
interface Clock {
    public function now(): CarbonImmutable;
}
```

**`Domain/Time/SystemClock.php`**
```php
// Production: returns CarbonImmutable::now()
```

**`Domain/Time/FixedClock.php`**
```php
// Tests: returns a hard-coded CarbonImmutable passed to the constructor
// Never use in production code — only in tests
```

Inject `Clock` into services that need "now", then swap to `FixedClock` in tests to make time deterministic.

### 4.2 TimezoneResolver

**`Domain/Time/TimezoneResolver.php`**

Stateless singleton. Provides the resolution chain for "what timezone does this attendance / user belong to?"

**Resolution chain (in order of priority):**
1. `attendance.timezone` (frozen IANA string set at clock-in)
2. `user → userProfessionalData → branch → address → country → timezones[0]`
3. `config('app.timezone')`
4. `'Asia/Riyadh'` (hard fallback)

**Methods:**
- `forAttendance(Attendance $a): string` — reads the frozen `timezone` column
- `forUser(User $u): string` — traverses user → branch relations (user must have `userProfessionalData.branch.address.country.timezones` loaded)
- `forCurrentRequest(): string` — delegates to `getTimeZoneBranchByRequest()` global helper

**Critical:** The `getTimeZoneBranchByRequest()` global helper internally uses `once()` so it is computed once per request and memoized. Under Octane/RoadRunner, `once()` is request-scoped (not process-scoped), so there is no cross-request state leak.

### 4.3 AttendanceCalculator

**`Domain/Calculator/AttendanceCalculator.php`**

The single source of truth for all numeric attendance fields. Pure function.

```php
final class AttendanceCalculator {
    public function __construct(
        private readonly LatenessPolicy       $lateness,
        private readonly OvertimePolicy       $overtime,
        private readonly EarlyDeparturePolicy $earlyDeparture,
    ) {}

    public function calculate(CalculatorInput $input): WorkHoursResult { ... }
}
```

**Algorithm step-by-step:**
1. If `clockIn` or `clockOut` is null → return all-zeros `WorkHoursResult`.
2. `grossMinutes = clockIn.diffInMinutes(clockOut)` (signed: negative if clockOut before clockIn — which should not happen but is handled by `max(0, …)`)
3. `netMinutes = max(0, grossMinutes - totalBreakMinutes)`
4. `breakHours = round(totalBreakMinutes / 60, 2)`
5. `workHours = round(netMinutes / 60, 2)`
6. `overtimeHours = overtime.calculate(input, netMinutes)` — see §4.5
7. `[isLate, lateMinutes] = lateness.evaluate(input)` — see §4.4
8. `[isEarlyDeparture, earlyMinutes] = earlyDeparture.evaluate(input)` — see §4.6
9. Return `WorkHoursResult` with all 7 fields.

**Who calls this:**
- `AttendanceService::clockOut()` — after recording clock_out_time
- `AttendanceService::recalculateWorkHoursAndSave()` — after approval or manual edit
- `AutoCloseAttendanceService::closeIfExpired()` — auto-close paths
- Any future jobs that recalculate

**Who must NOT call this:**
- Model accessors (would cause recursive save loops)
- Presenters (read-only; use the already-calculated stored values)

### 4.4 CalculatorInput (Value Object)

**`Domain/Calculator/CalculatorInput.php`**
```php
final readonly class CalculatorInput {
    public CarbonImmutable $scheduledStart;   // Scheduled period start, in branch TZ
    public CarbonImmutable $scheduledEnd;     // Scheduled period end, in branch TZ
    public ?CarbonImmutable $clockIn;         // Actual first clock-in, in branch TZ; null if not yet clocked
    public ?CarbonImmutable $clockOut;        // Actual latest clock-out, in branch TZ; null if still active
    public int $totalBreakMinutes;            // Sum of all COMPLETED break duration_minutes
    public int $gracePeriodMinutes;           // From constraint snapshot
    public float $maxOverTimeHours;           // From attendance.max_over_time (HOURS decimal, e.g. 4.5)
    public string $timezone;                  // IANA identifier from attendance.timezone column
}
```

**How callers build it** (`AttendanceService::buildCalculatorInput`):
```php
$timezone = $attendance->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';

$scheduledStart = CarbonImmutable::parse($attendance->start_time)->setTimezone($timezone);
$scheduledEnd   = CarbonImmutable::parse($attendance->end_time)->setTimezone($timezone);

// Overnight shift: if end <= start, bump end to next day
if (!$scheduledEnd->greaterThan($scheduledStart)) {
    $scheduledEnd = $scheduledEnd->addDay();
}

$clockIn  = $attendance->clock_in_time
    ? CarbonImmutable::parse($attendance->clock_in_time)->setTimezone($timezone)
    : null;
$clockOut = $attendance->clock_out_time
    ? CarbonImmutable::parse($attendance->clock_out_time)->setTimezone($timezone)
    : null;

// Only COMPLETED breaks count (end_time is not null)
$totalBreakMinutes = (int) $attendance->breaks()
    ->whereNotNull('end_time')
    ->sum('duration_minutes');

// Read grace from constraint snapshot
$snapshot       = $attendance->appliedAttendanceConstraint?->constraint_snapshot ?? [];
$latenessRules  = $snapshot['lateness_rules'] ?? [];
$graceValue     = (int) ($latenessRules['lateness_period'] ?? $latenessRules['grace_period_minutes'] ?? 0);
$graceUnit      = (string) ($latenessRules['lateness_unit'] ?? 'minute');
$graceMinutes   = match (strtolower($graceUnit)) {
    'hour' => $graceValue * 60,
    'day'  => $graceValue * 1440,
    default => $graceValue,
};

return new CalculatorInput(
    scheduledStart:     $scheduledStart,
    scheduledEnd:       $scheduledEnd,
    clockIn:            $clockIn,
    clockOut:           $clockOut,
    totalBreakMinutes:  $totalBreakMinutes,
    gracePeriodMinutes: max(0, $graceMinutes),
    maxOverTimeHours:   (float) ($attendance->max_over_time ?? 0.0),
    timezone:           $timezone,
);
```

### 4.5 WorkHoursResult (Value Object)

**`Domain/Calculator/WorkHoursResult.php`**
```php
final readonly class WorkHoursResult {
    public float $totalWorkHours;       // Net worked hours (rounded 2 decimals)
    public float $totalBreakHours;      // Break hours (rounded 2 decimals)
    public float $overtimeHours;        // Overtime (rounded 2 decimals)
    public bool  $isLate;
    public int   $lateMinutes;
    public bool  $isEarlyDeparture;
    public int   $earlyDepartureMinutes;
}
```

Callers persist all 7 fields in one DB `UPDATE`:
```php
$attendance->update([
    'total_work_hours'        => $result->totalWorkHours,
    'total_break_hours'       => $result->totalBreakHours,
    'overtime_hours'          => $result->overtimeHours,
    'is_late'                 => $result->isLate,
    'late_minutes'            => $result->lateMinutes,
    'is_early_departure'      => $result->isEarlyDeparture,
    'early_departure_minutes' => $result->earlyDepartureMinutes,
]);
```

### 4.6 StandardLatenessPolicy

**`Domain/Calculator/StandardLatenessPolicy.php`**

**Business rule (confirmed with stakeholder):**
- If `clockIn <= scheduledStart + gracePeriodMinutes` → not late
- If `clockIn > scheduledStart + gracePeriodMinutes` → IS late
- `lateMinutes = FULL minutes past scheduledStart` (NOT past the grace threshold)

**Example:**
- Scheduled start: 09:00. Grace: 15 min. Employee arrives at 09:16.
- `threshold = 09:00 + 15 = 09:15`. Since 09:16 > 09:15, IS late.
- `lateMinutes = diff(09:00, 09:16) = 16` — not 1!

```php
public function evaluate(CalculatorInput $input): array   // returns [bool, int]
{
    if (!$input->clockIn) return [false, 0];

    $threshold = $input->scheduledStart->addMinutes($input->gracePeriodMinutes);
    if ($input->clockIn->lessThanOrEqualTo($threshold)) return [false, 0];

    $lateMinutes = (int) $input->scheduledStart->diffInMinutes($input->clockIn);
    return [true, $lateMinutes];
}
```

### 4.7 StandardOvertimePolicy

**`Domain/Calculator/StandardOvertimePolicy.php`**

**Formula:**
```
scheduledMinutes = scheduledStart.diffInMinutes(scheduledEnd)
overtimeMinutes  = max(0, netWorkMinutes - scheduledMinutes)
capMinutes       = round(maxOverTimeHours * 60)
overtime         = min(overtimeMinutes, capMinutes)
result           = round(overtime / 60, 2)
```

If `maxOverTimeHours == 0` → cap is 0 → no overtime recorded.

### 4.8 StandardEarlyDeparturePolicy

**`Domain/Calculator/StandardEarlyDeparturePolicy.php`**

- If `clockOut < scheduledEnd` → early departure
- `earlyDepartureMinutes = scheduledEnd.diffInMinutes(clockOut)`

### 4.9 AutoBreakComputer

**`Domain/Breaks/AutoBreakComputer.php`**

Pure function used when a user re-clocks-in after being clocked out.

```php
public function computeGap(
    CarbonImmutable $previousClockOut,
    CarbonImmutable $newClockIn
): ?BreakSegment
```

- Returns `null` if `newClockIn <= previousClockOut` (invalid/zero gap)
- Otherwise returns a `BreakSegment` with `source = 'auto_gap'`

### 4.10 BreakSegment (Value Object)

**`Domain/Breaks/BreakSegment.php`**
```php
final readonly class BreakSegment {
    public CarbonImmutable $start;           // Previous clock_out_time
    public CarbonImmutable $end;             // New clock_in_time
    public int             $durationMinutes;
    public string          $source;          // 'auto_gap' always from AutoBreakComputer
}
```

---

## 5. Models

### 5.1 Attendance Model

**`Models/Attendance.php`**
**Table:** `attendances`

**Status constants:**
```php
const STATUS_WAITING          = 'waiting';          // Pre-created by command; awaiting employee arrival
const STATUS_ACTIVE           = 'active';           // Employee is currently clocked in
const STATUS_COMPLETED        = 'completed';        // Clocked out (or auto-closed)
const STATUS_PENDING_APPROVAL = 'pending_approval'; // Awaiting manager sign-off
const STATUS_APPROVED         = 'approved';
const STATUS_REJECTED         = 'rejected';
```

**Valid status transitions (enforced by `validateStatusTransition`):**
```
waiting          → active, completed
active           → completed, pending_approval
completed        → pending_approval
pending_approval → approved, rejected
```

**Cast map:**
```php
// clock_in_time, clock_out_time, start_time, end_time are stored in branch timezone, NOT UTC
// Do NOT use datetime cast - it treats values as UTC which causes wrong time conversion
'approved_at'      => 'datetime'
'created_at'       => 'datetime'
'updated_at'       => 'datetime'
'deleted_at'       => 'datetime'
'total_work_hours' => 'decimal:2'
'total_break_hours'=> 'decimal:2'
'overtime_hours'   => 'decimal:2'
'max_over_time'    => 'decimal:1'   // HOURS, e.g. "4.5"
'late_minutes'     => 'integer'
'early_departure_minutes' => 'integer'
'is_late'          => 'boolean'
'is_early_departure' => 'boolean'
'business_date'    => 'date'
'clock_in_location' => 'array'
'clock_out_location' => 'array'
'verification_data' => 'array'
'location_tracking' => 'array'
```

**Note:** `is_absent` and `is_holiday` are NOT in `$casts` — they return integers (0/1) unless explicitly cast by the calling code. This is a known inconsistency; be careful when comparing with `=== true`.

**Relationships:**
```php
user(): BelongsTo(User)
company(): BelongsTo(Company)
approver(): BelongsTo(User, 'approved_by')   // alias: approvedBy()
breaks(): HasMany(AttendanceBreak)
appliedAttendanceConstraint(): HasOne(AppliedAttendanceConstraint, 'attendance_id', 'id')
professionalData(): HasOne(UserProfessionalData, 'user_id', 'user_id')
attendanceConstraint(): HasMany(AttendanceConstraint, 'id', 'constraint_id')
```

**Important model methods:**

| Method | What it does |
|---|---|
| `isActive(): bool` | `status == 'active' AND clock_out_time IS NULL` |
| `isCompleted(): bool` | `status == 'completed' AND clock_out_time IS NOT NULL` |
| `isOnBreak(): bool` | Checks for active break in `breaks` relation |
| `activeBreak(): ?AttendanceBreak` | `breaks` where `start_time NOT NULL AND end_time IS NULL` |
| `completedBreaks(): Collection` | breaks where both start and end are set |
| `calculateTotalBreakMinutes(): int` | Sums `duration_minutes` from completed breaks |
| `calculateTotalBreakHours(): float` | `calculateTotalBreakMinutes() / 60`, rounded 2 decimals |
| `updateTotalBreakHours(): self` | Persists `total_break_hours` — USE WITH CAUTION (extra DB write) |
| `validateStatusTransition(string $new): void` | Throws `InvalidArgumentException` on invalid transition |
| `validate(): void` | Validates times, location, IP, user_agent |

**Query scopes:**
```php
scopeActive($query)    → status='active' AND clock_out_time IS NULL
scopeCompleted($query) → status='completed' AND clock_out_time IS NOT NULL
scopeDateRange($query, $start, $end)
scopeForUser($query, $userId)
scopeForCompany($query, $companyId)
```

**CRITICAL WARNING — Do NOT add timezone-converting accessors to this model.**
The comment on lines 181–186 explains: times are stored in branch TZ (not UTC). Adding accessors that `setTimezone('UTC')` would cause all lateness/overtime math to break by the UTC offset of the branch.

### 5.2 AttendanceBreak Model

**`Models/AttendanceBreak.php`**
**Table:** `attendance_breaks`

**Key columns:** `attendance_id`, `company_id`, `start_time`, `end_time`, `duration_minutes`, `source` (`auto_gap`|`manual`), `notes`

**Methods:**
- `isActive(): bool` — `start_time NOT NULL AND end_time IS NULL`
- `isCompleted(): bool` — `end_time IS NOT NULL`
- `calculateDuration(): void` — sets `duration_minutes = end_time.diffInMinutes(start_time)`, saves
- `getFormattedDuration(): string` — returns `"Xh Ym"` format

### 5.3 AppliedAttendanceConstraint Model

**`Models/AppliedAttendanceConstraint.php`**

1-to-1 with `attendances`. Stores `constraint_snapshot` (JSON) which is the full constraint config at the moment of clock-in. This snapshot is what `AttendanceCalculator` inputs use for grace period, max overtime, etc. — it is immutable after creation.

### 5.4 AttendanceConstraint Model (extended)

**`Models/AttendanceConstraint.php`**  
**Table:** `attendance_constraints`

In addition to its original structure, the model now has:

**New relationship:**
```php
public function additionalLocations(): HasMany
{
    return $this->hasMany(AttendanceConstraintLocation::class, 'attendance_constraint_id');
}
```

This gives direct access to the `attendance_constraint_locations` rows for this constraint.

**Existing relationships (summary):**

| Relationship | Type | Notes |
|---|---|---|
| `users()` | BelongsToMany (pivot `attendance_constraint_user`) | Users with this as an *additional* constraint |
| `branches()` | HasMany(ManagementHierarchy) via `branch_ids` JSON | Linked branches |
| `managementHierarchies()` | MorphedByMany via `constrainables` table | Polymorphic management hierarchy links |
| `additionalLocations()` | HasMany(AttendanceConstraintLocation) | GPS locations for this constraint (**new**) |
| `creator()` | BelongsTo(User, `created_by`) | |
| `updater()` | BelongsTo(User, `updated_by`) | |
| `company()` | BelongsTo(Company) | |

**Important:** The main constraint for an employee is set via `user_professional_datas.attendance_constraint_id` (belongs-to on `UserProfessionalData`). The `attendance_constraint_user` pivot is for **additional** constraints (additional locations). These are two completely separate assignment channels.

### 5.5 AttendanceConstraintLocation Model

**`Models/AttendanceConstraintLocation.php`**  
**Table:** `attendance_constraint_locations`

```php
namespace Modules\Attendance\Models;

class AttendanceConstraintLocation extends Model
{
    use UuidTrait;
    use CustomBelongsToTenant;

    protected $fillable = [
        'attendance_constraint_id',
        'company_id',
        'name',
        'latitude',
        'longitude',
        'radius',
        'created_by',
    ];

    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
        'radius'    => 'integer',
    ];

    public function constraint(): BelongsTo { ... }
    public function creator(): BelongsTo { ... }
}
```

**No soft-deletes** — locations are hard-deleted. Cascade from `attendance_constraints` also hard-deletes all child rows.

---

## 6. Services — Application Layer

### 6.1 AttendanceService

**`Services/AttendanceService.php`**

The main orchestrator. Stateless. Constructor:
```php
public function __construct(
    private AttendanceRepository $attendanceRepository,
    private AttendanceCalculator $calculator,
) {}
```

#### `clockIn(ClockInDTO $dto): Attendance`

Full step-by-step:
1. `ensureUserHasNoActiveClockIn($dto->getUserId())` — throws `AttendanceException::alreadyClockedIn()` (HTTP 400) if user has an active row with `clock_in_time NOT NULL AND clock_out_time IS NULL`.
2. Load `$user = User::find(auth()->user()->id)`.
3. Resolve `$timezone = getTimeZoneBranchByRequest() ?? config('app.timezone')`.
4. `$currentDate = Carbon::now($timezone)->format('Y-m-d')`.
5. `$constraints = AttendanceConstraintService::getTodaysWorkRulesForUser($user, $currentDate)` — returns array with `current_work_period`, `all_work_periods`, `max_over_time`, `early_clock_in_rules`, etc.
6. `[$startDateTime, $endDateTime] = resolveWorkPeriodBounds($constraints, $currentDate, $timezone)`:
   - Parses `start_time` and `end_time` from `current_work_period` in branch TZ.
   - If `$startDateTime > $endDateTime` (overnight shift), bumps `$endDateTime` by 1 day.
7. `enforceEarlyClockInRule(…)` — if constraint has `prevent_early_clock_in: true`, throws generic `Exception` with Arabic message if `clockInMoment < startDateTime - earlyPeriod`.
8. `buildClockInAttendanceData(…)` — assembles array with all clock-in fields.
9. `persistClockInAttendance($userId, $startDateTime, $attendanceData)`:
   - Looks for a `waiting` row with `user_id`, `start_time`, `clock_in_time IS NULL`.
   - If found: updates it (promotes `waiting → active`).
   - If not found: creates new row via `AttendanceRepository::create()`.
10. If `extends_to_next_day` flag is set: dispatches `ProcessClockInAttendanceData` with delay until `$endDateTime`.
11. `scheduleAutoClockOutWhenNextShiftStarts(…)` — dispatches `AutoClockOutAtNextShiftStartJob` with `delay($nextShiftStart)`.
12. `scheduleAutoCloseAtMaxOvertime(…)` — dispatches `AutoCloseAttendanceJob` with `delay($endDateTime + max_over_time * 60 min)`.
13. Returns the attendance record.

#### `clockOut(ClockOutDTO $dto): Attendance`

1. `attendanceRepository->getCurrentAttendance($dto->getUserId())` — throws `AttendanceException::notClockedIn()` if null.
2. Throws `AttendanceException::alreadyClockedOut()` if `clock_out_time` is already set.
3. `attendanceRepository->update($attendance->id, buildClockOutUpdatePayload(…))`:
   - Sets `clock_out_time = Carbon::parse($dto->getClockOutTime())->setTimezone(getTimeZoneBranchByRequest())`.
   - Sets `clock_out_location`, appends notes, sets `status = 'completed'`, `day_status = 'clocked_out'`.
4. `$attendance->refresh()`.
5. `$input = buildCalculatorInput($attendance)`.
6. `$result = calculator->calculate($input)`.
7. `$attendance->update([7 calculated fields])`.
8. Returns `$attendance->refresh()`.

#### `startBreak($userId, $notes): Attendance`

1. Gets current attendance, throws if none.
2. Throws `alreadyOnBreak()` if `isOnBreak()` is true.
3. Creates `AttendanceBreak` with `start_time = now()`.
4. Updates attendance notes if notes provided.
5. Returns refreshed attendance.

#### `endBreak($userId, $notes): Attendance`

1. Gets current attendance, throws if none.
2. Throws `notOnBreak()` if `isOnBreak()` is false.
3. Gets `activeBreak()`, sets `end_time = now()`, calls `calculateDuration()`, saves.
4. `updateData['total_break_hours'] = calculateTotalBreakHours()` — **NOTE: this does not re-run the full calculator; only updates break hours**.
5. Returns via `attendanceRepository->updateAttendance(…)`.

#### `getTeamAttendance(array $filters, $page, $perPage, $userId): LengthAwarePaginator`

Efficient GROUP BY approach:
1. Builds base query with `WHERE` conditions (date range converted to UTC, filters, business_date NOT NULL).
2. Counts distinct `(user_id, business_date)` pairs with `COUNT(DISTINCT CONCAT(user_id, CHAR(0), business_date))`.
3. Selects representative ID per group: `COALESCE(MIN(CASE WHEN clock_in_time IS NOT NULL THEN id END), MIN(id))`.
4. Fetches those IDs with `whereIn` + `with(AttendanceTeamPresenter::requiredRelations())`.
5. Returns `LengthAwarePaginator` with correct total.

#### Other Methods

| Method | Summary |
|---|---|
| `getAttendanceSummary($userId, $start, $end)` | Counts late, absent, overtime, work hours by date range |
| `updateAttendance($id, $data)` | Updates; recalculates if clock times changed; blocks past-day edits |
| `approveAttendance($id, $approvedBy, $notes)` | Sets `status = 'approved'`, recalculates |
| `rejectAttendance($id, $rejectedBy, $reason)` | Sets `status = 'rejected'` |
| `deleteAttendance($id)` | Throws if `status = 'approved'` |
| `endShiftAutomatically($id, $method, $notes, $markAbsent)` | Legacy auto-close; new code uses `AutoCloseAttendanceService` |
| `createAbsenceRecord($user, $date, $reason)` | Creates completed row with `is_absent = true` |
| `createWaitingRecord($user, $date, $notes, $start, $end)` | Creates `waiting` row for pre-scheduled period |

### 6.2 AutoCloseAttendanceService

**`Services/AutoCloseAttendanceService.php`**

**The single writer for all automated shift closures.** Stateless final class.

```php
final class AutoCloseAttendanceService {
    public function __construct(
        private readonly AttendanceCalculator $calculator,
    ) {}
}
```

#### `closeIfExpired(Attendance $attendance, CarbonImmutable $closeAt, string $reason): bool`

**The core method. Always call this from any code path that auto-closes a shift.**

Full implementation:
```php
return DB::transaction(function () use ($attendance, $closeAt, $reason): bool {
    // Acquire row-level lock — second concurrent writer waits here
    $fresh = Attendance::query()->lockForUpdate()->find($attendance->id);

    // Re-read state after lock — no-op if another writer already closed it
    if (!$fresh
        || $fresh->status !== Attendance::STATUS_ACTIVE
        || $fresh->clock_out_time !== null
        || $fresh->clock_in_time === null
    ) {
        return false;  // Already closed, wrong state — no-op
    }

    $input  = $this->buildCalculatorInput($fresh, $closeAt);
    $result = $this->calculator->calculate($input);

    $fresh->update([
        'clock_out_time'          => $closeAt->format('Y-m-d H:i:s'),
        'clock_out_location'      => $this->resolveLastLocation($fresh),
        'status'                  => Attendance::STATUS_COMPLETED,
        'day_status'              => 'clocked_out',
        'shift_end_method'        => $reason,
        'total_work_hours'        => $result->totalWorkHours,
        'total_break_hours'       => $result->totalBreakHours,
        'overtime_hours'          => $result->overtimeHours,
        'is_late'                 => $result->isLate,
        'late_minutes'            => $result->lateMinutes,
        'is_early_departure'      => $result->isEarlyDeparture,
        'early_departure_minutes' => $result->earlyDepartureMinutes,
        'notes'                   => trim(($fresh->notes ?? '') . "\n[Auto] Clock-out: $reason at ..."),
    ]);

    return true;
});
```

**Key design decisions:**
- `clock_out_time = $closeAt` (the pre-computed boundary) — NOT `now()`. This means if the job fires 3 minutes late, the employee is NOT penalised with 3 extra minutes of overtime. The boundary is always deterministic.
- `SELECT … FOR UPDATE` inside a transaction prevents all concurrent writers from simultaneously closing the same row. The second concurrent caller re-reads `status = 'completed'` and returns `false`.
- Returns `bool` so callers can log whether it was a no-op.

**`reason` values:**
| Value | Trigger |
|---|---|
| `'auto_max_ot'` | Dispatched by `AutoCloseAttendanceJob` (end_time + max overtime) |
| `'auto_next_shift'` | Dispatched by `AutoClockOutAtNextShiftStartJob` |
| `'manual'` | Human clock-out via `AttendanceService::clockOut()` |
| `'auto_radius'` | Future: radius enforcement |

### 6.3 ClockInService

**`Services/ClockInService.php`**

Use-case entry point for the clock-in HTTP flow.

```php
public function execute(ClockInDTO $dto, array $requestData): Attendance {
    $violations = MockAttendanceService::validateClockIn($dto, $requestData);
    $blocking   = array_filter($violations, fn($v) => $v['blocks_attendance'] ?? false);
    if (!empty($blocking)) {
        throw AttendanceException::clockInBlocked($blocking);  // HTTP 422
    }
    return MockAttendanceService::persistClockIn($dto, $requestData);
    // persistClockIn calls AttendanceService::clockIn() + dispatches AttendanceClockedIn event
}
```

### 6.4 ClockOutService

**`Services/ClockOutService.php`**

Thin wrapper.

```php
public function execute(ClockOutDTO $dto): Attendance {
    return AttendanceService::clockOut($dto);
}
```

The controller handles post-clock-out constraint logging (non-blocking; violations are recorded but don't block the response).

### 6.5 UserAttendanceService

**`Services/UserAttendanceService.php`**

Mobile/web API: returns work rules and period status for a user on a given date. Stateless singleton — no mutable instance state.

**Key methods:**

`getUserConstraints(User|string $userOrId, string $date): array`
- Loads user with relations: `professionalData.attendanceConstraint`, `userProfessionalData.branch.address.country.timezones`, `userProfessionalData.department`.
- Calls `AttendanceConstraintService::getTodaysWorkRulesForUser($user, $date)` to get work_rules.
- Fetches day attendances (`fetchDayAttendancesAndCurrentOpen`).
- Enhances periods with attendance data (`enhancePeriodsWithAttendance`).
- Returns `{ user_id, user_name, date, work_rules: [...enhanced periods...] }`.

`checkClockInStatus(string $userId): array`
- Returns `{ user_id, is_clocked_in: bool, is_on_break: bool, attendance_id: string|null, clock_in_time: string|null, status: string|null }`.

### 6.6 UserAttendanceHistoryService

**`Services/UserAttendanceHistoryService.php`**

History API for mobile. Stateless singleton.

`getUserAttendanceHistoryMobileApi($userId, $month, $year, $page, $perPage): array`
- Last 3 calendar days in branch TZ (today, yesterday, day-before-yesterday).
- Calls `buildHistoryPeriodsForDay()` for each date.
- Returns `{ data: [{ date, day_name, status, periods: [...] }], pagination: {...} }`.

`getUserAttendanceHistory($userId, $month, $year, $page, $perPage): array`
- Full calendar month in branch TZ.
- Same structure.

### 6.7 AttendanceConstraintService — Location Merging (Updated)

**`Services/AttendanceConstraintService.php`**

Two private methods handle merging additional locations. Both were updated in 2026-05-14 to pull from the new `attendance_constraint_locations` table **in addition to** the existing `branch_locations` JSON.

#### `buildAdditionalLocationRules(User $user): array`

Called by `getTodaysWorkRulesForUser()` to populate the `additional_locations` key in the `user-constraint/today` API response.

**Sources merged (in order):**
1. `branch_locations` JSON from every active additional constraint (existing behaviour)
2. Rows from `attendance_constraint_locations` for every active additional constraint (**new**)

```php
$user->loadMissing('additionalAttendanceConstraints.additionalLocations');

// 1. JSON branch_locations (legacy / manual)
$branchLocations = $user->additionalAttendanceConstraints
    ->where('is_active', true)
    ->flatMap(fn ($c) => collect($c->branch_locations ?? []))
    ->map(fn ($loc) => ['name' => ..., 'latitude' => ..., 'longitude' => ..., 'radius' => ...]);

// 2. attendance_constraint_locations table rows (new)
$tableLocations = $user->additionalAttendanceConstraints
    ->where('is_active', true)
    ->flatMap(fn ($c) => $c->additionalLocations ?? collect())
    ->map(fn ($loc) => ['id' => $loc->id, 'name' => ..., 'latitude' => ..., 'longitude' => ..., 'radius' => ...]);

return $branchLocations->merge($tableLocations)->values()->all();
```

**Note:** Table locations include an `id` field (the location UUID) that JSON branch_locations do not have.

#### `mergeAdditionalLocationsForUser(Attendance $attendance, AttendanceConstraint $mainConstraint): AttendanceConstraint`

Called inside `validateSingleConstraint()` before passing the constraint to `LocationConstraintService`. Merges all additional allowed locations into a **clone** of the main constraint's `branch_locations`.

**Sources merged:**
1. `branch_locations` JSON from active additional constraints
2. `attendance_constraint_locations` rows from active additional constraints (**new**), mapped to the `{name, latitude, longitude, radius}` shape that `LocationConstraintService` expects

The original main constraint is never mutated — a clone is returned. Time rules, shift schedules, device rules, and all other constraint settings are evaluated solely from the original main constraint.

---

## 7. DTOs (Data Transfer Objects)

### 7.1 ClockInDTO

**`DTO/ClockInDTO.php`**
```php
readonly class ClockInDTO {
    public UuidInterface $user_id;
    public UuidInterface $company_id;
    public string $clock_in_time;         // ISO datetime string
    public ?array $location;              // {latitude, longitude, accuracy?, …}
    public ?string $notes;
    public ?string $ip_address;
    public ?string $user_agent;
}
```

Getters: `getUserId()`, `getCompanyId()`, `getClockInTime()`, `getLocation()`, `getNotes()`, `getIpAddress()`, `getUserAgent()`, `toArray()`

### 7.2 ClockOutDTO

**`DTO/ClockOutDTO.php`**
```php
readonly class ClockOutDTO {
    public UuidInterface $user_id;
    public UuidInterface $company_id;
    public string $clock_out_time;
    public ?array $location;
    public ?string $notes;
    public ?string $ip_address;
    public ?string $user_agent;
}
```

---

## 8. Controllers

### 8.1 AttendanceController

**`Controllers/AttendanceController.php`**

Constructor injects: `AttendanceService`, `AttendanceConstraintService`, `MockAttendanceService`, `ClockInService`, `ClockOutService`.

**Key action methods:**

`clockIn(ClockInRequest $request): JsonResponse`
```
$attendance = $clockInService->execute($request->toDTO(), $request->all())
return Json::item(AttendancePresenter::present($attendance))
```
On `AttendanceException` with violations: returns HTTP 422 with violation array.

`clockOut(ClockOutRequest $request): JsonResponse`
```
$attendance = $clockOutService->execute($request->toDTO())
// Post-validation: loops constraint checks, creates violation records (non-blocking, no HTTP error)
return Json::item(AttendancePresenter::present($attendance))
```

`startBreak(BreakRequest $request): JsonResponse`
```
$attendance = AttendanceService::startBreak($userId, $notes)
return Json::item(AttendancePresenter::present($attendance))
```

`endBreak(BreakRequest $request): JsonResponse`
```
$attendance = AttendanceService::endBreak($userId, $notes)
// Post-validation: validateBreakEnd() → violation record if applicable (non-blocking)
return Json::item(AttendancePresenter::present($attendance))
```

### 8.2 UserAttendanceController

**`Controllers/UserAttendanceController.php`**

Injects: `UserAttendanceService`, `UserAttendanceHistoryService`.

- `getUserConstraints(Request)` → `UserAttendanceService::getUserConstraints(…)`
- `getUserAttendanceHistory(Request)` → `UserAttendanceHistoryService::getUserAttendanceHistoryMobileApi(…)`

### 8.3 AttendanceConstraintController — New Methods (2026-05-14)

**`Controllers/AttendanceConstraintController.php`**

All new methods are added to the existing controller. No existing methods were modified.

#### `updateBasicInfo(Request, string $constraintId): JsonResponse`

`PATCH /{constraint}/basic-info`

Updates only three fields without touching `constraint_config` or any other part of the constraint. All fields are optional (send only what you want to change).

| Request field | Validation | Notes |
|---|---|---|
| `constraint_name` | sometimes, string, max:255 | |
| `constraint_type` | sometimes, string, in valid types | |
| `branch_ids` | sometimes, nullable, array of `management_hierarchies` UUIDs | |

Bumps the applicable-constraints cache after saving.

#### `getConstraintEmployees(Request, string $constraintId): JsonResponse`

`GET /{constraint}/employees`

Returns all employees assigned to this constraint from two sources:
- **`source: "main"`** — employees whose `user_professional_datas.attendance_constraint_id` matches this constraint
- **`source: "additional"`** — employees linked via the `attendance_constraint_user` pivot table

Deduplication: results are merged and unique by `id`.

#### `assignEmployeeToConstraint(Request, string $constraintId): JsonResponse`

`POST /{constraint}/employees`

Sets `user_professional_datas.attendance_constraint_id = $constraintId` for the given user. This is the **main constraint** assignment (not an additional constraint). Returns 404 if the user has no `UserProfessionalData` record.

| Request field | Required | Notes |
|---|---|---|
| `user_id` | Yes | UUID, must exist in `users` |

#### `createLocations(Request, string $constraintId): JsonResponse`

`POST /{constraint}/locations`

Bulk-creates rows in `attendance_constraint_locations`. Accepts an array so multiple locations can be created in one request. Returns all created rows.

| Request field | Required | Notes |
|---|---|---|
| `locations` | Yes | Array, min 1 |
| `locations.*.name` | No | string, max:255 |
| `locations.*.latitude` | Yes | numeric, -90 to 90 |
| `locations.*.longitude` | Yes | numeric, -180 to 180 |
| `locations.*.radius` | Yes | integer, 1 to 10000 |

#### `getLocations(string $constraintId): JsonResponse`

`GET /{constraint}/locations`

Returns all rows from `attendance_constraint_locations` for the given constraint. Includes `id`, `name`, `latitude`, `longitude`, `radius`, `created_at`.

#### `updateLocation(Request, string $locationId): JsonResponse`

`PUT /locations/{location}`

Updates a single location row. All fields optional. Scoped to `company_id = Auth::user()->company_id` for tenant safety. Returns 404 if not found.

| Request field | Required | Notes |
|---|---|---|
| `name` | No | string, max:255 |
| `latitude` | No | numeric, -90 to 90 |
| `longitude` | No | numeric, -180 to 180 |
| `radius` | No | integer, 1 to 10000 |

#### `deleteLocation(string $locationId): JsonResponse`

`DELETE /locations/{location}`

Hard-deletes the location row. Scoped to `company_id`. Returns 404 if not found. Bumps cache.

#### `getDayShifts(string $constraintId): JsonResponse`

`GET /{constraint}/day-shifts`

Reads `constraint_config.time_rules.weekly_schedule` and returns it as a structured array ordered Saturday → Friday. Each day entry contains:
- `day` (string)
- `enabled` (bool)
- `periods` (array of `{start_time, end_time, extends_to_next_day}`)
- `lateness_rules` (nullable)
- `early_clock_in_rules` (nullable)

Also returns top-level `constraint_id`, `constraint_name`, and `max_over_time`. Returns empty shifts array if `constraint_config` has no `time_rules`.

#### `getShifts(string $constraintId): JsonResponse` ← **NEW**

`GET /{constraint}/shifts`

Returns the current shift configuration in the **exact same shape expected by `POST /{constraint}/shifts`**, so the frontend can render the edit form without any mapping.

**Mode detection logic** (applied automatically — no query parameter required):
- `weekly` — all enabled days share identical periods (compared after normalising key names to snake_case)
- `daily`  — at least two enabled days have different periods

**Response for `weekly` mode:**
```json
{
  "mode": "weekly",
  "days": ["saturday", "sunday", "monday"],
  "periods": [
    { "start_time": "08:00", "end_time": "17:00", "extends_to_next_day": false }
  ]
}
```

**Response for `daily` mode:**
```json
{
  "mode": "daily",
  "schedule": {
    "saturday": { "periods": [{ "start_time": "08:00", "end_time": "12:00", "extends_to_next_day": false }] },
    "sunday":   { "periods": [{ "start_time": "09:00", "end_time": "18:00", "extends_to_next_day": false }] }
  }
}
```

**Always present in both modes:**
- `constraint_id`, `constraint_name`, `max_over_time`
- `raw_schedule` — full 7-day map with `enabled`, `periods`, `lateness_rules`, `early_clock_in_rules` per day (useful for displaying per-day rule badges in the UI)

Required permission: `EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW`.

#### `assignShifts(Request, string $constraintId): JsonResponse` ← **NEW**

`POST /{constraint}/shifts`

Replaces the `time_rules.weekly_schedule` for the constraint with the provided shift configuration. Supports two modes:

| Mode | Description |
|---|---|
| `weekly` | A single set of periods (same hours every day) applied to `days`. Unchecked days are set to `enabled: false`. |
| `daily` | Each day in `schedule` gets its own period list. Days omitted from `schedule` are set to `enabled: false`. |

**Key invariants:**
1. Only `time_rules.weekly_schedule` is modified — the rest of `constraint_config` (e.g. `location_rules`) is preserved by merging.
2. If a day had no `lateness_rules` or `early_clock_in_rules` before, defaults are automatically applied: `lateness_period: 30 minutes` and `allowed_minutes_before: 30`. Existing per-day rules are never overwritten.
3. Bumps the applicable-constraints cache after saving.

**Weekly mode request body:**
```json
{
  "mode": "weekly",
  "days": ["saturday", "sunday", "monday", "tuesday", "wednesday"],
  "periods": [
    { "start_time": "08:00", "end_time": "17:00", "extends_to_next_day": false }
  ]
}
```

**Daily mode request body:**
```json
{
  "mode": "daily",
  "schedule": {
    "saturday": { "periods": [{ "start_time": "08:00", "end_time": "12:00", "extends_to_next_day": false }] },
    "sunday":   { "periods": [{ "start_time": "09:00", "end_time": "18:00", "extends_to_next_day": false }] }
  }
}
```

Required permission: `EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE`.

#### `updateRules(Request, string $constraintId): JsonResponse` ← **NEW**

`PATCH /{constraint}/rules`

Updates constraint-level rules that apply uniformly across all working days. All three fields are optional.

| Request field | Type | Notes |
|---|---|---|
| `max_over_time` | numeric, min:0 | Stored directly on the `attendance_constraints` row. |
| `lateness_minutes` | integer, min:0 | Applied to `lateness_rules.lateness_period` on **every** day in `weekly_schedule`. |
| `early_clock_in_minutes` | integer, min:0 | Applied to `early_clock_in_rules.allowed_minutes_before` on **every** day in `weekly_schedule`. |

Days with `enabled: false` are also updated so that if they are later enabled, the rules are already correct.

Bumps the applicable-constraints cache after saving.

Required permission: `EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE`.

---

## 9. Jobs

### 9.1 AutoCloseAttendanceJob

**`Jobs/AutoCloseAttendanceJob.php`**

Dispatched by `AttendanceService::scheduleAutoCloseAtMaxOvertime()` at clock-in time.

**Critical distinction — trigger time vs stored time:**
- **`->delay($deadline)`** = `end_time + max_over_time_hours * 60 min` — when the queue worker fires.
- **`$closeAtIso`** = `end_time` (the shift's scheduled end, as ISO 8601 with TZ offset) — what gets stored as `clock_out_time`.

Employees who never clock out are capped at scheduled hours with zero overtime. This matches `AutoCloseStaleShiftsCommand`'s behaviour. See **INV-14**.

```php
class AutoCloseAttendanceJob implements ShouldQueue {
    public function __construct(
        public readonly string $attendanceId,
        public readonly string $companyId,
        /** ISO 8601 with TZ offset — equals end_time (NOT end_time + max_over_time). */
        public readonly string $closeAtIso,
    ) {}

    public function handle(AutoCloseAttendanceService $service): void {
        tenancy()->initialize($this->companyId);
        try {
            $attendance = Attendance::find($this->attendanceId);
            if (!$attendance) { Log::warning(…); return; }
            $closeAt = CarbonImmutable::parse($this->closeAtIso); // preserves TZ offset
            $service->closeIfExpired($attendance, $closeAt, 'auto_max_ot');
        } finally {
            tenancy()->end();
        }
    }
}
```

**Why tenancy re-initialization is needed in jobs:**
Jobs run in a separate worker process that has no HTTP request context. The tenancy middleware (`InitializeTenancyByRequestData`) does not run. Jobs must manually call `tenancy()->initialize($companyId)` to set up the correct DB connection for the tenant. The `finally` block ensures tenancy is always ended even if an exception is thrown.

### 9.2 AutoClockOutAtNextShiftStartJob

**`Jobs/AutoClockOutAtNextShiftStartJob.php`**

Dispatched by `AttendanceService::scheduleAutoClockOutWhenNextShiftStarts()`.
Delay = next scheduled period's `start_time`. The `clockOutAtIso` must be passed as ISO 8601 **with TZ offset** (via `->toIso8601String()`). Passing `->format('Y-m-d H:i:s')` would drop the offset, causing the worker to re-parse the time as UTC and shift the stored wall-clock by the branch offset. See **INV-15**.

```php
class AutoClockOutAtNextShiftStartJob implements ShouldQueue {
    public function __construct(
        public readonly string $attendanceId,
        public readonly string $companyId,
        /** ISO 8601 with TZ offset — must use ->toIso8601String(), not ->format('Y-m-d H:i:s'). */
        public readonly string $clockOutAtIso,
    ) {}

    public function handle(AutoCloseAttendanceService $service): void {
        tenancy()->initialize($this->companyId);
        try {
            $attendance = Attendance::find($this->attendanceId);
            if (!$attendance) { return; }
            $closeAt = CarbonImmutable::parse($this->clockOutAtIso);
            $service->closeIfExpired($attendance, $closeAt, 'auto_next_shift');
        } finally {
            tenancy()->end();
        }
    }
}
```

### 9.3 ProcessClockInAttendanceData

**`Jobs/ProcessClockInAttendanceData.php`**

Dispatched when a shift `extends_to_next_day = true`. Fires at `end_time` (midnight-crossing moment). Handles any day-boundary processing needed for overnight shifts.

### 9.4 SyncHolidayAttendanceJob

Processes holiday attendance records (creates `is_holiday = 1` rows for users scheduled on holidays).

---

## 10. Events & Listeners

### 10.1 AttendanceClockedIn (Event)

**`Events/AttendanceClockedIn.php`**

Dispatched by `ClockInService::execute()` (via `MockAttendanceService::persistClockIn`) after a successful clock-in.

Carries: the `Attendance` model instance.

### 10.2 HandleAttendanceLateness (Listener)

**`Listeners/HandleAttendanceLateness.php`**

Listens for `AttendanceClockedIn`. Evaluates whether the clock-in was late (using `AttendanceCalculator`) and creates a constraint violation record if needed. This runs synchronously in the HTTP request but is designed to be non-blocking (wrapped in try/catch).

**Registered in `AttendanceServiceProvider::registerEventListeners()`:**
```php
Event::listen(AttendanceClockedIn::class, HandleAttendanceLateness::class);
```

---

## 11. Exceptions

### 11.1 AttendanceException

**`Exceptions/AttendanceException.php`**

Extends `App\Exceptions\CustomException`. All named constructors:

| Static factory | HTTP code | Message |
|---|---|---|
| `alreadyClockedIn()` | 400 | "You are already clocked in. Please clock out first." |
| `notClockedIn()` | 400 | "You are not currently clocked in." |
| `alreadyClockedOut()` | 400 | "You have already clocked out for today." |
| `alreadyOnBreak()` | 400 | "You are already on break. Please end your current break first." |
| `notOnBreak()` | 400 | "You are not currently on break." |
| `onBreak()` | 400 | "You are currently on break. Please end your break first." |
| `attendanceNotFound()` | 404 | "Attendance record not found." |
| `cannotModifyPastAttendance()` | 403 | "Cannot modify attendance records from previous days." |
| `invalidClockOutTime()` | 400 | "Clock out time cannot be before clock in time." |
| `attendanceAlreadyApproved()` | 400 | "This attendance record has already been approved." |
| `unauthorizedToApprove()` | 403 | "You are not authorized to approve this attendance record." |
| `cannotDeleteApprovedAttendance()` | 403 | "Cannot delete approved attendance records." |
| `cannotRejectApprovedAttendance()` | 409 | "Cannot reject an attendance record that has already been approved." |
| `userNotFound()` | 404 | "User not found." |
| `cannotModifyConstraintWithOpenAttendance()` | 409 | "Cannot modify this attendance constraint while any linked employee is still clocked in..." |
| `clockInBlocked(array $violations)` | 422 | First violation message; carries `violations` array accessible via `getViolations()` |

---

## 12. Presenters

### 12.1 AttendancePresenter

**`Presenters/AttendancePresenter.php`**

**API PAYLOAD IS FROZEN.** The shape of the output must never change (no field added, removed, renamed, or retyped) unless explicitly coordinated with all consumers.

**`requiredRelations(): array`** — call this before loading attendance records to avoid N+1:
```php
[
    'user',
    'user.professionalData.jobTitle',
    'user.professionalData.department',
    'user.professionalData.branch',
    'user.professionalData.management',
    'user.professionalData.attendanceConstraint',
    'company',
    'approvedBy',
    'breaks',
    'appliedAttendanceConstraint',
]
```

**Output shape (all fields, frozen):**
```json
{
    "id": "uuid",
    "user_id": "uuid",
    "company_id": "uuid",
    "clock_in_time": "2024-01-15 09:05:00",
    "clock_out_time": "2024-01-15 17:30:00",
    "start_time": "2024-01-15 09:00:00",
    "end_time": "2024-01-15 17:00:00",
    "timezone": "Asia/Riyadh",
    "total_work_hours": 8.25,
    "total_break_hours": 1.0,
    "overtime_hours": 0.5,
    "is_late": 1,
    "is_absent": 0,
    "is_holiday": 0,
    "is_early_departure": 0,
    "late_minutes": 5,
    "early_departure_minutes": 0,
    "status": "completed",
    "day_status": "clocked_out",
    "shift_end_method": "manual",
    "business_date": "2024-01-15",
    "approved_by": null,
    "approved_at": null,
    "clock_in_location": {"latitude": 24.7136, "longitude": 46.6753},
    "clock_out_location": null,
    "notes": "...",
    "ip_address": "...",
    "work_date": "2024-01-15",
    "is_on_break": false,
    "is_clocked_in": 1,
    "duration_formatted": "8h 15m",
    "break_duration_formatted": "1h 0m",
    "overtime_formatted": "0h 30m",
    "created_at": "...",
    "updated_at": "...",
    "user": {
        "id": "uuid",
        "name": "...",
        "email": "...",
        "birthdate": "...",
        "country": "...",
        "gender": "...",
        "phone": "..."
    },
    "company": {"id": "uuid", "name": "..."},
    "approved_by_user": null,
    "breaks": [
        {
            "id": "uuid",
            "start_time": "2024-01-15 12:00:00",
            "end_time": "2024-01-15 13:00:00",
            "duration_minutes": 60,
            "duration_formatted": "1h 0m",
            "notes": null,
            "is_active": false
        }
    ],
    "professional_data": {
        "job_title": "...",
        "job_code": "...",
        "department": "...",
        "branch": "...",
        "management": "...",
        "attendance_constraint": {...}
    }
}
```

---

## 13. HTTP Layer — Requests & Routes

### 13.1 Routes

Routes are loaded by `RouteServiceProvider`. The attendance API routes use the `InitializeTenancyByRequestData` middleware which reads the company/tenant from the request and sets up the DB connection.

**Key API routes:**
```
POST   /api/attendance/clock-in          → AttendanceController::clockIn
POST   /api/attendance/clock-out         → AttendanceController::clockOut
POST   /api/attendance/start-break       → AttendanceController::startBreak
POST   /api/attendance/end-break         → AttendanceController::endBreak
GET    /api/attendance/team              → AttendanceController::getTeamAttendance
GET    /api/attendance/history           → AttendanceController::getAttendanceHistory
GET    /api/attendance/user-constraints  → UserAttendanceController::getUserConstraints
GET    /api/attendance/user-history      → UserAttendanceController::getUserAttendanceHistory
```

**Constraint management routes (all prefixed `/api/v1/attendance/constraints`):**
```
GET    /                              → index          (list with pagination)
GET    /list                          → list           (simplified list)
POST   /                              → store          (create constraint)
GET    /user                          → userConstraint (today's rules for authed user)
PUT    /{constraint}                  → update         (full update)
DELETE /{constraint}                  → destroy
GET    /types                         → getConstraintTypes
POST   /validate                      → validate
GET    /branches/{branchId}           → getConstraintsByBranch
POST   /branches/{branchId}/bulk-assign → bulkAssignToBranch
GET    /branches/{branchId}/inherited → getInheritedConstraints
GET    /violations                    → getViolations
PUT    /violations/{violation}/resolve → resolveViolation
PUT    /violations/{violation}/dismiss → dismissViolation
GET    /statistics                    → getStatistics
POST   /bulk/activate                 → bulkActivate
POST   /bulk/deactivate               → bulkDeactivate
POST   /bulk/delete                   → bulkDelete
GET    /{constraint}                  → show

--- New routes (2026-05-14) ---
PATCH  /{constraint}/basic-info       → updateBasicInfo
GET    /{constraint}/employees        → getConstraintEmployees
POST   /{constraint}/employees        → assignEmployeeToConstraint
GET    /{constraint}/locations        → getLocations
POST   /{constraint}/locations        → createLocations
PUT    /locations/{location}          → updateLocation
DELETE /locations/{location}          → deleteLocation
GET    /{constraint}/day-shifts       → getDayShifts
GET    /{constraint}/shifts           → getShifts      (frontend-ready, detects mode)
POST   /{constraint}/shifts           → assignShifts   (weekly / daily mode)
PATCH  /{constraint}/rules            → updateRules    (lateness + early clock-in + max overtime)

--- Per-user additional constraints ---
GET    /users/{userId}/additional     → getUserAdditionalConstraints
POST   /users/{userId}/additional     → assignUserConstraints
DELETE /users/{userId}/additional/{constraintId} → removeUserConstraint
```

**Permission mapping for new routes:**

| Route | Permission |
|---|---|
| `PATCH /{constraint}/basic-info` | `EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE` |
| `GET /{constraint}/employees` | `EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW` |
| `POST /{constraint}/employees` | `EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE` |
| `GET /{constraint}/locations` | `EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW` |
| `POST /{constraint}/locations` | `EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE` |
| `PUT /locations/{location}` | `EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE` |
| `DELETE /locations/{location}` | `EMPLOYEE_ATTENDANCE_CONSTRAINTS_DELETE` |
| `GET /{constraint}/day-shifts` | `EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW` |
| `GET /{constraint}/shifts` | `EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW` |
| `POST /{constraint}/shifts` | `EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE` |
| `PATCH /{constraint}/rules` | `EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE` |

### 13.2 Form Requests

**ClockInRequest** — validates:
- `latitude`: required, numeric
- `longitude`: required, numeric
- `notes`: optional, string
- `clock_in_time`: optional, defaults to `now()` in branch TZ

**ClockOutRequest** — validates:
- `latitude`, `longitude`: required, numeric
- `notes`: optional

Both have a `toDTO()` method that assembles the DTO from the validated data + `auth()->user()`.

---

## 14. Console Commands & Schedules

### 14.1 SendAttendanceSilentNotificationCommand

**`app/Console/Commands/SendAttendanceSilentNotificationCommand.php`**
**Artisan command:** `attendance:send-clock-in-pings`
**Options:** `--dry-run`

**Purpose:** Sends silent FCM push notifications to all currently clocked-in employees so their mobile apps can sync attendance state in the background.

**Does NOT perform auto-close.** Auto-close is handled by `AutoCloseAttendanceJob` and `AutoClockOutAtNextShiftStartJob`.

**Flow:**
1. Query all attendances where `clock_in_time IS NOT NULL AND clock_out_time IS NULL` (active shifts).
2. For each attendance with a user that has an `fcm_token`:
   - Build notification data: `{ type: "attendance_tracking", attendance_id, user_id, clock_in_time, status, action: "sync_attendance_status" }`.
   - If `--dry-run`: log user name/email, skip send.
   - Otherwise: `FirebaseNotificationService::sendSilent($token, $data)`.
   - Log success/failure.
3. Output count summary.

### 14.2 CreateWaitingAttendanceCommand

**Artisan:** `attendance:create-waiting`
**Schedule:** every 6 hours

Creates `STATUS_WAITING` attendance rows for all users who have upcoming shifts in the next window. These rows are promoted to `STATUS_ACTIVE` when the user clocks in (`persistClockInAttendance` looks for them by `user_id + start_time + clock_in_time IS NULL`).

### 14.3 CreateHolidayAttendanceCommand

**Artisan:** `attendance:create-holiday-attendance`
**Schedule:** daily at 00:05 Asia/Riyadh

Creates `is_holiday = 1` attendance rows for users whose scheduled workday falls on a company holiday.

---

## 15. Service Container & Octane Safety

### 15.1 Registered Singletons

All registered in `AttendanceServiceProvider::register()`:

```php
// Domain — stateless, safe
Clock::class            → SystemClock::class
TimezoneResolver::class → singleton
AutoBreakComputer::class → singleton
LatenessPolicy::class   → StandardLatenessPolicy::class
OvertimePolicy::class   → StandardOvertimePolicy::class
EarlyDeparturePolicy::class → StandardEarlyDeparturePolicy::class
AttendanceCalculator::class → factory (injects policies)

// Application — stateless, safe
AutoCloseAttendanceService::class → singleton
ClockInService::class             → singleton
ClockOutService::class            → singleton
```

The constraint services are registered in `ConstraintServiceProvider` as singletons.

### 15.2 Octane Flush Array

Services that must be flushed between requests (in `config/octane.php`):
```php
'flush' => [
    \Modules\Attendance\Services\UserAttendanceService::class,
    \Modules\Attendance\Services\UserAttendanceHistoryService::class,
],
```

### 15.3 Octane Safety Rules

1. **No mutable instance state on singletons.** If a property can change between requests, it must NOT be an instance property on a singleton.
2. **Per-request memoization: use `once()` helper.** The `getTimeZoneBranchByRequest()` global helper uses `once()` internally. Under RoadRunner, `once()` is properly reset between requests.
3. **Cross-request caching: use `Cache::remember()` with Redis.** Never store cross-request cache in an instance property.
4. **Never store `auth()->user()` in a singleton property.** Auth context changes between requests.

---

## 16. Business Rules Encyclopaedia

### 16.1 Lateness Rules

- An employee is considered **late** if their `clock_in_time > scheduled_start + grace_period_minutes`.
- `late_minutes` = difference in full minutes between `scheduled_start` and `clock_in_time` (not between `grace_threshold` and `clock_in_time`).
- Grace period is read from `applied_attendance_constraints.constraint_snapshot.lateness_rules.lateness_period` + `lateness_unit`.
- If `lateness_unit = 'hour'`, multiply by 60. If `= 'day'`, multiply by 1440.
- Source of truth: `StandardLatenessPolicy::evaluate()`.

### 16.2 Overtime Rules

- Overtime = `net_worked_minutes - scheduled_minutes`, capped at `max_over_time_hours * 60`.
- `max_over_time` is snapshotted onto the `attendances` row at clock-in time (column `max_over_time`, stored in HOURS, decimal).
- If `max_over_time = 0`, no overtime is recorded regardless of how long the employee worked.
- Source of truth: `StandardOvertimePolicy::calculate()`.

### 16.3 Early Departure Rules

- Employee left early if `clock_out_time < scheduled_end_time`.
- `early_departure_minutes = scheduledEnd.diffInMinutes(clockOut)`.
- Source of truth: `StandardEarlyDeparturePolicy::evaluate()`.

### 16.4 Break Rules

- Breaks are stored as `AttendanceBreak` rows in `attendance_breaks`.
- A break is **active** when `start_time IS NOT NULL AND end_time IS NULL`.
- At most one active break at any time (enforced by `isOnBreak()` guard in `startBreak()`).
- `duration_minutes` is set when `end_time` is recorded.
- **Only completed breaks** (those with `end_time`) contribute to `total_break_hours` and to the `totalBreakMinutes` input to the calculator.
- An active break (no `end_time`) is NOT counted in the calculator — this is intentional.

### 16.5 Multi Clock-In / Clock-Out Per Day

One `attendances` row per scheduled period per calendar day.
- `clock_in_time` = first clock-in; NEVER overwritten.
- `clock_out_time` = latest clock-out; overwritten on each clock-out.
- On re-clock-in after clock-out: the status transitions back from `completed → active`.
- The gap between the previous `clock_out_time` and new `clock_in_time` should be auto-computed as a break via `AutoBreakComputer::computeGap()`.

### 16.6 Overnight Shifts

A shift is overnight when `end_time < start_time` on the same calendar date.
The fix: `if ($scheduledEnd <= $scheduledStart) { $scheduledEnd = $scheduledEnd->addDay(); }`.
This is applied in both `AttendanceService::resolveWorkPeriodBounds()` and `buildCalculatorInput()` and `AutoCloseAttendanceService::buildCalculatorInput()`.

### 16.7 Status Flow

```
CREATE  → waiting (pre-created by command; no clock-in yet)
              │
              ▼ clock-in
            active
              │
       ┌──────┴─────────┐
       │                │
       ▼                ▼
   completed     pending_approval
   (auto/manual  (if approval flow
    clock-out)    enabled)
                        │
               ┌────────┴────────┐
               ▼                 ▼
           approved           rejected
```

### 16.8 Constraint System

7 types of constraints, each with its own service:

| Type | Service | What it checks |
|---|---|---|
| `time` | `TimeConstraintService` | Shift enforcement, lateness, break rules, overtime, multiple periods |
| `location` | `LocationConstraintService` | Geofencing (radius check), IP whitelisting, remote work zones |
| `device` | `DeviceConstraintService` | Device whitelist, device type, registration, security |
| `role` | `RoleConstraintService` | Management hierarchy, permissions, department, seniority |
| `behavioral` | `BehavioralConstraintService` | Frequency patterns, anomaly detection |
| `security` | `SecurityConstraintService` | 2FA, biometric, audit, fraud detection |
| `compliance` | `ComplianceConstraintService` | Labor law, union rules, industry regulations |

`AttendanceConstraintService` is the **facade** that coordinates all 7. It maintains backward compatibility so callers don't need to know which sub-service handles a given constraint type.

### 16.9 Auto-Close Triggers

| Trigger | Job/Command | `shift_end_method` | Job fires at | `clock_out_time` stored |
|---|---|---|---|---|
| Max overtime exceeded | `AutoCloseAttendanceJob` | `auto_max_ot` | `end_time + max_over_time_hours * 60 min` | **`end_time`** (shift's scheduled end) |
| Next shift starting | `AutoClockOutAtNextShiftStartJob` | `auto_next_shift` | Next period's `start_time` | Next period's `start_time` |
| Safety-net cron | `AutoCloseStaleShiftsCommand` (every 5 min) | `auto_max_ot` | Any time after `end_time + max_over_time` | **`end_time`** (shift's scheduled end) |
| Outside geofence | (future) | `auto_radius` | At detection | `now()` at detection |
| Employee manual | HTTP clock-out | `manual` | N/A | Actual clock-out time |

> **Note:** `AutoCloseAttendanceJob` and `AutoCloseStaleShiftsCommand` always store the same `clock_out_time` (`end_time`). The job fires at the deadline for precise timing; the command is a safety net for lost/delayed jobs. Both delegate to `AutoCloseAttendanceService::closeIfExpired()` which holds the row lock.

---

## 17. Full Clock-In Flow (Step-by-Step)

```
HTTP POST /api/attendance/clock-in
    {latitude: 24.7136, longitude: 46.6753, notes: "..."}
         │
         ▼
ClockInRequest::authorize() + rules()   [validates fields]
         │
         ▼
AttendanceController::clockIn()
    $dto = $request->toDTO()                    [builds ClockInDTO]
    $attendance = $clockInService->execute($dto, $request->all())
         │
         ▼
ClockInService::execute()
    $violations = MockAttendanceService::validateClockIn($dto, $requestData)
    if blocking violations → throw AttendanceException::clockInBlocked() [HTTP 422]
    return MockAttendanceService::persistClockIn($dto, $requestData)
         │
         ▼
AttendanceService::clockIn()
    1. ensureUserHasNoActiveClockIn($userId)
       └─ attendanceRepository::getCurrentAttendance($userId)
          └─ if active row exists → throw alreadyClockedIn() [HTTP 400]
    2. $timezone = getTimeZoneBranchByRequest() ?? config('app.timezone')
    3. $currentDate = Carbon::now($timezone)->format('Y-m-d')
    4. $constraints = AttendanceConstraintService::getTodaysWorkRulesForUser($user, $currentDate)
    5. [$startDateTime, $endDateTime] = resolveWorkPeriodBounds($constraints, $currentDate, $timezone)
       └─ overnight fix: if end <= start, end += 1 day
    6. enforceEarlyClockInRule($dto, $startDateTime, $constraints, $timezone)
       └─ if prevent_early_clock_in AND clockInMoment < earliestAllowed → throw Exception [generic 500]
    7. $attendanceData = buildClockInAttendanceData(...)
       └─ includes: user_id, company_id, clock_in_time, clock_in_location, start_time, end_time,
                    status='active', is_absent=0, is_late=0, is_holiday=0, day_status='in_location',
                    timezone, max_over_time (snapshot), business_date
    8. persistClockInAttendance($userId, $startDateTime, $attendanceData)
       └─ look for waiting row: user_id + start_time + clock_in_time IS NULL
          ├─ found → $waiting->update($attendanceData) + refresh()
          └─ not found → attendanceRepository::create($attendanceData)
    9. if extends_to_next_day → dispatch ProcessClockInAttendanceData delayed to $endDateTime
    10. scheduleAutoClockOutWhenNextShiftStarts($attendance, $constraints, $endDateTime)
        └─ resolveNextShiftStartAfterPeriodEnd() → find next period start > $currentPeriodEnd
           └─ if found && future → dispatch AutoClockOutAtNextShiftStartJob delayed to $nextStart
    11. scheduleAutoCloseAtMaxOvertime($attendance, $endDateTime, $maxOverTimeHours)
        └─ $closeAt = $endDateTime + max_over_time * 60 minutes
           └─ if $closeAt is future → dispatch AutoCloseAttendanceJob delayed to $closeAt
    12. return $attendance
         │
         ▼
AttendanceController (back)
    AttendanceClockedIn event dispatched (by MockAttendanceService)
         │
         ▼
HandleAttendanceLateness listener fires synchronously
    Evaluates lateness via AttendanceCalculator
    Creates constraint violation record if late
         │
         ▼
AttendancePresenter::present($attendance) [loads relations if not loaded]
return Json::item([...30+ fields...])   [HTTP 200]
```

---

## 18. Full Clock-Out Flow (Step-by-Step)

```
HTTP POST /api/attendance/clock-out
    {latitude: 24.7136, longitude: 46.6753}
         │
         ▼
ClockOutRequest::authorize() + rules()
         │
         ▼
AttendanceController::clockOut()
    $dto = $request->toDTO()
    $attendance = $clockOutService->execute($dto)
         │
         ▼
ClockOutService::execute()
    return AttendanceService::clockOut($dto)
         │
         ▼
AttendanceService::clockOut()
    1. $attendance = attendanceRepository::getCurrentAttendance($dto->getUserId())
       └─ null → throw notClockedIn() [HTTP 400]
    2. $attendance->clock_out_time !== null → throw alreadyClockedOut() [HTTP 400]
    3. attendanceRepository::update($attendance->id, {
           clock_out_time: Carbon::parse($dto->getClockOutTime())->setTimezone(getTimeZoneBranchByRequest()),
           clock_out_location: $dto->getLocation(),
           notes: append $dto->getNotes(),
           status: 'completed',
           day_status: 'clocked_out',
       })
    4. $attendance->refresh()
    5. $input = buildCalculatorInput($attendance)   [reads breaks, snapshot, timezone]
    6. $result = calculator->calculate($input)      [pure domain function]
    7. $attendance->update({
           total_work_hours, total_break_hours, overtime_hours,
           is_late, late_minutes, is_early_departure, early_departure_minutes
       })                                           [single UPDATE for all 7 fields]
    8. return $attendance->refresh()
         │
         ▼
AttendanceController (back)
    Post-validation: loop constraint checks (non-blocking, logs violations)
         │
         ▼
AttendancePresenter::present($attendance)
return Json::item([...30+ fields...])   [HTTP 200]
```

---

## 19. Auto-Close Flow (Step-by-Step)

This flow has **two entry points** that converge into the same writer (`AutoCloseAttendanceService`).

### Entry 1: Max-Overtime Job (`auto_max_ot`)

```
[Queue worker, no HTTP request]
AutoCloseAttendanceJob::handle()
    1. tenancy()->initialize($this->companyId)
    2. $attendance = Attendance::find($this->attendanceId)
       └─ not found → Log::warning + return
    3. $closeAt = CarbonImmutable::parse($this->closeAtIso)
       └─ closeAtIso = $endDateTime->toIso8601String()  ← the shift's end_time (NOT end_time + max_over_time)
       └─ the job was DELAYED to end_time + max_over_time, but it SAVES end_time
    4. $closed = AutoCloseAttendanceService::closeIfExpired($attendance, $closeAt, 'auto_max_ot')
    5. finally: tenancy()->end()
```

### Entry 2: Next-Shift Job (`auto_next_shift`)

```
[Queue worker]
AutoClockOutAtNextShiftStartJob::handle()
    1. tenancy()->initialize($this->companyId)
    2. $attendance = Attendance::find($this->attendanceId)
    3. $closeAt = CarbonImmutable::parse($this->clockOutAtIso)
    4. $closed = AutoCloseAttendanceService::closeIfExpired($attendance, $closeAt, 'auto_next_shift')
    5. finally: tenancy()->end()
```

### The Writer (`AutoCloseAttendanceService::closeIfExpired`)

```
DB::transaction()
    │
    ├── Attendance::lockForUpdate()->find($attendance->id)
    │       [acquires MySQL row lock; second concurrent caller BLOCKS here until commit]
    │
    ├── Re-read state after lock:
    │   └─ if not found OR status != 'active' OR clock_out_time IS NOT NULL OR clock_in_time IS NULL
    │       └─ return false   [no-op: already closed by concurrent writer]
    │
    ├── buildCalculatorInput($fresh, $closeAt)
    │       [reads breaks, constraint snapshot, sets clockOut = $closeAt (NOT now())]
    │
    ├── calculator->calculate($input)
    │       [pure domain; returns WorkHoursResult]
    │
    ├── $branchTz = $fresh->timezone ?: config('app.timezone') ?: 'Asia/Riyadh'
    │   $closeAtInBranch = $closeAt->setTimezone($branchTz)   // normalise to branch TZ for storage
    │
    ├── $fresh->update({
    │       clock_out_time: $closeAtInBranch->format('Y-m-d H:i:s'),   // branch-TZ wall clock, deterministic
    │       clock_out_location: last location point or clock_in_location,
    │       status: 'completed',
    │       day_status: 'clocked_out',
    │       shift_end_method: $reason,
    │       total_work_hours, total_break_hours, overtime_hours,
    │       is_late, late_minutes, is_early_departure, early_departure_minutes,
    │       notes: append "[Auto] Clock-out: $reason at ..."
    │   })
    │
    └── return true
```

---

## 20. Timezone Strategy

### 20.1 Where Times Are Stored

All datetime columns in `attendances` are stored in the **branch timezone** (e.g., `Asia/Riyadh`). This is a historical decision that cannot be changed without a data migration. Key consequence: there are no UTC-converting accessors on the model.

### 20.2 The Timezone Column

`attendances.timezone` is set at clock-in time from `getTimeZoneBranchByRequest()`. It is then frozen — this means even if the company changes its timezone setting later, completed attendance records use the correct TZ for all calculations.

### 20.3 Resolving Timezone

**Order of priority:**
1. `attendance.timezone` (for any existing attendance row)
2. `getTimeZoneBranchByRequest()` (for new clock-ins during an HTTP request)
3. `user → userProfessionalData → branch → address → country → timezones[0]` (via `TimezoneResolver::forUser()`)
4. `config('app.timezone')`
5. `'Asia/Riyadh'` (hard fallback)

### 20.4 `business_date` Column

`business_date` is the calendar day in the branch timezone (e.g., `2024-01-15`). It is set at clock-in:
```php
'business_date' => $startDateTime->toDateString(),  // already in branch TZ
```

It is critical for the `getTeamAttendance()` GROUP BY and for the date-based indexes.

### 20.5 `getTimeZoneBranchByRequest()` Global Helper

This function reads the branch timezone from the authenticated user's branch → address → country → timezones chain. It uses `once()` internally so it is computed only once per request cycle and does not leak between requests under Octane.

### 20.6 The Carbon Parsing Trap — `parse($str, $tz)` vs `parse($str)->setTimezone($tz)`

This is the single most dangerous mistake in this codebase. The two patterns look similar but do **completely different things**:

```php
// ✅ CORRECT — labels the wall-clock string as already being in branch TZ
$start = CarbonImmutable::parse('2024-01-15 08:30:00', 'Asia/Riyadh');
// Result: 2024-01-15 08:30:00 Asia/Riyadh  (absolute: 05:30 UTC)

// ❌ WRONG — parses the string as UTC first, then CONVERTS to branch TZ
$start = CarbonImmutable::parse('2024-01-15 08:30:00')->setTimezone('Asia/Riyadh');
// Result: 2024-01-15 11:30:00 Asia/Riyadh  (absolute: 08:30 UTC — shifted by +3h!)
```

**Why this matters:** `APP_TIMEZONE = UTC` (see `config/app.php`). So `CarbonImmutable::parse($str)` with no second argument treats the string as **UTC**. The attendance datetime columns (`start_time`, `end_time`, `clock_in_time`, `clock_out_time`) are stored as **branch-timezone wall-clock strings** (e.g., `2024-01-15 08:30:00` means 08:30 in Asia/Riyadh, NOT in UTC). Using `->setTimezone()` shifts every value by the branch UTC offset, corrupting all downstream calculations:

| Field (Asia/Riyadh, UTC+3) | Wrong (setTimezone) | Correct (parse with tz) |
|---|---|---|
| `start_time = "08:30:00"` | Parsed as 08:30 UTC → 11:30 Riyadh | Stays 08:30 Riyadh |
| `clock_in_time = "08:33:31"` | Parsed as 08:33 UTC → 11:33 Riyadh | Stays 08:33 Riyadh |
| `end_time = "17:30:00"` | Parsed as 17:30 UTC → 20:30 Riyadh | Stays 17:30 Riyadh |

**Exception — `$closeAt` passed into `closeIfExpired`:** This argument comes from `CarbonImmutable::parse($iso)` where `$iso` is `->toIso8601String()` output (includes the TZ offset, e.g., `+03:00`). Such strings parse correctly because the offset is embedded. The `setTimezone($branchTz)` call inside `closeIfExpired` is then harmless (it re-labels to the named TZ, which represents the same absolute instant).

**Rule:** Always use `CarbonImmutable::parse($dbString, $timezone)` when reading from the `attendances` table. See **INV-13**.

### 20.7 Display Formatting — Hours, Overtime, Lateness Are Always `HH:MM` Strings

The DB stores work / break / overtime as `DECIMAL(8,2)` and lateness / early-departure as integer minutes (see migration `2025_06_18_223500_create_attendances_table`). On the **wire**, however, every report and history endpoint exposes these values as zero-padded `HH:MM` strings produced by a single helper:

```
Modules\Attendance\Support\HoursFormatter
  │  ├─ fromHours(float $hours)            → "HH:MM"
  │  ├─ fromMinutes(int $minutes)          → "HH:MM"
  │  └─ fromDecimalString($eloquentValue)  → "HH:MM"
  └─ covered by Tests/Unit/Support/HoursFormatterTest
```

**Why this exists — the "09:93" bug:**
A mobile FE was rendering the raw `total_work_hours: 9.93` (a decimal-hour value = 9h 56m) by splitting on the dot, producing `09:93`. The colon-93 string is not a valid clock time — minutes ≥ 60 should always carry into hours. The fix is to format on the **backend** so clients receive only normalised strings.

**API contract (the only legal shape for these fields):**

| Field | DB type | Wire type | Example |
|---|---|---|---|
| `total_work_hours` | DECIMAL(8,2) hours | `"HH:MM"` string | `"10:33"` |
| `total_break_hours` | DECIMAL(8,2) hours | `"HH:MM"` string | `"00:30"` |
| `overtime_hours` | DECIMAL(8,2) hours | `"HH:MM"` string | `"01:33"` |
| `late_minutes` | INT minutes | `"HH:MM"` string | `"00:03"` |
| `early_departure_minutes` | INT minutes | `"HH:MM"` string | `"00:15"` |
| `delay_hours` (history endpoint) | derived | `"HH:MM"` string | `"00:03"` |

Hours are **never capped** at 24 — a 27-hour weekly summary returns `"27:00"`. The minute field is **always** in `[00, 59]`.

**Endpoints that ship HH:MM (verified):**
- `UserAttendanceController::getUserAttendanceHistory` → `UserAttendanceHistoryService::getUserAttendanceHistoryMobileApi` — `work_hours`, `delay_hours`, `overtime_hours`.
- `AttendanceController::index` / `getHistory` / `getLateArrivals` / `getEarlyDepartures` / `getOvertimeRecords` — via `AttendancePresenter::present()`.
- `AttendancePresenter::getSummaryData()` and `AttendancePresenter::getReportData()` — used by report exports.
- `AttendanceController::clockIn` / `clockOut` / `startBreak` / `endBreak` — also use `AttendancePresenter::present()`, so they emit `HH:MM` too. This is intentional: a single payload shape for the FE.

**Backwards-compatible aliases:** `AttendancePresenter::present()` still emits `duration_formatted`, `break_duration_formatted`, `overtime_formatted` in `"Xh Ym"` style for clients that already rely on those keys. They internally delegate to `HoursFormatter` so they share the same normalisation.

See **INV-16** for the rule and the regression test.

---

## 21. Concurrency & Race Conditions

### C1: Duplicate Active Attendance Rows

**Problem:** If two `clockIn` requests for the same user arrive simultaneously, both could pass the `ensureUserHasNoActiveClockIn()` check (they both read "no active row") and both create a new row.

**Fix:** The check `ensureUserHasNoActiveClockIn()` uses a DB query. The repository should use a unique constraint on `(user_id, status)` where `status = 'active'` (or similar). The SELECT FOR UPDATE pattern in `AutoCloseAttendanceService` also prevents creating duplicate active rows during auto-close.

**Test coverage:** `ClockInConcurrencyTest` (`@group requires-db`)

### C2: Concurrent Auto-Close Writers

**Problem:** `AutoCloseAttendanceJob` (max-OT timer) and `AutoClockOutAtNextShiftStartJob` (next-shift timer) can both fire within seconds of each other for the same attendance row. Without a lock, both could call `closeIfExpired`, both read `status = 'active'`, and both write `clock_out_time` — the second writer would overwrite the first's `clock_out_time` with a later timestamp.

**Fix:** `AutoCloseAttendanceService::closeIfExpired()` uses `DB::transaction() + lockForUpdate()`. The second writer waits for the lock, then re-reads `status = 'completed'`, and returns `false` without writing.

**Test coverage:** `AutoCloseRaceTest` (`@group requires-db`)

### C3: Stale Job Firing on Manually-Closed Shift

**Problem:** Employee manually clocks out at 16:55. The `AutoCloseAttendanceJob` (scheduled for 17:30) fires later and overwrites the manual `clock_out_time` and `shift_end_method`.

**Fix:** Same `SELECT FOR UPDATE` + status re-read. `status = 'completed'` after manual clock-out → job returns `false` → no overwrite.

**Test coverage:** `AutoCloseRaceTest::test_close_returns_false_for_already_completed_attendance()`

### Deterministic `clock_out_time`

`AutoCloseAttendanceService` stores `$closeAt` (the pre-computed boundary), not `now()`. If a queue worker is delayed 5 minutes, the employee's recorded clock-out is still `end_time`, not `end_time + 5 minutes`. The boundary is always `end_time` — computed at clock-in time from the constraint snapshot and passed as `closeAtIso` in the job constructor.

**Test coverage:** `AutoCloseRaceTest::test_clock_out_time_equals_close_at_not_wall_clock_time()`

---

## 22. Test Suite Map

All tests are in `modules/Attendance/Tests/`.

### Unit Tests (`Tests/Unit/`)

| File | What it tests |
|---|---|
| `Calculator/AttendanceCalculatorTest.php` | 12+ input combinations: late + grace, overnight, no-OT cap, DST boundary, no clock-out, multi break |
| `Calculator/StandardLatenessPolicyTest.php` | Lateness detection, grace window edge cases |
| `Calculator/StandardOvertimePolicyTest.php` | OT cap, zero cap, exact boundary |
| `Domain/AutoBreakComputerTest.php` | Gap creation, zero gap, negative gap |
| `DTO/CreateAttendanceConstraintDTOTest.php` | DTO construction & serialisation |

### Feature Tests (`Tests/Feature/`)

| File | `@group` | What it tests |
|---|---|---|
| `ClockFlow/ClockInConcurrencyTest.php` | `requires-db` | C1: second clock-in rejected; at most 1 active row |
| `ClockFlow/AutoCloseRaceTest.php` | `requires-db` | C2+C3: idempotency, deterministic closeAt, guard conditions, null clock_in guard |
| `AutoCloseAttendanceServiceTest.php` | none | Service resolves from container; ISO round-trip |
| `AttendanceConstraintsIntegrationTest.php` | `requires-db` | Full constraint evaluation flow |
| `Domain/TimezoneResolverTest.php` | none | TZ resolution chain |
| `Models/AttendanceModelTest.php` | `requires-db` | Status transitions, relationships |
| `Presenters/AttendancePresenterContractTest.php` | `requires-db` | Snapshot of presenter JSON (payload freeze) |

### Running Tests

```bash
# All tests (no DB required)
php artisan test --exclude-group requires-db

# DB-required tests (needs MySQL running + migrated)
php artisan test --group requires-db

# Specific class
php artisan test --filter AutoCloseRaceTest --group requires-db
php artisan test --filter ClockInConcurrencyTest --group requires-db
```

**Why `@group requires-db` tests use `DatabaseTransactions` not `RefreshDatabase`:**
The project's migration folder contains a raw MySQL dump for the `countries` table (with `AUTO_INCREMENT`, `ENGINE=InnoDB`) that cannot run on SQLite. `DatabaseTransactions` wraps each test in a transaction that rolls back without recreating the schema, so it requires a pre-migrated MySQL database but avoids the MySQL-dump migration problem.

---

## 23. Invariants Checklist (Dangerous Traps)

These are things that will cause silent wrong results or runtime errors if violated. Read carefully before making changes.

### INV-1: Never overwrite `clock_in_time` once set
Once an attendance row has `clock_in_time`, it must never be updated. The multi-clock-in flow relies on this (the first clock-in time represents when the employee arrived, even through break-and-return cycles).

### INV-2: `clock_out_time = $closeAt` in auto-close, never `now()`
If you change `AutoCloseAttendanceService` to use `CarbonImmutable::now()` instead of `$closeAt`, delayed jobs will penalise employees with extra overtime.

### INV-3: Always use `SELECT … FOR UPDATE` inside a transaction when closing a shift
Any code that reads `status` then writes `clock_out_time` must hold a row lock to prevent C2/C3 race conditions. Don't add a new auto-close path that bypasses `AutoCloseAttendanceService`.

### INV-4: `maxOverTimeHours` in the calculator is HOURS (decimal), not minutes
`max_over_time` on the `attendances` table stores a decimal value in **hours** (e.g., `4.5` means 4 hours 30 minutes). The `StandardOvertimePolicy` multiplies by 60 to get minutes. Don't pass raw minutes here.

### INV-5: Don't add UTC-converting accessors to the Attendance model
Times are stored in branch TZ. Adding `->setTimezone('UTC')` or `->utc()` in a model accessor would shift all stored times by the UTC offset and break every calculation.

### INV-6: Only COMPLETED breaks count in the calculator
`totalBreakMinutes` is calculated as `SUM(duration_minutes) WHERE end_time IS NOT NULL`. An employee currently on break has an active break (no `end_time`) that is intentionally excluded until they end it.

### INV-7: Grace period is from scheduledStart, but late_minutes is measured from scheduledStart
Even if the grace period is 15 minutes, `late_minutes = diff(scheduledStart, clockIn)`. The grace window only gates whether `is_late = true`. The recorded minutes are always from the actual shift start.

### INV-8: Presenter output shape is frozen — never add, remove, or rename fields
The JSON shape returned by `AttendancePresenter::present()` is consumed by mobile apps. Any structural change requires a mobile release and coordinated deployment. New fields must be added in a backward-compatible way (if added at all).

### INV-9: Jobs must re-initialize tenancy
Jobs run without the HTTP tenancy middleware. Any job that reads or writes attendance data must call `tenancy()->initialize($companyId)` at the start and `tenancy()->end()` in a `finally` block.

### INV-10: `is_absent` and `is_holiday` are NOT in the model's `$casts`
These columns return integers (0/1). Comparing with `=== true` will always be `false`. Use `== true` or cast explicitly. This is a known inconsistency in the model.

### INV-11: Overnight shifts need `scheduledEnd->addDay()`
Whenever you build a `CalculatorInput` from an attendance row, you must check if `scheduledEnd <= scheduledStart` and add a day if so. Both `AttendanceService::buildCalculatorInput()` and `AutoCloseAttendanceService::buildCalculatorInput()` do this — any new code building `CalculatorInput` must also do it.

### INV-12: `business_date` must be set at clock-in
The `getTeamAttendance()` method filters with `whereNotNull('business_date')`. Attendance rows without `business_date` are invisible to the team view. Always set `business_date = $startDateTime->toDateString()` in `buildClockInAttendanceData()`.

### INV-13: Always parse attendance datetime strings with the branch timezone as second argument
Attendance datetime columns (`start_time`, `end_time`, `clock_in_time`, `clock_out_time`) are stored as **branch-TZ wall-clock strings**. Read them back with:
```php
// ✅ Correct
CarbonImmutable::parse($attendance->start_time, $timezone);

// ❌ Wrong — parses as UTC then shifts by branch offset
CarbonImmutable::parse($attendance->start_time)->setTimezone($timezone);
```
The wrong form corrupts work-hours, overtime, and lateness calculations by shifting all attendance times by the branch UTC offset (e.g., +3 hours for Asia/Riyadh). Every `buildCalculatorInput()` in the codebase uses the correct form — any new code reading these columns must follow the same pattern. See **§20.6** for a full explanation.

### INV-14: `scheduleAutoCloseAtMaxOvertime` — trigger time ≠ stored `clock_out_time`
`AttendanceService::scheduleAutoCloseAtMaxOvertime()` dispatches `AutoCloseAttendanceJob` with:
- **`->delay($deadline)`** where `$deadline = end_time + max_over_time_hours * 60 min` — controls **when** the job fires.
- **`$closeAtIso = $endDateTime->toIso8601String()`** — the shift's **end_time**, which becomes `clock_out_time`.

These two values are intentionally different. The job fires at the max-OT deadline so precise timing is honoured; but the stored `clock_out_time` is the scheduled shift end (not the deadline), so employees who forget to clock out are capped at zero overtime. The `AutoCloseStaleShiftsCommand` fallback stores the same `end_time`. Both writers must always agree.

### INV-15: Always use `->toIso8601String()` when passing datetimes through job constructors
Job constructors receive datetime values as strings. The worker process has `APP_TIMEZONE = UTC`, so `CarbonImmutable::parse($str)` treats any timezone-less string as UTC:
```php
// ❌ Wrong — drops TZ info; worker parses as UTC and shifts by branch offset
SomeJob::dispatch($carbon->format('Y-m-d H:i:s'));

// ✅ Correct — embeds the TZ offset; parse() in the worker preserves the instant
SomeJob::dispatch($carbon->toIso8601String()); // e.g. "2024-01-15T08:30:00+03:00"
```
All attendance jobs (`AutoCloseAttendanceJob`, `AutoClockOutAtNextShiftStartJob`) follow this rule. Any new job that stores an attendance datetime must also use `->toIso8601String()`.

### INV-16: All attendance hour fields leaving the API must be `HH:MM` strings (via `HoursFormatter`)
Never return a raw decimal-hour value (`9.93`) or a raw minute count (`93`) from a presenter / report / history endpoint. Always funnel through `HoursFormatter::fromHours()` / `::fromMinutes()` / `::fromDecimalString()`:
```php
// ✅ Correct
'total_work_hours' => HoursFormatter::fromDecimalString($attendance->total_work_hours),
'late_minutes'     => HoursFormatter::fromMinutes((int) $attendance->late_minutes),

// ❌ Wrong — raw decimal lets the FE produce "09:93" by splitting on the dot
'total_work_hours' => (float) $attendance->total_work_hours,
```
The formatter guarantees:
- Minutes field is always `[00, 59]` — a value of 93 minutes always carries to `01:33`, never displays as `00:93`.
- Hours are never capped at 24 (weekly / monthly aggregates work).
- Negative values are clamped to `00:00`.
- Eloquent's `decimal:2` cast (which returns a *string*, not a float) is handled transparently.

**Test coverage:** `Tests/Unit/Support/HoursFormatterTest` — includes a property-style test that walks 0–120 hours in 0.17h increments and asserts the minute field is always `< 60`.

### INV-17: `getTodaysWorkRulesForUser` must use the current time on today — never midnight
`AttendanceConstraintService::getTodaysWorkRulesForUser(User $user, $date, $timezone)` receives `$date` as a bare `Y-m-d` string from several callers (`AttendanceService::clockIn()`, `UserAttendanceService::getUserConstraints()`, `UserAttendanceHistoryService`). `Carbon::parse('2026-04-27', $timezone)` resolves to **`2026-04-27 00:00:00`** — midnight. Passing that `$now` into `getCurrentOrNextPeriodDetails()` fails to match any scheduled period (all periods start at 12:33+), so:
- `current_period` stays `null`.
- `fallback_period` = `$allTodaysPeriods[0]` (the earliest period of the day).
- The caller sees `current_work_period` = the first period, regardless of the actual clock-in time.

This caused real-world bug: at 18:18 Riyadh time, clock-in rows were assigned `start_time=12:33, end_time=12:49` (the day's first period), the `AutoCloseStaleShiftsCommand` then closed them instantly with `clock_out_time=12:49`.

**Required pattern (in `getTodaysWorkRulesForUser`):**
```php
$now = $date
    ? Carbon::parse($date, $timezone)
    : Carbon::now($timezone);

// When a bare date is passed and it's today, substitute current time so
// getCurrentOrNextPeriodDetails() can match the active period.
if ($date && $now->isStartOfDay()) {
    $today = Carbon::now($timezone);
    if ($now->isSameDay($today)) {
        $now = $today;
    }
}
```
Historical queries (past dates) are unaffected because `isSameDay($today)` only triggers for today. Any new caller that needs the live "current period" must either pass no `$date` or ensure this normalization remains in place.

### INV-18: Re-clock-in lateness anchor must filter previous attendances by scheduled period
`HandleAttendanceLateness::buildCalculatorInput()` contains a "re-clock-in anchor" rule: if the user already has a prior attendance in the same scheduled period, use that earlier clock-in as the lateness anchor (so someone who briefly steps out and returns isn't double-penalised).

**The previous-row query must filter by `start_time` AND `end_time`** — matching by date alone picks up rows assigned to a *different* scheduled period (e.g. a morning shift's row), and if that earlier clock-in happens to fall inside the current period's window the anchor shifts onto unrelated data. Real-world symptom: clock-in at 18:32 against 17:40-22:48 period showed `late_minutes=14` (difference from the unrelated 18:18 clock-in on the 12:33-12:49 period) instead of the expected 52.

**Required pattern:**
```php
$previous = Attendance::where('user_id', $attendance->user_id)
    ->where('start_time', $attendance->start_time)   // same scheduled period
    ->where('end_time',   $attendance->end_time)
    ->where('id', '!=', $attendance->id)
    ->whereNotNull('clock_in_time')
    ->orderByDesc('clock_in_time')
    ->first();
```
The subsequent `between($scheduledStart, $scheduledEnd)` check is still required for safety but is no longer the primary filter.

### INV-19: Lateness grace lookup reads per-day rules from `weekly_schedule.{day}.lateness_rules`
Multi-period constraint configs store lateness rules per weekday:
```
constraint_config.time_rules.weekly_schedule.monday.lateness_rules
                                           .tuesday.lateness_rules
                                           ...
```
Reading `constraint_config.time_rules.lateness_rules` returns `[]` for these configs, so `gracePeriodMinutes` silently becomes 0 and every minute past `scheduledStart` counts as late. Only legacy single-schedule configs stored lateness rules at the `time_rules.*` root.

**Required pattern (in `HandleAttendanceLateness::resolveGraceMinutes()`):**
```php
$timeRules = $constraint->constraint_config['time_rules'] ?? [];

$rules = [];
$weeklySchedule = $timeRules['weekly_schedule'] ?? null;
if (is_array($weeklySchedule) && $attendance->start_time) {
    $timezone = $attendance->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';
    $dayName  = strtolower(CarbonImmutable::parse($attendance->start_time, $timezone)->format('l'));
    $rules    = $weeklySchedule[$dayName]['lateness_rules'] ?? [];
}
if (empty($rules)) {
    $rules = $timeRules['lateness_rules'] ?? [];   // legacy fallback
}
```
Day name must be derived from the attendance's `start_time` in the branch TZ — not `now()` — so a row clocked in just after midnight on a shift that extends from the previous day still reads the correct day's rules. Any other consumer that reads `lateness_rules` (e.g. snapshot-based readers in `AttendanceService::buildCalculatorInput()` and `ProcessClockInAttendanceData`) must follow the same resolution.

### INV-20: `additional_locations` in `user-constraint/today` — mirrors the location validation used at clock-in

#### What it is
`GET /api/v1/attendance/constraints/user` returns a `work_rules` object. Alongside `location_work` (the user's primary branch location from their main constraint), the response includes `additional_locations` — an **array** of extra allowed locations drawn from every active constraint in the user's `attendance_constraint_user` pivot table.

**Example response shape:**
```json
{
  "work_rules": {
    "location_work": {
      "name": "فرع مصر",
      "latitude": 30.059123,
      "longitude": 31.356976,
      "radius": 1000
    },
    "additional_locations": [
      {
        "name": "فرع الإسكندرية",
        "latitude": 31.200096,
        "longitude": 29.918739,
        "radius": 500
      },
      {
        "id": "loc-uuid-1",
        "name": "Riyadh Office",
        "latitude": 24.7136,
        "longitude": 46.6753,
        "radius": 200
      }
    ]
  }
}
```

`additional_locations` is always present (empty array `[]` when the user has no additional constraints).

#### Two location sources (updated 2026-05-14)

`additional_locations` is now populated from **two sources**:

| Source | How set | Identifier |
|---|---|---|
| `branch_locations` JSON column on `AttendanceConstraint` | Set during constraint create/update via API | No stable ID (index-based) |
| `attendance_constraint_locations` table rows | Created via `POST /api/v1/attendance/constraints/{id}/locations` | Stable UUID `id` field |

Both sources are merged in `AttendanceConstraintService::buildAdditionalLocationRules()`. Table-based locations include an `id` field; JSON-based locations do not.

#### How location validation works at clock-in
`AttendanceConstraintService::validateSingleConstraint()` calls `mergeAdditionalLocationsForUser()` **before** passing the constraint to `LocationConstraintService::validateLocationConstraint()`. This method:
1. Loads the user's `additionalAttendanceConstraints` (via the `attendance_constraint_user` pivot) **with** `additionalLocations` relation eager-loaded.
2. Collects all `branch_locations` JSON arrays from every active additional constraint.
3. Collects all `attendance_constraint_locations` rows from every active additional constraint, mapped to `{name, latitude, longitude, radius}`.
4. Merges both lists with the main constraint's `branch_locations` into a **cloned** constraint object.
5. Passes the clone to `validateMultiLocation()`.

The result: **clock-in passes if the user is within any location from the main constraint OR any additional constraint (either source)**. Time, shift, device, and all other rules are still evaluated only against the main constraint.

#### Managing additional locations (new CRUD)

Individual GPS locations attached to an additional constraint can be managed via:

```
POST   /api/v1/attendance/constraints/{id}/locations   → createLocations (bulk)
GET    /api/v1/attendance/constraints/{id}/locations   → getLocations
PUT    /api/v1/attendance/constraints/locations/{id}   → updateLocation (lat, lng, radius)
DELETE /api/v1/attendance/constraints/locations/{id}   → deleteLocation
```

These operate on `attendance_constraint_locations` rows and do NOT touch `branch_locations` JSON.

#### Pivot table
`attendance_constraint_user` (`attendance_constraint_id`, `user_id`, `created_at`, `updated_at`).
Managed via `User::additionalAttendanceConstraints()` (`BelongsToMany`).
API: `POST /api/v1/attendance/constraints/users/{userId}/additional` with `{ "constraint_ids": [...] }` performs a **full sync** (replaces the entire set for the user). Cache is bumped via `bumpApplicableConstraintsCacheForCompany()` after each change.

#### Postman collection
`Attendance_Constraint_Management_APIs.postman_collection.json` at the project root contains ready-to-import examples for all new endpoints.

#### Related documentation
`ATTENDANCE_CONSTRAINT_MANAGEMENT_APIS.md` at the project root contains the full API reference with request/response examples, field descriptions, and migration instructions for all new constraint management endpoints.

---

## 24. EmployeeTask Module Integration

> **Added:** 2026-05-18  
> **Module path:** `modules/EmployeeTask/`  
> **Full plan:** `EMPLOYEE_TASK_SYSTEM_PLAN.md`  
> **Status:** Phases 1–6 fully implemented (30 PHP files). Phase 7 (tests, Postman, dev DB migration) pending.

### 24.1 What Was Built

An **Employee Work Task Request (طلب مهمة عمل)** system implemented as a completely separate module. It adds parallel task-hour tracking alongside the existing attendance system without touching any Attendance table, presenter, or core service.

**Files created (30 total):**

| Directory | Files |
|---|---|
| `Controllers/` | `EmployeeTaskController.php`, `AdminEmployeeTaskController.php` |
| `Database/Migrations/` | `2026_05_20_000001_create_employee_task_requests_table.php`, `…_sessions_…`, `…_extension_requests_…` |
| `DTO/` | `CreateEmployeeTaskRequestDTO`, `StartTaskDTO`, `EndTaskDTO`, `CreateExtensionRequestDTO` |
| `Enums/` | `EmployeeTaskStatus.php`, `EmployeeTaskExtensionStatus.php` |
| `Exceptions/` | `EmployeeTaskException.php` |
| `Jobs/` | `AutoCloseTaskAtDurationExpiryJob.php`, `AutoCloseTaskIfOutOfLocationJob.php` |
| `Models/` | `EmployeeTaskRequest.php`, `EmployeeTaskSession.php`, `EmployeeTaskExtensionRequest.php` |
| `Presenters/` | `EmployeeTaskRequestPresenter.php`, `EmployeeTaskSessionPresenter.php`, `EmployeeTaskExtensionPresenter.php` |
| `Providers/` | `EmployeeTaskServiceProvider.php`, `EmployeeTaskRouteServiceProvider.php` |
| `Repositories/` | `EmployeeTaskRepository.php`, `EmployeeTaskSessionRepository.php` |
| `Requests/` | 7 form request classes |
| `Routes/` | `employee_tasks.php` (17 endpoints) |
| `Services/` | `EmployeeTaskRequestService`, `EmployeeTaskLifecycleService`, `EmployeeTaskLocationService`, `EmployeeTaskAutoCloseService`, `EmployeeTaskExtensionService`, `EmployeeTaskReportService` |
| `Support/` | `GeoDistance.php` (Haversine formula) |
| `Config/` | `permissions.php` |

**ProcedureSetting change:** Added `EmployeeTaskRequest = 'employee_task_request'` to `modules/ProcedureSetting/Enums/ProcedureSettingType.php`.

---

### 24.2 Architectural Boundary (What Was NOT Changed)

This is the most important invariant for anyone working in the Attendance module:

| Unchanged artifact | Why it was not touched |
|---|---|
| `attendances` table schema | Zero columns added. API payload byte-equivalent. |
| `AttendancePresenter` output | Not modified. Existing clients see no change. |
| `AttendanceCalculator` / policies | Pure domain logic unchanged. |
| `AttendanceConstraintService` core | Only **read** from it (radius snapshot). Never written to. |
| All clock-in / clock-out flows | Not touched. |
| `attendance_breaks` table | Not touched. |

The EmployeeTask module is a **read-only consumer** of the Attendance module. It reads constraint radius and uses `GeoDistance::metres()` (its own Haversine copy) — it does not call any Attendance write methods.

---

### 24.3 How Task Hours Integrate With Attendance Hours

Task hours and attendance hours are **parallel independent systems**. They are summed for the daily total:

```
total_work_hours  =  attendance_hours  +  task_hours
overtime_hours    =  max(0, total_work_hours − scheduled_hours)  [capped by max_over_time]
```

**Overlap assumption:** The system trusts that an employee cannot be at two GPS locations simultaneously, so task + attendance hours are never de-duplicated.

**Lateness:** Always calculated from attendance clock-in only. A task started before the shift does NOT reset the lateness clock.

**Intra-day report endpoint** (`GET /api/v1/employee-tasks/intra-day-report?date=YYYY-MM-DD`) returns a combined timeline of all attendance clock-in/clock-out pairs and all task sessions for a given day, enabling the mobile UI to show a unified workday view.

---

### 24.4 Radius Snapshot Pattern (mirrors Attendance convention)

At task **start** time (not request creation), the service calls:

```php
EmployeeTaskLocationService::snapshotRadiusFromConstraint(User $user): int
```

This reads the employee's main `AttendanceConstraint` radius and stores it in `employee_task_requests.radius_meters`. All subsequent location checks during that task use the snapshotted value — constraint changes mid-task do NOT affect an already-started task.

This mirrors the Attendance module's own snapshot approach (`applied_attendance_constraints.constraint_snapshot`).

---

### 24.5 New Tables

Three new tables (no changes to existing Attendance tables):

| Table | Purpose |
|---|---|
| `employee_task_requests` | One row per task request. Stores lifecycle state, GPS, hours, approval data. |
| `employee_task_sessions` | One row per continuous work period (start→pause, resume→end). Mirrors `attendance_breaks`. |
| `employee_task_extension_requests` | One row per duration-extension request submitted by the employee. |

All three use UUID primary keys (`char(36)`), include `company_id` for multi-tenancy, and store datetimes in **branch timezone** (same convention as `attendances` — NOT UTC).

---

### 24.6 Auto-Close Jobs

Two queued jobs handle automatic task termination:

**`AutoCloseTaskAtDurationExpiryJob`**
- Dispatched at task `start()` with `delay = (duration_hours + max_over_time_hours) * 3600` seconds.
- On fire: calls `EmployeeTaskAutoCloseService::closeIfExpired()` with `source = 'auto_duration'`.
- Re-dispatched (with updated deadline) when an extension is approved.
- Idempotent: `closeIfExpired()` acquires a row lock and checks task is still `in_progress` before acting.

**`AutoCloseTaskIfOutOfLocationJob`**
- Dispatched by `EmployeeTaskLocationService::processLocationPing()` when an employee is detected outside the task radius.
- Delay = constraint's `out_of_location_threshold_minutes`.
- On fire: verifies employee is still out-of-radius, then calls `closeIfExpired()` with `source = 'auto_location'`.
- Cancelled automatically (idempotency) if employee returns to radius before the delay expires.

**Safety invariant (INV-T3):** Both jobs use `SELECT … FOR UPDATE` row lock + status re-check inside `closeIfExpired()` to prevent race conditions between concurrent jobs.

---

### 24.7 Status State Machine

**Task request statuses:** `pending → approved → in_progress → paused → completed`  
with branches to `rejected` and `cancelled`.

**Extension badge** (`last_extension_status` column on `employee_task_requests`):  
`null | extension_pending | extension_approved | extension_rejected`  
Always returned in API responses alongside the main `status` field.

Full transition table and Arabic labels are documented in `EMPLOYEE_TASK_SYSTEM_PLAN.md §3`.

---

### 24.8 API Endpoints (17 total)

**Employee-facing (`EmployeeTaskController`):**

| Method | URI | Action |
|---|---|---|
| `POST` | `/api/v1/employee-tasks` | Create task request |
| `GET` | `/api/v1/employee-tasks` | List own tasks |
| `GET` | `/api/v1/employee-tasks/{id}` | Get task detail |
| `PATCH` | `/api/v1/employee-tasks/{id}/cancel` | Cancel own request |
| `POST` | `/api/v1/employee-tasks/{id}/start` | Start task |
| `POST` | `/api/v1/employee-tasks/{id}/pause` | Pause task |
| `POST` | `/api/v1/employee-tasks/{id}/resume` | Resume task |
| `POST` | `/api/v1/employee-tasks/{id}/end` | End task |
| `GET` | `/api/v1/employee-tasks/{id}/live-status` | Live timer data |
| `POST` | `/api/v1/employee-tasks/{id}/location-ping` | GPS location update |
| `GET` | `/api/v1/employee-tasks/{id}/location-check` | Check if in radius |
| `POST` | `/api/v1/employee-tasks/{id}/extension-requests` | Submit extension request |
| `GET` | `/api/v1/employee-tasks/{id}/extension-requests` | List extension requests |
| `GET` | `/api/v1/employee-tasks/intra-day-report` | Intra-day combined timeline |

**Admin-facing (`AdminEmployeeTaskController`):**

| Method | URI | Action |
|---|---|---|
| `GET` | `/api/v1/admin/employee-tasks` | List all company tasks |
| `PATCH` | `/api/v1/admin/employee-tasks/{id}/approve` | Approve task |
| `PATCH` | `/api/v1/admin/employee-tasks/{id}/reject` | Reject task |
| `PATCH` | `/api/v1/admin/employee-tasks/{id}/cancel` | Force-cancel task |
| `PATCH` | `/api/v1/admin/employee-tasks/{id}/extension-requests/{extId}/approve` | Approve extension |
| `PATCH` | `/api/v1/admin/employee-tasks/{id}/extension-requests/{extId}/reject` | Reject extension |

---

### 24.9 Key Invariants (EmployeeTask-specific)

These complement the existing INV-* series. Reference them when modifying `modules/EmployeeTask/`:

| ID | Invariant |
|---|---|
| **INV-T1** | All `time_from` / `time_to` / session `start_time` / `end_time` are stored in branch timezone (use `getTimeZoneBranchByRequest()`). Never store UTC. |
| **INV-T2** | Always use `->toIso8601String()` when passing datetimes through job constructors. Never `->format('Y-m-d H:i:s')` (same as INV-15 for Attendance). |
| **INV-T3** | `EmployeeTaskAutoCloseService::closeIfExpired()` MUST acquire a row lock (`lockForUpdate()`) and re-read status before any write to prevent auto-close race conditions. |
| **INV-T4** | `radius_meters` is snapshotted from the constraint at `start()` time, not at request creation. Location checks always use the snapshotted value. |
| **INV-T5** | Auto-close jobs set `time_to = boundary_time` (the computed expiry moment), not `Carbon::now()`. This ensures `total_task_hours` is accurate even if the job fires late. |
| **INV-T6** | `AttendancePresenter` output is never modified by the EmployeeTask module. Task data is only added via the separate intra-day report endpoint. |
| **INV-T7** | A task in `in_progress` status must have exactly one `employee_task_sessions` row with `end_time IS NULL`. Enforced by `EmployeeTaskLifecycleService` on every state transition. |
| **INV-T8** | `original_duration_hours` is set only on the first extension approval (preserves the original approved duration for audit). Subsequent extensions only update `duration_hours`. |
| **INV-T9** | A new extension request can only be submitted if no other extension request is in `pending` status for that task. |
| **INV-T10** | Extension approval re-dispatches `AutoCloseTaskAtDurationExpiryJob` with the updated deadline. Old job fires harmlessly — `closeIfExpired()` returns false if task is not `in_progress`. |

---

### 24.10 Permissions

Defined in `modules/EmployeeTask/Config/permissions.php`:

```
EMPLOYEE_TASK_CREATE          → employee-task.employee-tasks.create
EMPLOYEE_TASK_VIEW            → employee-task.employee-tasks.view
EMPLOYEE_TASK_LIST            → employee-task.employee-tasks.list
EMPLOYEE_TASK_CANCEL          → employee-task.employee-tasks.cancel
EMPLOYEE_TASK_START           → employee-task.employee-tasks.start
EMPLOYEE_TASK_END             → employee-task.employee-tasks.end
EMPLOYEE_TASK_APPROVE         → employee-task.employee-tasks.approve
EMPLOYEE_TASK_REJECT          → employee-task.employee-tasks.reject
EMPLOYEE_TASK_ADMIN_LIST      → employee-task.employee-tasks.admin-list
EMPLOYEE_TASK_ADMIN_CANCEL    → employee-task.employee-tasks.admin-cancel
EMPLOYEE_TASK_EXTENSION_CREATE  → employee-task.extensions.create
EMPLOYEE_TASK_EXTENSION_APPROVE → employee-task.extensions.approve
EMPLOYEE_TASK_REPORT_VIEW     → employee-task.reports.view
```

---

### 24.11 Procedure & Condition Integration

**Added:** 2026-06-18

The Procedure Workflow system can gate task lifecycle actions on attendance-derived conditions. This section documents which Attendance module services are consumed and how.

#### 24.11.1 Overview

`EmployeeTaskFormConditionService` (in `modules/EmployeeTask/Services/`) is the bridge between the Procedure Workflow system and the Attendance module. It resolves the child `ProcedureSetting` for a given form, reads its `conditions` JSON, and evaluates them against real-time attendance data.

```
EmployeeTaskRequestService::create()
        │
        ▼
EmployeeTaskFormConditionService::checkCreateTaskConditions()
        │
        ├── ProcedureWorkflowService::resolveInternalProcedureSettingByForm()
        │         returns child ProcedureSetting (or null → skip)
        │
        ├── AttendanceConstraintService::getTodaysWorkRulesForUser(User)
        │         returns: is_holiday, current_work_period, all_work_periods
        │
        └── evaluates: allow_during_shift / allow_outside_shift / allow_on_holidays
                  throws EmployeeTaskException (422) on violation


EmployeeTaskLifecycleService::end()
        │
        ▼
EmployeeTaskFormConditionService::checkEndTaskConditions()
        │
        ├── ProcedureWorkflowService::resolveInternalProcedureSettingByForm()
        │
        └── EmployeeTaskLocationService::isWithinTaskRadius($task, $lat, $lng)
                  evaluates: can_exit_outside_location
                  throws EmployeeTaskException (422) on violation
```

#### 24.11.2 AttendanceConstraintService Consumption

**Method used:** `AttendanceConstraintService::getTodaysWorkRulesForUser(User $user): array`

The condition service loads the `User` model with the following relations to support constraint resolution:
- `professionalData.attendanceConstraint` — direct personal constraint (highest priority)
- `userProfessionalData.branch` — used to find branch-default constraint
- `userProfessionalData.department` — used in broad constraint lookup fallback

**Return values consumed:**

| Key | Type | Used for |
|-----|------|---------|
| `is_holiday` | `bool` | `AllowOnHolidays` condition check. `true` when `day_status` is `holiday` or `day_off_or_weekend`, or when no time constraint is assigned. |
| `current_work_period` | `array\|null` | `AllowDuringShift` / `AllowOutsideShift` check. Non-null = employee is inside a scheduled work period right now. |

**No attendance constraint assigned:** `getTodaysWorkRulesForUser` returns `is_holiday = false` and an empty `all_work_periods` / null `current_work_period`. The condition service treats this as "outside shift, not holiday" — only the `AllowOutsideShift` condition applies.

#### 24.11.3 EmployeeTaskLocationService Consumption

**Method used:** `EmployeeTaskLocationService::isWithinTaskRadius(EmployeeTaskRequest $task, float $lat, float $lng): bool`

Uses `GeoDistance::haversine()` to compare the employee's GPS coordinates against the task's location anchor and `task.radius_meters` (snapshotted from the attendance constraint at task start — see INV-T4).

Returns `true` if within radius, `false` if outside. The `can_exit_outside_location = false` condition blocks the end action when the employee is outside radius.

#### 24.11.4 Condition Defaults (from InternalProcedureSettingsSeeder)

| Form | Condition | Default |
|------|-----------|---------|
| `createTask` | `allow_during_shift` | `true` |
| `createTask` | `allow_outside_shift` | `false` |
| `createTask` | `allow_on_holidays` | `false` |
| `endTask` | `can_exit_outside_location` | `true` |

Admins can override these defaults via the internal procedure settings API.

---

#### INV-T11 — Condition Check Must Fire Before Workflow Start

`EmployeeTaskFormConditionService::check*Conditions()` **must always be called before** `WorkflowEngine::previewResponsibles()` or `ProcedureWorkflowService::resolveInternalProcedureSettingByForm()` initiates any workflow work. Violating this order would allow a condition-blocked action to create orphan workflow/process records.

Current call sites enforce this ordering:
- `EmployeeTaskRequestService::create()` — condition check is the first statement after `$branchId` resolution.
- `EmployeeTaskLifecycleService::end()` — condition check fires after status validation but before `resolveEndTaskProcedure()`.
