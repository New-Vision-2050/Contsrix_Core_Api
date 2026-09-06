# Attendance Module Deep Reference

Last reviewed: 2026-08-25

This document is the module-local technical reference for `modules/Attendance`.
It is written for backend developers, reviewers, and AI assistants who need to
change the Attendance module without rediscovering the architecture from
scratch. It focuses on the current implementation, important invariants, data
flows, extension points, and places where a small edit can accidentally change
business behavior.

The root repository also contains attendance documents and API collections. This
file is intentionally placed inside the module so that the reference travels with
the code it describes.

## Table Of Contents

1. Module responsibility
2. Directory map
3. Runtime registration
4. HTTP route surface
5. Database model
6. Core models
7. DTO and request boundary
8. Domain layer
9. Application services
10. Constraint system
11. Clock-in flow
12. Clock-out flow
13. Break flow
14. Auto attendance and scheduled jobs
15. User-facing mobile APIs
16. Location tracking and radius enforcement
17. Leave request subsystem
18. Presenters and response shaping
19. Permissions
20. Timezone and date strategy
21. Concurrency and locking strategy
22. Cache strategy
23. Testing map
24. Extension guide
25. High-risk invariants
26. Troubleshooting checklist

## 1. Module Responsibility

The Attendance module owns employee attendance lifecycle behavior:

- Clock in and clock out.
- Multiple work periods per day.
- Early clock-in rules, lateness rules, overtime caps, early departure flags.
- Manual and automatic breaks.
- Waiting, active, completed, absent, and holiday attendance rows.
- Applied constraint snapshots for auditability.
- Constraint definitions, assignment, validation, violations, locations, shifts,
  and rules.
- Mobile user APIs for today's constraint, clock-in status, and history.
- Location tracking, geofencing, and radius based shift ending.
- Leave requests, leave balances, and leave request approval flows.
- Scheduled creation of waiting rows and holiday rows.

The module depends on the user, company, branch/management hierarchy, permission,
tenancy, queue, audit, and export infrastructure already present in the app.

The module should not be treated as a simple CRUD module. Most write paths are
stateful workflows with audit, schedule, branch timezone, constraint snapshot,
and concurrency assumptions.

## 2. Directory Map

```text
modules/Attendance/
  Config/
    config.php
    permissions.php
  Contracts/
    *ConstraintServiceInterface.php
  Controllers/
    AttendanceController.php
    AttendanceConstraintController.php
    LocationTrackingController.php
    ManagementHierarchyController.php
    UserAttendanceController.php
    LeaveRequestController.php
  Database/migrations/
    attendance, constraint, leave, break, task, location migrations
  DataClasses/
    BranchLocation.php
    DaySchedule.php
    LocationTrackingCollection.php
    LocationTrackingPoint.php
    MultiplePeriodsConfig.php
    Period.php
    TemporaryLocationException.php
    TimePeriod.php
    WeeklySchedule.php
  Domain/
    Breaks/
    Calculator/
    Time/
  DTO/
    request-to-service transfer objects
  Events/
    AttendanceClockedIn.php
    AttendanceConstraintUpdated.php
    UpdateAttendance.php
  Exceptions/
    AttendanceException.php
    LeaveRequestException.php
  Exports/
    AttendanceTeamExport.php
  Filters/
    AttendanceFilter.php
    AttendanceConstraintFilter.php
    AttendanceConstraintViolationFilter.php
  Jobs/
    AutoCloseAttendanceJob.php
    AutoClockOutAtNextShiftStartJob.php
    ProcessClockInAttendanceData.php
  Listeners/
    HandleAttendanceLateness.php
    HandelAttendanceConstraintUpdate.php
    LogAttendanceConstraintUpdate.php
  Models/
    Attendance.php
    AttendanceBreak.php
    AttendanceConstraint.php
    AttendanceConstraintLocation.php
    AttendanceConstraintViolation.php
    AttendanceTask.php
    AppliedAttendanceConstraint.php
    LeaveBalance.php
    LeaveRequest.php
    LeaveType.php
  Observers/
    AttendanceConstraintObserver.php
  Presenters/
    response serializers
  Providers/
    AttendanceServiceProvider.php
    ConstraintServiceProvider.php
    RouteServiceProvider.php
  Repositories/
    query and persistence wrappers
  Requests/
    form request validation
  Resources/routes/
    api.php
  Routes/
    attendance_constraints.php
    management_hierarchy.php
  Services/
    clock, attendance, constraint, location, leave support services
  Support/
    HoursFormatter.php
  Tests/
    module-local unit and feature tests
```

## 3. Runtime Registration

`module.json` registers `Modules\Attendance\Providers\AttendanceServiceProvider`.
The module declares dependencies on `User`, `Company`, and `RoleAndPermission`.

### AttendanceServiceProvider

Main boot responsibilities:

- Registers translations, config, views, schedules, event listeners.
- Loads migrations from `modules/Attendance/Database/migrations`.
- Registers `RouteServiceProvider`.
- Registers `ConstraintServiceProvider`.
- Binds domain layer singletons:
  - `Clock` -> `SystemClock`
  - `TimezoneResolver`
  - `AutoBreakComputer`
  - `LatenessPolicy` -> `StandardLatenessPolicy`
  - `OvertimePolicy` -> `StandardOvertimePolicy`
  - `EarlyDeparturePolicy` -> `StandardEarlyDeparturePolicy`
  - `AttendanceCalculator`
- Binds application singleton services:
  - `AutoCloseAttendanceService`
  - `ClockInService`
  - `ClockOutService`

Scheduled commands registered by the provider:

- `attendance:create-waiting` every six hours.

The provider is not the only scheduler. `routes/console.php` schedules attendance
commands as well, all in `Asia/Riyadh` with overlap protection:

- `CreateWaitingAttendanceCommand` every three hours.
- `attendance:auto-close-stale-shifts` every five minutes.
- `attendance:auto-close-stale-location` every five minutes.
- `attendance:mark-missed-clock-ins-absent` every five minutes.
- `attendance:send-silent-notifications` every five minutes.

Note that `attendance:create-waiting` is therefore registered twice, in the
provider and in `routes/console.php`, so it runs on both cadences. Anything that
command writes must be safe to re-run repeatedly during a working day, after
employees have already clocked in. Check both places before changing its cadence.

Event listener registered here:

- `AttendanceClockedIn` -> `HandleAttendanceLateness`

### ConstraintServiceProvider

Registers the specialized constraint service interfaces and concrete classes:

- `TimeConstraintServiceInterface` -> `TimeConstraintService`
- `LocationConstraintServiceInterface` -> `LocationConstraintService`
- `DeviceConstraintServiceInterface` -> `DeviceConstraintService`
- `RoleConstraintServiceInterface` -> `RoleConstraintService`
- `BehavioralConstraintServiceInterface` -> `BehavioralConstraintService`
- `SecurityConstraintServiceInterface` -> `SecurityConstraintService`
- `ComplianceConstraintServiceInterface` -> `ComplianceConstraintService`

It also registers the concrete constraint services and
`AttendanceConstraintService` as singletons.

Important: these singleton services must remain stateless. Do not store request,
tenant, user, attendance, or constraint data in service instance properties.
Octane can keep singleton instances alive across requests.

Observer and listeners registered here:

- `AttendanceConstraint::observe(AttendanceConstraintObserver::class)`
- `AttendanceConstraintUpdated` -> `LogAttendanceConstraintUpdate`
- `UpdateAttendance` -> `HandelAttendanceConstraintUpdate`

### RouteServiceProvider

Every route group is mounted under `api/v1` with `api`, `auth:api`, and tenancy
request initialization middleware:

- `Resources/routes/api.php`
- `Routes/attendance_constraints.php`
- `Routes/management_hierarchy.php`

Some route files also apply their own `auth:api`, `tenant`, or permission
middleware. When changing middleware, check both provider-level and file-level
middleware to avoid duplicate assumptions.

## 4. HTTP Route Surface

All paths below are relative to `/api/v1`.

### Attendance routes

Route file: `Resources/routes/api.php`

Prefix: `/attendance`

Core employee actions:

- `POST /attendance/clock-in` -> `AttendanceController::clockIn`
- `POST /attendance/clock-out` -> `AttendanceController::clockOut`
- `POST /attendance/start-break` -> `AttendanceController::startBreak`
- `POST /attendance/end-break` -> `AttendanceController::endBreak`
- `GET /attendance/current-status` -> `AttendanceController::getCurrentStatus`
- `GET /attendance/status` -> `AttendanceController::getStatus`
- `GET /attendance/history` -> `AttendanceController::getHistory`
- `GET /attendance/summary` -> `AttendanceController::getSummary`

Mobile user routes:

- `GET /attendance/user-constraint/today`
  -> `UserAttendanceController::getMyConstraintForToday`
- `GET /attendance/user-attendance/status`
  -> `UserAttendanceController::getMyClockInStatus`
- `GET /attendance/user-attendance/history`
  -> `UserAttendanceController::getUserAttendanceHistory`

Location tracking:

- `GET /attendance/live-tracking`
  -> `LocationTrackingController::getLiveTrackingData`
- `POST /attendance/track-location`
  -> `LocationTrackingController::store`

Team and admin routes:

- `GET /attendance/open` -> `AttendanceController::getOpenAttendances`
- `GET /attendance/team` -> `AttendanceController::getTeamAttendance`
- `GET /attendance/team/user` -> `AttendanceController::getUserAttendance`
- `GET /attendance/{attendance}/team` -> `AttendanceController::teamAttendance`
- `GET /attendance/{attendance}/applied-attendance`
  -> `AttendanceController::appliedAttendanceConstraint`
- `PUT /attendance/{attendanceId}` -> `AttendanceController::update`
- `POST /attendance/{attendanceId}/approve` -> `AttendanceController::approve`
- `POST /attendance/{attendanceId}/reject` -> `AttendanceController::reject`
- `DELETE /attendance/{attendanceId}` -> `AttendanceController::destroy`
- `POST /attendance/team/export`
  -> `AttendanceController::exportTeamAttendance`

### Constraint routes

Route file: `Routes/attendance_constraints.php`

Prefix: `/attendance/constraints`

Constraint CRUD and listing:

- `GET /attendance/constraints`
- `GET /attendance/constraints/list`
- `POST /attendance/constraints`
- `GET /attendance/constraints/{constraint}`
- `PUT /attendance/constraints/{constraint}`
- `DELETE /attendance/constraints/{constraint}`

Types, validation, statistics, violations:

- `GET /attendance/constraints/types`
- `POST /attendance/constraints/validate`
- `GET /attendance/constraints/violations`
- `PUT /attendance/constraints/violations/{violation}/resolve`
- `PUT /attendance/constraints/violations/{violation}/dismiss`
- `GET /attendance/constraints/statistics`
- `GET /attendance/constraints/violations/summary`

Branch routes:

- `GET /attendance/constraints/branches/{branchId}`
- `POST /attendance/constraints/branches/{branchId}/bulk-assign`
- `GET /attendance/constraints/branches/{branchId}/inherited`

Bulk operations:

- `POST /attendance/constraints/bulk/activate`
- `POST /attendance/constraints/bulk/deactivate`
- `POST /attendance/constraints/bulk/delete`

Per-user assignments and locations:

- `GET /attendance/constraints/employees/{userId}/constraint-locations`
- `PUT /attendance/constraints/employees/{userId}/assign-constraint`
- `GET /attendance/constraints/users/{userId}/additional`
- `POST /attendance/constraints/users/{userId}/additional`
- `DELETE /attendance/constraints/users/{userId}/additional/{constraintId}`

Constraint employees:

- `GET /attendance/constraints/{constraint}/employees`
- `POST /attendance/constraints/{constraint}/employees`

Constraint locations:

- `GET /attendance/constraints/{constraint}/locations`
- `POST /attendance/constraints/{constraint}/locations`
- `PUT /attendance/constraints/locations/{location}`
- `DELETE /attendance/constraints/locations/{location}`

Constraint shifts and rules:

- `GET /attendance/constraints/{constraint}/day-shifts`
- `GET /attendance/constraints/{constraint}/shifts`
- `POST /attendance/constraints/{constraint}/shifts`
- `PATCH /attendance/constraints/{constraint}/rules`
- `PATCH /attendance/constraints/{constraint}/basic-info`

### Hierarchy routes

Route file: `Routes/management_hierarchy.php`

Prefix: `/attendance/hierarchy`

- `GET /attendance/hierarchy/branches`
- `GET /attendance/hierarchy/branches/{branchId}`
- `GET /attendance/hierarchy/branches/{branchId}/children`
- `GET /attendance/hierarchy/branches/{branchId}/parents`
- `GET /attendance/hierarchy/users/{userId}/branch`
- `GET /attendance/hierarchy/branches/{branchId}/users`

## 5. Database Model

The schema is spread across migrations from 2025 and 2026. Some later migrations
change earlier design decisions, so always inspect the final model casts and the
latest migrations before assuming an original migration still describes runtime
shape perfectly.

### `attendances`

Primary lifecycle table. One row represents a scheduled period occurrence or a
synthetic waiting/absence/holiday row.

Important columns:

- `id`: UUID primary key.
- `user_id`: user owning the row.
- `company_id`: tenant/company boundary.
- `clock_in_time`: nullable after waiting-row support.
- `clock_out_time`: nullable until completion.
- `start_time`, `end_time`: scheduled period bounds.
- `date`: legacy/date column added by older migration.
- `business_date`: canonical branch-calendar date for reporting/indexing.
- `day_status`: work-day classification/status.
- `shift_end_method`: how shift was ended, such as manual or auto-close.
- `total_work_hours`: net worked hours as decimal hours.
- `total_break_hours`: break hours as decimal hours.
- `overtime_hours`: overtime decimal hours.
- `max_over_time`: snapshot from the applicable constraint at clock-in.
- `is_late`, `late_minutes`.
- `is_early_departure`, `early_departure_minutes`.
- `is_absent`, `is_holiday`.
- `status`: string constrained in MySQL to known states.
- `clock_in_location`, `clock_out_location`: JSON.
- `verification_data`, `location_tracking`: JSON.
- `timezone`: IANA timezone frozen for the row.
- `approved_by`, `approved_at`.

Known statuses in `Attendance`:

- `waiting`
- `active`
- `completed`
- `absent`
- `holiday`
- `pending_approval`
- `approved`
- `rejected`

Current MySQL check constraint allows:

- `waiting`
- `active`
- `completed`
- `pending_approval`
- `approved`
- `rejected`
- `absent`
- `holiday`

Important indexes:

- user/date and company/date indexes on `business_date`.
- company/status/start-time index.
- company/late/start-time index.
- company/user/start-time index.

### `attendance_breaks`

Break rows are the current break source of truth. Legacy break columns were
dropped from `attendances`.

Typical columns:

- `attendance_id`
- `start_time`
- `end_time`
- `duration_minutes`
- `notes`
- `source`, defaulting to `auto_gap` in later migration.

### `attendance_constraints`

Defines the rules used by clock-in/out validation and mobile "today" APIs.

Important columns:

- `id`: UUID primary key.
- `company_id`.
- `department_ids`: JSON.
- `branch_ids`: JSON.
- `branch_locations`: JSON branch keyed locations.
- `constraint_type`.
- `constraint_name`.
- `constraint_config`: JSON rules payload.
- `max_over_time`: decimal/integer-ish overtime cap, interpreted as hours.
- `is_active`.
- `inherit_from_parent`.
- `priority`.
- `start_date`, `end_date`.
- `created_by`, `updated_by`.
- `notes`.
- soft deletes.

Earlier migrations had `user_id` and `department_id`. Later migrations moved
assignment toward JSON and pivot models. Some old scopes still reference
`user_id`; treat those scopes carefully before using them in new code.

### `attendance_constraint_user`

Pivot table connecting users to constraints:

- `attendance_constraint_id`
- `user_id`
- composite primary key.

This is central for direct user assignment and additional constraint assignment.

### `attendance_constraint_locations`

Table-based additional locations attached to a constraint:

- `id`: UUID primary key.
- `attendance_constraint_id`.
- `company_id`.
- `name`.
- `latitude`.
- `longitude`.
- `radius`.
- `created_by`.
- timestamps.

These locations supplement the older `branch_locations` JSON model. Location
responses and validation must merge both sources when applicable.

### `applied_attendance_constraints`

Stores the constraint snapshot used for an attendance row:

- auto-incrementing integer id.
- `attendance_id`.
- `company_id`.
- `constraint_snapshot`: JSON.

This table supports audit and repeatability. Do not recompute historical applied
rules from the current constraint row; use the snapshot when looking at past
attendance behavior.

### Leave tables

Leave subsystem tables include:

- `leave_types`
- `leave_requests`
- `leave_balances`

They support translatable leave type names/descriptions, approval state,
emergency flag, attachments, accrual, carry-over, and balance tracking.

## 6. Core Models

### Attendance

Path: `Models/Attendance.php`

Key traits and behavior:

- `UuidTrait`
- `BaseFilterable`
- `OwenIt\Auditing\Auditable`
- `CustomBelongsToTenant`
- Soft deletes are imported but currently commented out.

Important relationships:

- `user()`
- `company()`
- `approver()` / `approvedBy()`
- `breaks()`
- `appliedAttendanceConstraint()`
- `professionalData()`
- `attendanceConstraint()`

Important methods:

- `validateStatusTransition(string $newStatus)`
- `activeBreak()`
- `completedBreaks()`
- `isOnBreak()`
- `calculateTotalBreakMinutes()`
- `calculateTotalBreakHours()`
- `updateTotalBreakHours()`
- `validateTimes()`
- `validateLocation()`
- `validateIp()`
- `validateUserAgent()`
- query scopes for date range, user, company, active, completed.
- state helpers: `isActive`, `isCompleted`, `isPendingApproval`,
  `isApproved`, `isRejected`.
- `getFormattedWorkDuration()`.

Critical model invariant:

`clock_in_time`, `clock_out_time`, `start_time`, and `end_time` are intentionally
not cast as `datetime`. They are stored as branch-local datetime strings. Casting
them as Laravel datetimes would make Laravel interpret them as UTC and shift
them incorrectly.

### AttendanceConstraint

Path: `Models/AttendanceConstraint.php`

Key traits:

- `UuidTrait`
- `BaseFilterable`
- `SoftDeletes`
- `OwenIt\Auditing\Auditable`
- `CustomBelongsToTenant`

Important relationships:

- `company()`
- `users()`
- `creator()`
- `updater()`
- `additionalLocations()`
- `managementHierarchies()`

Important behavior:

- `appliesToBranch(string $branchId)`
- `addBranch(string $branchId)`
- `removeBranch(string $branchId)`
- `setBranchLocation(string $branchId, array $location)`
- `getBranchLocation(string $branchId)`
- `removeBranchLocation(string $branchId)`
- `getAllBranchLocations()`
- `setBranchLocations(array $branchLocations)`
- `hasBranchLocation(string $branchId)`
- `isValidForDate($date = null)`
- `getConstraintTypes()`
- `getConstraintArrayTypes()`
- `getConstraintNamesByType(string $type)`

Important constants:

- Types: `location`, `time`, `device`, `role`, `behavioral`, `security`,
  `compliance`, and app-specific `regular`.
- Location names include geofencing, IP restriction, office verification,
  remote zones, multi-location, radius enforcement.
- Time names include shift enforcement, early prevention, late restriction,
  break limits, overtime approval, multiple periods, late clock out, break time
  limits.
- Device, role, behavioral, security, and compliance constants are defined for
  specialized validators.

### AttendanceConstraintLocation

Path: `Models/AttendanceConstraintLocation.php`

Table-backed extra locations for constraints. Relationships:

- `constraint()`
- `creator()`

### AppliedAttendanceConstraint

Path: `Models/AppliedAttendanceConstraint.php`

Stores the snapshot JSON of constraints used by a specific attendance row.
Its primary key is not a UUID in the current implementation.

### AttendanceBreak

Represents break segments tied to attendance. Current code should use this
relation rather than legacy columns on `attendances`.

### AttendanceConstraintViolation

Violation records are created by constraint validation and managed through the
constraint violation routes. They carry status, severity/details, resolution,
dismissal, and ownership metadata.

## 7. DTO And Request Boundary

Requests validate HTTP inputs. DTOs carry validated values into services.

Important DTOs:

- `ClockInDTO`
- `ClockOutDTO`
- `FilterAttendanceDTO`
- `CreateAttendanceConstraintDTO`
- `UpdateAttendanceConstraintDTO`
- `ValidateAttendanceDTO`
- `BulkConstraintIdsDTO`
- `FilterViolationDTO`
- `ResolveViolationDTO`
- `DismissViolationDTO`
- `CreateLeaveRequestDTO`
- `UpdateLeaveRequestDTO`
- `ApproveLeaveRequestDTO`
- `RejectLeaveRequestDTO`
- `LeaveBalanceDTO`
- `LeaveCalendarDTO`
- `LeaveConflictCheckDTO`
- `MyLeaveRequestsFilterDTO`
- `PendingApprovalsFilterDTO`

Important request classes:

- Attendance: `ClockInRequest`, `ClockOutRequest`, `BreakRequest`,
  `FilterAttendanceRequest`, `GetAttendanceRequest`, `UpdateAttendanceRequest`,
  `ExportAttendanceRequest`.
- Constraints: `CreateAttendanceConstraintRequest`,
  `UpdateAttendanceConstraintRequest`, `FilterConstraintsRequest`,
  `BulkConstraintRequest`, `ValidateAttendanceRequest`, `GetViolationsRequest`,
  `ResolveViolationRequest`, `DismissViolationRequest`,
  `GetStatisticsRequest`, `GetUserConstraintRequest`.
- User/mobile: `GetUserAttendanceHistoryRequest`, `LiveTrackingRequest`,
  `TrackLocationRequest`.
- Leave: `CreateLeaveRequestRequest`, `UpdateLeaveRequestRequest`,
  `GetLeaveRequestsRequest`, `ApproveLeaveRequestRequest`,
  `RejectLeaveRequestRequest`, `MyLeaveRequestsRequest`,
  `PendingApprovalsRequest`, `LeaveCalendarRequest`,
  `LeaveConflictCheckRequest`, `LeaveBalanceRequest`.

`Requests/Traits/HasConstraintConfigValidation.php` is a key validation helper
for nested constraint payloads. When changing weekly schedule or multiple period
shape, update request validation and data-class validation together.

## 8. Domain Layer

The `Domain` folder contains pure logic. It should not depend on request,
database, facades, auth, queues, or Eloquent state.

### Calculator

Main classes:

- `AttendanceCalculator`
- `CalculatorInput`
- `WorkHoursResult`
- `LatenessPolicy`
- `StandardLatenessPolicy`
- `OvertimePolicy`
- `StandardOvertimePolicy`
- `EarlyDeparturePolicy`
- `StandardEarlyDeparturePolicy`

`AttendanceCalculator::calculate()` receives a `CalculatorInput` and returns
`WorkHoursResult`.

The calculator evaluates:

- gross worked minutes from clock-in to clock-out.
- net worked minutes after breaks.
- lateness and late minutes.
- early departure and early departure minutes.
- overtime capped by policy/max overtime.

The calculator should remain deterministic. Inject the data it needs instead of
reaching out to global current time inside the calculator.

### Breaks

Main classes:

- `BreakSegment`
- `AutoBreakComputer`

`AutoBreakComputer` calculates implicit gap breaks, especially for re-clock-in or
period continuity scenarios. Generated breaks are persisted outside the domain
layer by application services.

### Time

Main classes:

- `Clock`
- `SystemClock`
- `FixedClock`
- `TimezoneResolver`

`FixedClock` exists for tests and deterministic behavior. `TimezoneResolver`
centralizes attendance/user/request timezone decisions.

## 9. Application Services

### ClockInService

Path: `Services/ClockInService.php`

Small use-case wrapper around:

- pre-clock-in validation via `AttendanceConstraintService`.
- persistence via `AttendanceService::clockIn`.

Use this service for the controller-facing clock-in action. Avoid bypassing it
unless the caller is deliberately creating synthetic rows.

### ClockOutService

Path: `Services/ClockOutService.php`

Small use-case wrapper around `AttendanceService::clockOut`.

### AttendanceService

Path: `Services/AttendanceService.php`

The main attendance application service.

Core write methods:

- `clockIn(ClockInDTO $clockInDTO): Attendance`
- `clockOut(ClockOutDTO $clockOutDTO): Attendance`
- `startBreak(UuidInterface|string $userId, ?string $notes = null): Attendance`
- `endBreak(UuidInterface|string $userId, ?string $notes = null): Attendance`
- `updateAttendance(string $attendanceId, array $data): Attendance`
- `approveAttendance(...)`
- `rejectAttendance(...)`
- `deleteAttendance(string $attendanceId): bool`
- `endShiftAutomatically(...)`
- `createAbsenceRecord(...)`
- `createWaitingRecord(...)`
- `updateAttendanceStatus(...)`

Core read methods:

- `getCurrentAttendance(...)`
- `getAttendance(...)`
- `getAttendanceHistory(...)`
- `getAttendanceList(...)`
- `getAttendanceSummary(...)`
- `getAttendanceForExport(...)`
- `getTeamAttendance(...)`
- `getOpenAttendances(...)`
- `getLateArrivals(...)`
- `getEarlyDepartures(...)`
- `getOvertimeRecords(...)`
- `getBreaks(...)`
- `getAttendanceForUserOnDate(...)`
- `getPresentUserIdsOnDate(...)`
- `getWaitingUserIdsOnDate(...)`

Private workflow helpers include:

- active-clock-in guard.
- work period bounds resolution.
- early clock-in rule enforcement.
- clock-in data construction.
- applied constraint persistence.
- auto-close scheduling.
- next-shift auto-clock-out scheduling.
- calculator input construction.
- clock-out payload construction.
- export query construction.
- work-hour recalculation.
- attendance period aggregation.

### AttendanceConstraintService

Path: `Services/AttendanceConstraintService.php`

The central constraint orchestrator.

Important methods:

- `validateAttendance(Attendance $attendance, array $requestData = [], bool $isDryRun = false): array`
- `getEffectiveConstraintForUser(User $user)`
- `getApplicableConstraints(User $user): Collection`
- `validateSingleConstraint(...)`
- `validatePreClockIn(User $user, array $requestData = []): array`
- `createViolationRecord(...)`
- `resolveViolation(...)`
- `dismissViolation(...)`
- `getViolationStatistics(...)`
- `getUserViolations(...)`
- `getAttendanceViolations(...)`
- `validateBreakEnd(Attendance $attendance)`
- `createViolation(...)`
- `getTodaysWorkRulesForUser(User $user, $date = null, ?string $timezone = null): array`
- `getApplicableConstraintsForDataRetrieval(User $user): Collection`
- `bumpApplicableConstraintsCacheForCompany(string $companyId): void`

Important private methods:

- `buildTimeRules(...)`
- `getCurrentOrNextPeriodDetails(...)`
- `buildAdditionalLocationRules(...)`
- `buildLocationRules(...)`
- `mergeAdditionalLocationsForUser(...)`
- `resolveConstraintsFromDb(...)`

This service bridges legacy branch-location JSON and newer table-backed
constraint locations. Keep both sources in mind for read and validation paths.

### Specialized constraint services

These implement specific rule families:

- `TimeConstraintService`
- `LocationConstraintService`
- `DeviceConstraintService`
- `RoleConstraintService`
- `BehavioralConstraintService`
- `SecurityConstraintService`
- `ComplianceConstraintService`
- `RadiusEnforcementService`
- `BranchConstraintService`
- `DefaultConstraintService`

Most validator methods return either:

- `false` or `true` for no blocking violation, depending on the service method's
  historical convention.
- an array describing a violation.

Because return conventions differ between old and newer services, do not assume
all specialized validators use the same boolean semantics. Check the caller in
`AttendanceConstraintService::validateSingleConstraint` before changing one.

### UserAttendanceService

Path: `Services/UserAttendanceService.php`

Owns mobile/user-facing "today" and current status logic:

- resolves the user's effective constraint.
- builds today's work rules.
- overlays current and completed attendance data onto work periods.
- handles early clock-in active-period calculations.
- formats attendance status per period.
- derives timezone from user branch where possible.

Critical behavior: the "today" API must use current time for the current day,
not midnight from a bare date string.

### UserAttendanceHistoryService

Path: `Services/UserAttendanceHistoryService.php`

Builds user history responses. It merges scheduled periods with real attendance
rows, including empty scheduled period rows where needed.

Important behavior:

- Supports mobile API history and broader user history.
- Sorts and filters scheduled periods.
- Groups attendance rows by scheduled shift boundaries.
- Handles orphan attendance rows that do not map cleanly to schedule periods.
- Formats hours as `HH:MM` strings.
- Includes day names and branch-calendar date logic.

### AutoCloseAttendanceService

Path: `Services/AutoCloseAttendanceService.php`

Closes an attendance row at a scheduled close instant, not at arbitrary worker
wall-clock time. This matters for queued jobs that run late.

Important behavior:

- Reloads fresh attendance state.
- Avoids mutating already closed rows.
- Builds calculator input using the intended `closeAt`.
- Resolves constraint parameters from applied snapshot/current attendance data.
- May inspect last tracked location.

## 10. Constraint System

The module supports both broad constraint definitions and a front-end friendly
"regular" shift/rule model.

### Constraint assignment sources

Constraints can apply through:

- direct user assignment via `attendance_constraint_user`.
- additional per-user constraint routes.
- branch assignment through `branch_ids`.
- company-wide fallback when branch/user specificity is empty.
- inherited branch rules when `inherit_from_parent` is enabled.

### Constraint payload areas

`constraint_config` may contain:

- weekly schedule data.
- per-day work periods.
- lateness rules.
- early clock-in rules.
- break rules.
- location rules.
- device/security/compliance settings.

New code should preserve unknown keys unless the endpoint is explicitly designed
to replace a whole config section. The shifts and rules endpoints update focused
areas and should avoid wiping unrelated config.

### Weekly schedule and multiple periods

The data classes in `DataClasses` validate and normalize:

- enabled and disabled days.
- one or more work periods per day.
- cross-day/night-shift periods.
- overlapping periods.
- total weekly hours.
- grace windows.
- human-readable names.

The `TimeConstraintService::validateMultiplePeriods` code is the core time
window validator for these schedules.

### Locations

Location rules may come from:

- `attendance_constraints.branch_locations` JSON.
- `attendance_constraint_locations` rows.
- per-user additional constraints.
- request GPS payloads.
- ongoing `location_tracking` payloads.

When returning user constraint locations, include the same merged locations that
clock-in validation would use. A mismatch causes mobile clients to show a user
that they can clock in where the backend later rejects them, or the reverse.

When those locations exist and the employee is already clocked in, a GPS ping
outside them starts confirm-location (`attendance.out_zone_confirm_enabled`,
default true): three FCM pushes tell them to open the app, `work_rules.out_zone_warning`
and `GET /attendance/out-zone-warning` set `needs_location_confirm`, and mobile
POSTs current GPS to `/attendance/out-zone-warning/confirm-location`. That path
does **not** clock them out. Clock-in still requires GPS inside an allowed
location. Out-of-zone auto clock-out and the 45-minute no-GPS close stay off.
Scheduled `auto_max_ot` / next-shift close is unchanged.

### Violations

Violations should preserve:

- attendance id.
- constraint id.
- user/company context.
- violation type.
- severity/status.
- detailed payload for debugging and UI.
- resolution/dismissal metadata.

Blocking behavior is controlled by the rule and by
`AttendanceConstraintService::shouldBlockAttendance`.

## 11. Clock-In Flow

High-level request flow:

1. `POST /api/v1/attendance/clock-in`
2. `ClockInRequest` validates input.
3. `AttendanceController::clockIn` builds `ClockInDTO`.
4. `ClockInService::execute` validates pre-clock-in constraints.
5. `AttendanceService::clockIn` persists the attendance row.
6. Applied constraint snapshot is saved.
7. Auto-close and next-shift jobs may be scheduled.
8. `AttendanceClockedIn` event is dispatched/listened to.
9. Presenter formats response.

Key clock-in rules:

- A user must not already have an active clock-in.
- The current work period must be resolved from the applicable constraint and
  branch timezone.
- Early clock-in is allowed only inside the configured early window.
- If the user re-enters during the same scheduled period, do not accidentally
  create duplicate period rows unless that is intended by the current workflow.
- `max_over_time` must be snapshotted onto the attendance row.
- `business_date` should match the branch calendar day for the period, not the
  server day.
- The applied constraint snapshot is the future audit source.
- Location validation should happen before persisting when a rule is blocking.

### Which row receives the punch

`AttendanceService::persistClockInAttendance` prefers an existing un-clocked-in
row over creating a new one. The lookup is `clock_in_time IS NULL` plus either an
exact `start_time` match (regular) or a `business_date` / `start_time` date match
(flexible), ordered by `start_time` then `created_at`.

Two rules matter when touching this query:

- The order is load-bearing. A date can hold several un-clocked-in rows: one per
  scheduled period, plus rows the absence sweep already flipped to
  `is_absent = 1`. An unordered `first()` can activate the wrong period and leave
  the intended one absent.
- Reusing the row is what clears the absence. `buildClockInAttendanceData` sets
  `is_absent = 0`, `is_late = 0`, `is_holiday = 0`, `status = active`. If the
  lookup misses and a new row is created instead, the pre-existing absent row
  survives next to the punch row and the day reads as absent in any consumer that
  does not apply INV-16.

Flexible clock-in stores `start_time = clock_in_time`, so a flexible row no
longer carries its scheduled period start. Anything that keys a day off
`start_time` will mis-handle flexible rows; key off `business_date` instead.

Important date parsing rule:

When converting stored schedule/attendance strings, parse with the branch
timezone as the second argument where needed. Do not parse as UTC and then call
`setTimezone`, because that shifts a branch-local stored value.

## 12. Clock-Out Flow

High-level request flow:

1. `POST /api/v1/attendance/clock-out`
2. `ClockOutRequest` validates input.
3. `AttendanceController::clockOut` builds `ClockOutDTO`.
4. `ClockOutService::execute` delegates to `AttendanceService::clockOut`.
5. Current active attendance is locked/reloaded.
6. Active break is handled if necessary.
7. `AttendanceCalculator` computes work/break/overtime/lateness/early departure.
8. Attendance row is updated to completed.
9. Constraints can be validated for clock-out side effects.
10. Presenter formats response.

Key clock-out rules:

- Clock-out cannot precede clock-in.
- Completed rows should not be mutated as if active.
- Overtime is capped by the row snapshot, not by whatever the current constraint
  row says after edits.
- Breaks must be read from `attendance_breaks`.
- Auto-close must write the intended close instant, not worker execution time.
- Closing a row clears `is_absent`. Both `buildClockOutUpdatePayload` and
  `AutoCloseAttendanceService::closeIfExpired` write `is_absent = 0`, because both
  are already guarded on the row having a real `clock_in_time`. Without this, a
  row flipped absent before the employee arrived stays absent forever while
  carrying a full clock-in/clock-out pair.

This does not override deliberate absence decisions.
`AttendanceService::endShiftAutomatically($markAbsent = true)`, used by radius
enforcement, sets `is_absent = 1` and `status = completed` at the same time. A
completed row with a `clock_out_time` is invisible to `getCurrentAttendance()`
and fails the `status === active` guard in `closeIfExpired()`, so neither path can
undo it afterwards.

## 13. Break Flow

Breaks are represented by `AttendanceBreak` records.

Start break:

- User must have current active attendance.
- There must not already be an active break.
- A break record is created with `start_time`.

End break:

- User must have current active attendance.
- There must be an active break.
- `end_time` and `duration_minutes` are calculated.
- Attendance total break hours are updated/recalculated.

Legacy attendance break columns were dropped. Do not revive
`break_start_time` or `break_end_time` logic unless a migration explicitly brings
them back.

## 14. Auto Attendance And Scheduled Jobs

### Jobs in the module

- `AutoCloseAttendanceJob`
- `AutoClockOutAtNextShiftStartJob`
- `ProcessClockInAttendanceData`
- `MarkAbsentIfNoClockInJob`

### Console commands outside the module folder

Attendance commands live in `app/Console/Commands`:

- `CreateWaitingAttendanceCommand`
- `AutoCloseStaleShiftsCommand`
- `AutoCloseStaleLocationCommand`
- `UpdateAttendanceStatusCommand`
- `SendAttendanceSilentNotificationCommand`
- `MarkMissedClockInsAbsentCommand`

See section 3 for the full schedule, which spans both the provider and
`routes/console.php`.

Auto-close jobs should pass datetimes using ISO 8601 strings. This preserves the
instant across serialization and avoids positive/negative timezone offset bugs.

### Absence marking

Two writers can flip a row to absent, and both delegate to the same service:

- `MarkAbsentIfNoClockInJob`, dispatched at clock-in time with a delay until
  `ShiftWindow::$absentAt`.
- `MarkMissedClockInsAbsentCommand`, the every-five-minutes safety net for lost or
  delayed jobs.

`AbsenceMarkingService::markAbsentIfNoClockIn` is the only writer. It takes a row
lock, re-reads state, and refuses to act unless `clock_in_time` is still null and
the status is not already absent, completed, or holiday. A clock-in racing it
always wins, and repeat runs are no-ops. It also skips employees who are
legitimately elsewhere, via services tagged
`attendance.temporary_location_providers`.

`absent_at` is the deadline that produces the absence: `scheduled_start +
can_clock_in_before_minutes`, falling back to the end of the working window when
no deadline is configured. The whole subsystem is gated by
`config('attendance.absence_marking_enabled')`; when disabled the sweep reports
what it would have done instead of writing.

### AutoAttendanceService

`AutoAttendanceService::generateAttendanceUsers` backfills rows for scheduled days
that have none. Three details are easy to get wrong:

- Despite the `attendance:create-waiting` command name, the rows it creates for
  enabled work days are `status = absent` with `is_absent = 1`, not `waiting`.
  Holiday days get `status = holiday`.
- `$startDatePram` / `$endDatePram` default to the **whole current month**, not
  today. `CompanyUserRepository` calls it with no dates whenever professional data
  is saved for a user who has an attendance constraint, so an unrelated profile
  edit triggers a month-wide backfill.
- Dedupe is keyed on `business_date` plus the scheduled period start, and any date
  that already has a real clock-in is skipped entirely. Keying on `start_time`
  alone is wrong, because a flexible clock-in overwrites `start_time` with the
  punch time, which used to make a worked day look like a missing period and get a
  duplicate absent row inserted beside the real one.

Because the command runs every few hours rather than once a day, this method must
stay safe to re-run over dates that are already worked.

## 15. User-Facing Mobile APIs

### Today's constraint

Endpoint:

- `GET /api/v1/attendance/user-constraint/today`

Primary service:

- `UserAttendanceService::getUserConstraints`
- `AttendanceConstraintService::getTodaysWorkRulesForUser`

Response concept:

- effective work rules for the branch/current day.
- current or next work period.
- early clock-in rules.
- lateness rules.
- location/additional locations.
- attendance overlay per period.

Do not let the mobile response drift from actual validation. The frontend uses
this data to decide whether to enable controls and which locations are valid.

### Clock-in status

Endpoint:

- `GET /api/v1/attendance/user-attendance/status`

Primary service:

- `UserAttendanceService::checkClockInStatus`

Response should describe active attendance and scheduled period state.

### User attendance history

Endpoint:

- `GET /api/v1/attendance/user-attendance/history`

Primary service:

- `UserAttendanceHistoryService`

It should merge scheduled periods and actual attendance rows, including days
without attendance where a schedule exists.

## 16. Location Tracking And Radius Enforcement

Location tracking is exposed through:

- `GET /attendance/live-tracking`
- `POST /attendance/track-location`

Core classes:

- `LocationTrackingController`
- `LocationTrackingService`
- `RadiusEnforcementService`
- `LocationTrackingPoint`
- `LocationTrackingCollection`
- `BranchLocation`
- `TemporaryLocationException`

Tracked point data usually includes:

- latitude.
- longitude.
- accuracy.
- timestamp.
- battery/device metadata where available.

Radius enforcement should consider:

- allowed branch locations.
- additional locations.
- acceptable GPS accuracy.
- how long the user has been outside radius.
- temporary exceptions.
- whether the rule should auto-end the shift.

Distance calculations use haversine-style logic in data classes/services.

## 17. Leave Request Subsystem

Controller:

- `LeaveRequestController`

Models:

- `LeaveType`
- `LeaveRequest`
- `LeaveBalance`

Routes:

- `GET /leave/requests`
- `POST /leave/requests`
- `GET /leave/requests/{id}`
- `PUT /leave/requests/{id}`
- `DELETE /leave/requests/{id}`
- `GET /leave/my-requests`
- `POST /leave/requests/{id}/cancel`
- `GET /leave/pending-approvals`
- `POST /leave/requests/{id}/approve`
- `POST /leave/requests/{id}/reject`
- `GET /leave/calendar`
- `POST /leave/check-conflicts`

Leave status values:

- `pending`
- `approved`
- `rejected`
- `cancelled`

Leave requests can carry:

- date range.
- total days.
- reason.
- emergency flag.
- attachments.
- approval/rejection metadata.

## 18. Presenters And Response Shaping

Presenters keep API output shaping out of controllers/services.

Important presenters:

- `AttendancePresenter`
- `AttendanceTeamPresenter`
- `AttendanceUserPresenter`
- `AttendanceBreakPresenter`
- `AppliedAttendanceConstraintPresenter`
- `ConstraintPresenter`
- `ConstraintListPresenter`
- `LeaveRequestPresenter`
- `LiveTrackingPresenter`
- `UserAttendanceHistoryPresenter`

Important formatting conventions:

- API hour/minute values should generally leave presenters as `HH:MM` strings
  via `HoursFormatter` or equivalent helpers, not raw decimal internals.
- Attendance presenter computes live work/break hours for active rows.
- Breaks are formatted as arrays of break segments.
- Applied constraint presenter formats the snapshot, not current constraints.

When changing a presenter response contract, check feature presenter tests and
API collections.

## 19. Permissions

Config file: `Config/permissions.php`

Attendance permissions:

- `EMPLOYEE_ATTENDANCE_VIEW`
- `EMPLOYEE_ATTENDANCE_CREATE`
- `EMPLOYEE_ATTENDANCE_UPDATE`
- `EMPLOYEE_ATTENDANCE_DELETE`
- `EMPLOYEE_ATTENDANCE_EXPORT`
- `EMPLOYEE_ATTENDANCE_MAP`

Constraint permissions:

- `EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW`
- `EMPLOYEE_ATTENDANCE_CONSTRAINTS_CREATE`
- `EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE`
- `EMPLOYEE_ATTENDANCE_CONSTRAINTS_DELETE`

Routes use both the app's `Permission` enum helper and string permission
middleware in older hierarchy/leave areas. When unifying permission behavior,
audit both styles.

## 20. Timezone And Date Strategy

This is the module's most fragile area.

Rules:

- Branch-local schedule and attendance datetimes are intentionally stored as
  local strings.
- `Attendance` does not cast clock/schedule fields as `datetime`.
- Parse branch-local stored strings with the branch timezone when doing Carbon
  math.
- `timezone` on attendance rows freezes the relevant timezone at clock-in.
- `business_date` is the reporting date and should be based on branch calendar
  context.
- Queued jobs should preserve instants using ISO 8601 strings.
- Avoid server timezone assumptions in scheduled row creation and history APIs.

Common bug:

```php
Carbon::parse($attendance->start_time)->setTimezone($timezone)
```

If `start_time` is a branch-local stored string, this can parse it as the wrong
instant and then shift it. Prefer:

```php
Carbon::parse($attendance->start_time, $timezone)
```

when the value is known to be a local wall-clock string.

## 21. Concurrency And Locking Strategy

Important concurrency risks:

- double clock-in from repeated mobile requests.
- clock-out racing an auto-close job.
- two auto-close jobs for the same row.
- manual clock-out racing next-shift auto-clock-out.
- constraint edits while users have active attendance.
- bulk constraint mutations while active rows exist.

Expected patterns:

- Guard active clock-ins before creating a new row.
- Reload fresh attendance state before closing.
- Avoid mutating rows that are already closed.
- Use the intended close instant for auto-close calculations.
- Snapshot constraints at clock-in.
- Some repository methods guard against changing constraints with open
  attendance. Preserve this protection when refactoring bulk operations.

## 22. Cache Strategy

`AttendanceConstraintService` caches applicable constraints with a company
generation key. Relevant methods:

- `getApplicableConstraintsForDataRetrieval`
- `bumpApplicableConstraintsCacheForCompany`
- `getApplicableConstraintsCacheGeneration`
- `applicableConstraintsCompanyGenerationKey`

Constraint mutations must bump/invalidate the relevant company generation. If a
new mutation path edits branch assignments, user assignments, locations, rules,
or active status, ensure the cache generation is bumped.

## 23. Testing Map

Module-local tests live in:

- `modules/Attendance/Tests/Unit`
- `modules/Attendance/Tests/Feature`

Additional attendance tests live under:

- `tests/Unit/Attendance`
- `tests/Feature/Attendance`

High-value test areas:

- Calculator regression tests.
- Auto break computer tests.
- Clock-in concurrency tests.
- Auto-close race tests.
- Timezone resolver tests.
- Presenter contract tests.
- Attendance constraint integration tests.
- Branch location integration tests.
- Multiple periods data class and validation tests.
- Specialized service tests:
  - time constraints.
  - location constraints.
  - radius enforcement.
  - device constraints.
  - role constraints.
  - behavioral constraints.
  - security constraints.
  - compliance constraints.

Useful targeted commands:

```bash
php artisan test modules/Attendance/Tests/Unit/Calculator
php artisan test modules/Attendance/Tests/Feature/ClockFlow
php artisan test modules/Attendance/Tests/Feature/Presenters
php artisan test modules/Attendance/Tests/Feature/AttendanceConstraintsIntegrationTest.php
php artisan test tests/Feature/Attendance/MultiplePeriodsConstraintTest.php
```

Run broader tests when touching shared services:

```bash
php artisan test modules/Attendance/Tests tests/Unit/Attendance tests/Feature/Attendance
```

## 24. Extension Guide

### Add a new attendance response field

1. Add or derive the field in the relevant presenter.
2. Keep service return types unchanged unless the field is business logic.
3. Update presenter contract tests.
4. Update API collection/OpenAPI docs if the field is public.

### Add a new constraint rule

1. Add constants to `AttendanceConstraint` if the rule has a stable name/type.
2. Add request validation for the new config keys.
3. Implement validation in the proper specialized service.
4. Wire dispatch/orchestration in `AttendanceConstraintService`.
5. Decide blocking vs non-blocking behavior.
6. Add violation details with enough context for support/debugging.
7. Add unit tests for the specialized service.
8. Add integration tests if it affects clock-in/clock-out.
9. Ensure cache invalidation on config changes.

### Add a new shift/schedule shape

1. Update data classes first.
2. Update request validation.
3. Update `TimeConstraintService` period matching.
4. Update `AttendanceConstraintService::buildTimeRules`.
5. Update user today response and history merge logic.
6. Test cross-day, overlap, disabled-day, early window, grace, and history cases.

### Add a new location source

1. Add a model/table or config source.
2. Merge it into backend validation.
3. Merge it into user today's constraint/location response.
4. Add tests proving the frontend-visible locations match the validator.

### Add an auto-close mode

1. Define a new `shift_end_method` value.
2. Ensure the closing instant is deterministic and serialized safely.
3. Use `AttendanceCalculator` for hours.
4. Avoid mutating already closed rows.
5. Add race tests.

## 25. High-Risk Invariants

The `INV-xx` identifiers below are local to this document. Code comments in the
Rules V2 areas (`ShiftWindowCalculator`, `AbsenceMarkingService`,
`AttendanceService`) cite a separate series that reuses some of the same numbers
for different rules, and whose definitions are no longer in the repository. Do not
assume an `INV-14` or `INV-15` in a code comment refers to the entry here.

### INV-01: Do not datetime-cast branch-local clock fields

`clock_in_time`, `clock_out_time`, `start_time`, and `end_time` are stored as
branch-local strings. Laravel datetime casts can shift them as if they were UTC.

### INV-02: Applied constraints are historical truth

Past attendance should use `applied_attendance_constraints.constraint_snapshot`
for rule interpretation. Current constraint rows can be edited after the fact.

### INV-03: Auto-close writes intended close time

Queued jobs may run late. The row must close at the scheduled `closeAt` instant,
not at worker `now()`.

### INV-04: Job datetime payloads should be ISO 8601

Use `toIso8601String()` for queued datetime constructor arguments. Avoid plain
`Y-m-d H:i:s` when preserving an instant matters.

### INV-05: API hour fields should be formatted consistently

Decimal hours are useful internally. API consumers usually expect `HH:MM`
strings. Presenters/support formatters should own the conversion.

### INV-06: Today's rules must use current time for today

Do not call the "today" logic with a date parsed to midnight if the rule needs
to know the currently active or next period.

### INV-07: Location response must match validation locations

If a valid clock-in location is added to backend validation, it must also show in
the user constraint response used by the mobile app.

### INV-08: Breaks live in `attendance_breaks`

Do not read/write old break columns on `attendances`.

### INV-09: Constraint service singletons must stay stateless

Never cache request-specific data in singleton service properties.

### INV-10: Bulk constraint edits must consider open attendance

Changing a constraint while active attendance exists can alter current workflow
expectations. Preserve repository guards and snapshot behavior.

### INV-11: Multiple-period matching must use period windows

For re-clock-in, history grouping, and lateness anchors, match against the
scheduled period start/end window, not just the calendar date.

### INV-12: Grace rules are nested in schedule data

Per-day lateness/early rules can live under weekly schedule day config. Do not
only read top-level `time_rules` if the schedule stores rules per day.

### INV-13: Cross-day periods belong to the intended work day

Night shifts and periods crossing midnight should anchor to the configured work
day, not blindly to the clock event's calendar date.

### INV-14: Cache invalidation is part of writes

Constraint location, shift, assignment, active state, and rule changes must bump
applicable-constraint cache generation.

### INV-16: A real clock-in outranks a stale absence flag

`is_absent` is a point-in-time decision written before the employee arrived. It is
not authoritative once a punch exists on the same `business_date`, possibly on a
different row.

Any consumer that derives a day status must treat a real `clock_in_time` as
beating a sibling row flagged absent, and must evaluate the whole date rather than
whichever row it happens to read first. Current implementations of this rule:

- `AttendanceFilter::attendanceStatus`, via a `whereNotExists` on a sibling row
  with a clock-in.
- `AttendanceCalendarService`, `hasPresence` before `hasAbsent`.
- `UserAttendanceHistoryService::buildDayStatusPayload`, same ordering.
- `UserAttendanceService::enhancePeriodsWithAttendance` on
  `GET /attendance/user-constraint/today`: attach a punch in the allowed window
  (early + extension) or whose stored `start_time` is the scheduled period start;
  `is_absent` is only true when that period has no clock-in and `absent_at` is
  past. Matching only the scheduled block (08:30–17:30) dropped an 08:00 early
  punch, so today reported `is_absent: true` / `attendance: []` while the
  calendar — keyed on `business_date` — showed حاضر.
- `Modules\Reports\Services\ReportDataExtractionService::mergeDisplayStatus`,
  precedence `holiday > present > absent` across every row of the date.

Per-day numeric fields have the same hazard. Reading `late_minutes` from the first
row of a date reports `00:00` when that row is an empty absence row, hiding the
lateness recorded on the row that owns the punch. Accumulate across the date.

### INV-17: Today is not absent until the clock-in deadline passes

`AutoAttendanceService` pre-creates the whole month's period rows with
`status = absent`, so a day-status reader that trusts those rows flips today to
`غائب` at midnight even though the employee can still arrive on time.

A day in progress may only read as absent once the employee can no longer punch
in. The deadline is `can_clock_in_until` from
`GET /attendance/user-constraint/today`, which is
`ShiftWindow::$firstClockInDeadline ?? ShiftWindow::$lastClockInAt` — the same
value as `absent_at`. Readers should take it from that endpoint's payload rather
than recomputing `start ± minutes`, so the calendar cannot disagree with the
screen that offers the clock-in button.

`attendance_type = flexible` has no first-clock-in deadline:
`ShiftWindowCalculator::computeFlexible` returns
`firstClockInDeadline = null` and `lastClockInAt = end of day`, so a flexible
employee only becomes absent at end of day. Reading the deadline from the
endpoint gets this for free; hardcoding a schedule-period deadline does not.

Current implementations, both applied to the no-rows branch and the
pre-created-absent-rows branch:

- `AttendanceCalendarService::resolvePendingClockInDay`, in `buildDayData`. Gated
  on today, because the `$isFuture` branch already reports `required` and past
  days are settled.
- `UserAttendanceHistoryService::isStillAwaitingClockIn`, in
  `buildDayStatusPayload`. Not gated on today, because that endpoint pages over
  whole months through one branch; it short-circuits on past dates instead, so a
  month page only pays for the constraint lookup on today and later.

Both emit the literal `مطلوب للحضور` so the two endpoints agree byte-for-byte on
the same date.

`Modules\Reports\Services\ReportDataExtractionService::mergeDisplayStatus` does
not apply this rule; reports are read after the fact, where every day is settled.

### INV-18: `عطلة` belongs to the schedule, `إجازة` belongs to the person

Both labels used to come out of the single `attendances.is_holiday` flag, so every
weekend printed as `إجازة` in the report PDF while a holiday granted through
`PATCH /api/v1/sub_entities/records/attendance-status` printed as `عطلة` in the
calendar and the history endpoint — each the opposite of what it meant.

The two are decided from different sources and must not be collapsed:

- `عطلة` — the schedule does not work this date. Either the weekday is disabled in
  `time_rules.weekly_schedule`, or the date is listed in `time_rules.holidays`.
- `إجازة` — time off granted to this employee on a date the schedule would
  otherwise work: an approved `LeaveRequest`, any attendance-status override
  range in `user_manual_attendance_overrides` that covers the date, or an
  official public holiday for the employee's country (INV-21).

`عطلة` is evaluated first. A weekend falling inside an override range — or under an
official public holiday — stays `عطلة`, because a day the employee never works cannot
spend a leave day.

A later holiday PATCH **adds** a range; it does not replace earlier disjoint days.
Granting the 27th then the 30th leaves both as `إجازة` and leaves the 28th and 29th
on the constraint. Adjacent or overlapping ranges of the same status merge.
`required_attendance` for a range punches only those dates out of holiday coverage.
The three `users.manual_attendance_status*` columns are a snapshot of the last PATCH
only — never the full grant.

Support classes hold the shared decision so the consumers cannot drift:

- `Modules\Attendance\Support\ManualAttendanceStatus` — the only reader of granted
  ranges (`user_manual_attendance_overrides`, falling back to the three `users`
  columns when the table has no rows yet). `activeOn()` for models,
  `resolve()`/`isHolidayFor()` for a single legacy window.
- `Modules\Attendance\Support\ManualAttendanceOverrideSet` — add/punch/merge math
  for disjoint ranges.
- `Modules\Attendance\Support\ScheduledWorkDays` — `isWorkDay(date)` from the
  winning time constraint, resolved once per request via
  `AttendanceConstraintService::getScheduledWorkDaysForUser` so a month-wide reader
  does not build per-day rules just to detect a weekend. With no weekly schedule
  configured every date is schedulable, because there is then no basis to call a day
  `عطلة`.
- `Modules\Attendance\Support\PublicHolidayDates` — `isHoliday(date)` for the official
  holidays of the employee's country, loaded for a whole range at once by
  `PublicHolidayCalendarService` (INV-21).

Consumers: `AttendanceCalendarService::buildDayData` (`status_key` `off` vs
`leave`), `UserAttendanceHistoryService::buildDayStatusPayload`
(`dayOffStatusPayload` vs `leaveStatusPayload`), and
`ReportDataExtractionService` — `publicHolidayDisplayStatus` then
`holidayDisplayStatus` (`display_status` `holiday` vs `leave`, rendered by
`pdf/report.blade.php` as `عطلة` / `إجازة`).

Setting a range rewrites every attendance row inside it and punching the range later
does not undo those writes, so each of those rows carries
`ManualAttendanceStatus::HOLIDAY_ROW_NOTE`. Once no holiday range covers a row's
date the reader drops the row and resolves the day from the constraint, which is
what makes a punched day revert to normal. Never infer the override from
`is_holiday`, `day_status` or `status` alone — the weekend, public-holiday and
override rows are byte-identical on those columns.

### INV-19: A task does not stand in for a punch

Holding a task no longer makes an employee present. Every attendance surface used
to carry a `متواجد` (`on_task`) overlay: an employee with task activity on a date
read as present even with no clock-in, and the day's task minutes were added to
the worked hours. That was a workaround for task sites not being clockable — it is
obsolete now that an active task publishes a temporary geofence
(`EmployeeTaskTemporaryLocationProvider`, `source: employee_task`) that the
employee can genuinely clock in at, which produces a real attendance row that
every consumer already understands.

Presence therefore has exactly one source: an attendance row. A task without a
punch leaves the day `غائب` (or `مطلوب للحضور` while INV-17's deadline still
stands). Removed, and not to be reintroduced:

- `AttendanceCalendarService` — the `on_task` `status_key`, its dot colour and
  `summary.on_task_count`. The day's `tasks[]` array stays, informational only.
- `AttendanceStatusService` / `AttendanceTeamPresenter` — `on_task` status,
  `presentViaTask()`, `usersOnTask()` and the `$hasActiveTask` flags.
- `ReportDataExtractionService` — the task-presence merge that flipped
  `display_status` from `absent` to `present`, invented day rows for task-only
  dates and added task minutes to `total_work_hours` / `calculated_hours`.
- `AttendanceDashboardService`, `AttendanceReportService` and the
  `TaskAttendancePresenceService` they shared (deleted) — task days and hours no
  longer top up `actual_attendance_days` / `actual_worked_hours`.
- `validation.day_status.on_task` in both `lang/ar` and `lang/en`.

`AbsenceMarkingService` still consults `TemporaryLocationProvider::isEngagedElsewhere()`
to hold off auto-absence while an employee is on a task. That is a write-side
grace period, not a display status, and is deliberately left in place.

Out-of-zone auto clock-out is currently disabled
(`attendance.out_zone_auto_clock_out_enabled`, default false). When that flag is
on, `LocationConstraintService::validateMultiLocation` (`enforcement_action: auto_out_zone`,
after the 5-minute voice warning) is skipped for the same reason when the
constraint has locations and the employee has field work **that calendar day**:
an accepted employee task (`approved` / `in_progress` / `paused`), or a project
notification that has been sent (`pending`) or accepted/received (`in_progress`).
`TemporaryLocationProvider::hasFieldAssignmentOn()` is the shared reader;
`FieldWorkOutOfZoneExemption` is the write-side guard. The live task geofence is
not required — a task can be accepted, or a notification sent, before it publishes
coordinates. This does not make the day `متواجد` without a punch (INV-19).

### INV-20: The matched geofence is only knowable at punch time

A punch taken inside an active task's temporary geofence is ordinary attendance — it
counts as a present day, its hours accrue normally, and the report prints its time
in the same `دخول فعلي` / `خروج فعلي` columns as any office punch. There is no
separate task in/out column in the report.

The punch is still attributed to the task on the row. That attribution cannot be
recomputed later: validation asks only "is this coordinate inside any allowed
circle", and the allowed list is a flat merge of branch locations,
`attendance_constraint_locations`, locations from the user's additional
constraints, and task geofences; the match discards which circle won. The task
geofence itself is transient — `EmployeeTaskTemporaryLocationProvider` publishes
it only while the task is `in_progress` and inside `time_from + duration_hours` —
so by the time a report runs, the circle that accepted the punch no longer exists.
It is therefore recorded on the row: `attendances.clock_in_task_id` and
`attendances.clock_out_task_id`, resolved in
`AttendanceService::buildClockInAttendanceData` and
`buildClockOutUpdatePayload` via `TaskLocationPunchResolver`.

Both sides are resolved independently. An employee may start at the office and
finish at a task site, or the reverse, and each side is attributed on its own.

Two rules the resolver must keep:

- **A constraint location outranks an overlapping task geofence.** An employee
  standing at their own branch is at work, whatever task they also hold, so
  `clockInLocationsByKindForUser` returns the two kinds separately and the
  constraint set is tested first. Task entries are identified by
  `source = employee_task` — the only tag the merge preserves — and carry the task
  id in `reference_id`.
- **Location bookkeeping may never break a punch.** A failed constraint lookup
  resolves to "no task", leaving a valid attendance row that is simply not
  attributed.

`Modules\Attendance\Support\GeofenceMatch::first()` holds the predicate for both
callers, so a punch clock-in accepted cannot be one the resolver rejects.

Because the task punch is printed with the ordinary columns, no task-kind gating is
needed: the punch shows as soon as it exists. The `clock_in_task_id` /
`clock_out_task_id` attribution is kept on the row for audit and any future
breakdown, but it does not change where the time is printed.

**A task belongs to a user in two ways, and the geofence must follow both.**
`EmployeeTaskTemporaryLocationProvider` matches the task's own `user_id` and, for
project notifications, any user in the notification's `assigned_user_ids` (via
`ProjectNotification.employee_task_request_id`) — the same two paths
`EmployeeTaskPresenceService::taskIdsByUser` used. Project-notification task rows
are stored with a null `company_id` outside the tenant scope, so their assignees are
visible only on the notification. Dropping the second path leaves an assignee who
sees the task on their calendar yet has no geofence to clock in at, which is exactly
the case INV-19's removal of the `on_task` overlay depends on.

### INV-21: An official holiday is read, never materialised

A public holiday applies to an employee because of where they work, so the country is
resolved from their branch — `userProfessionalData.branch.address.country_id`, then
`user.branch.address.country_id`, then `company.country_id` when the branch has no
address on file. `PublicHolidayCalendarService::countryIdForUser` owns that order; a
missing branch address must never silently cancel an employee's holidays, which is why
the company fallback exists.

`public_holidays` is a central table shared by every tenant and keyed on `country_id`,
so the applicable dates are read live on each surface. The **applied days** in
`public_holiday_days` are the only trustworthy source, never the `date_start .. date_end`
range on the parent: `PublicHolidayDayCalculator` shifts a single-day holiday off a
weekend (Mon–Wed roll to Thursday, Fri/Sat to Sunday) and appends one compensation day
per Fri/Sat inside a multi-day range, so the parent range and the days genuinely differ.

**Reading beats pre-writing.** This used to work the other way: creating or updating a
holiday dispatched `SyncHolidayAttendanceJob`, and `attendance:create-holiday-attendance`
ran daily, both inserting one `is_holiday = 1` attendance row per user of every company
in the holiday's country. All of it is deleted, and must not come back:

- it keyed on `companies.country_id`, so an employee posted to a branch in another
  country got their company's holidays instead of their own;
- the job only wrote days `>= today`, so a back-dated holiday, and any employee hired
  after the holiday was created, silently got nothing;
- nothing ever deleted those rows, so editing a date or deactivating a holiday left the
  old days off standing forever;
- it never touched the work rules, so `/attendance/user-constraint/today` still returned
  periods and an employee could clock in on a holiday;
- it was scheduled twice, from `app/Console/Kernel.php` and from
  `AttendanceServiceProvider::registerSchedules()`, onto the same log file.

Rows the old writer left behind are recognised by
`PublicHolidayDates::isLegacyGeneratedRow()` (`notes` beginning `Auto-generated holiday
record:`) and dropped by every reader, exactly as a stale override row is
(`ManualAttendanceStatus::isHolidayRow`). Without that, a deleted holiday would keep its
days off for as long as those rows exist.

`Modules\Leave\PublicHoliday\Services\HolidayValidationService` is deleted too: it had
no callers and answered from the parent date range, so it disagreed with the applied
days by construction.

**Where it is applied.** One seam covers the system:
`AttendanceConstraintService::getTodaysWorkRulesForUser` sets `day_status = 'holiday'`,
`is_holiday = true`, `reason` to the holiday's name, and empties `all_work_periods` and
every `*_period` key. The periods are what carry `can_clock_in_until`, so emptying them
is what removes the clock-in — the same shape a constraint holiday and a manual holiday
override already produce (INV-17). Everything downstream of that method inherits the
behaviour: `/attendance/user-constraint/today`, clock-in validation in
`AttendanceService` and `ProcessClockInAttendanceData`, and the `allow_on_holidays` /
`allow_outside_shift` task condition evaluators.

Applied only to a day whose `day_status` is `work_day` or `Undefined`. A weekend already
answers `is_holiday` with no periods, and relabelling it would call a day the employee
never works an official holiday, which INV-18 assigns to the schedule.

**A `required_attendance` override outranks the holiday calendar.** The calendar is
country-wide and knows nothing about one admin's instruction for one employee, so
`ManualAttendanceStatus::isRequiredAttendanceOn()` (and `isRequiredAttendanceFor()` for raw
rows) suppresses the holiday on all four surfaces.

The order matters and is the reason `UserAttendanceService::getUserConstraints` resolves the
override *before* building the rules and passes `PublicHolidayDates::none()`: the holiday
empties `all_work_periods`, and `applyManualAttendanceOverride` can set `day_status` back to
`work_day` but cannot rebuild the periods. Applying them the other way round returns a work
day carrying no `can_clock_in_until`, telling the employee to attend while giving the app
nothing to clock in against. `applyManualAttendanceOverride` therefore takes the resolved
status rather than the user, so the ordering is not optional.

The override does not beat an approved `LeaveRequest`, nor a weekend or constraint holiday:
those days define no periods at all, so there is nothing to attend.

**Range readers pass their own dates.** `getTodaysWorkRulesForUser` takes an optional
pre-resolved `PublicHolidayDates`; the calendar and history resolve one for the whole
month and hand it down, so a month view costs one holiday query rather than thirty. The
report groups its employees by country and queries once per distinct country
(`ReportDataExtractionService::publicHolidaysByUser`). `PublicHolidayCalendarService`
holds no state of its own, so nothing survives a request under Octane.

**The aggregate counters resolve their country the same way.**
`AttendanceReportRepository::countPublicHolidayDaysInPeriod` and
`countAllPublicHolidayDaysInPeriod` feed `month_holidays` and `required_hours`, and their
`$countryId` now comes from `countryIdForUser()` in both `AttendanceDashboardService` and
`AttendanceReportService`. They previously read `employment_contracts.country_id` directly,
which could disagree with the branch country the day labels use — the same report then
counted a holiday the day rows did not mark, or the reverse.

The contract country is passed as `countryIdForUser`'s `$fallbackCountryId`, so it is still
consulted last for records carrying neither a branch address nor a company country, and no
figure that used to count silently drops to zero. `getEmployeeForCompany` eager-loads
`userProfessionalData.branch.address` so the resolution adds no queries.

INV-15 is intentionally skipped here; it is claimed by the Rules V2 series cited
in code comments.

## 26. Troubleshooting Checklist

### User cannot clock in

Check:

- active attendance already exists.
- current branch timezone.
- today's effective constraint.
- early clock-in window.
- multiple-period enabled day and current period.
- GPS payload shape and accuracy.
- merged branch/additional locations.
- blocking violation response.
- cache generation after recent constraint edits.

### User sees valid location but backend rejects

Check:

- `branch_locations` JSON.
- `attendance_constraint_locations` rows.
- direct/additional user constraints.
- `buildAdditionalLocationRules`.
- `buildLocationRules`.
- mobile `user-constraint/today` response.
- radius unit is meters, not kilometers.

### Hours look shifted

Check:

- whether code parsed branch-local values as UTC.
- whether a datetime cast was introduced.
- attendance row `timezone`.
- branch/company timezone fallback.
- job datetime serialization.

### Overtime is wrong

Check:

- scheduled period bounds.
- break minutes.
- `max_over_time` snapshot on attendance.
- applied constraint snapshot.
- `StandardOvertimePolicy`.
- whether auto-close used intended close time.

### History has missing or duplicate periods

Check:

- scheduled period keys.
- cross-day period anchoring.
- `business_date`.
- `UserAttendanceHistoryService` schedule merge.
- orphan attendance rows.
- whether clock-in falls inside the scheduled period window.

### Today says absent, calendar says present, clock-in button hidden

`GET /attendance/user-constraint/today` and
`GET /attendance/user-attendance/calendar` are not the same reader.

The calendar groups by `business_date` and applies INV-16: any `clock_in_time`
on that date is حاضر. Today used to attach a punch only when `clock_in_time`
sat inside the scheduled period (08:30–17:30), then set period `is_absent`
once `can_clock_in_until` / `absent_at` passed and `attendance` was empty.

An allowed early clock-in (08:00 for an 08:30 start, `early_period` 30) is
outside that block. After 10:30 the period flipped to `is_absent: true`,
`can_clock_in: false`, `attendance: []`, while the calendar still showed the
7h punch as حاضر. The app hides the button from those flags; the write-side
clock-in path keys the same row by scheduled `start_time` and may still allow
a re-entry until `lastClockInAt`.

Check:

- punch `clock_in_time` versus scheduled `start_time` (early window / extension).
- whether today now attaches that punch (`attendance` not empty, `is_absent` false).
- leftover `is_absent` rows on the same `business_date` (INV-16).

### Day shows absent but has clock-in and clock-out times

This is almost always more than one row on the same `business_date`, where the
reader took its status from a row that is not the one holding the punch.

Check:

- how many `attendances` rows exist for that `user_id` and `business_date`. An
  empty row with `is_absent = 1` beside a row with real times is the signature.
- the delay shown next to the punch. `00:00` against a visibly late clock-in means
  the number came from the empty absence row, not the punch row.
- whether the reader applies INV-16, or freezes the day on the first row it sees.
- whether the punch row's `start_time` still equals the scheduled period start. A
  flexible row stores the clock-in there instead, which used to defeat
  `AutoAttendanceService` dedupe and produce the duplicate.
- `notes` on the absent row. `Auto-marked absent: no clock-in before
  can_clock_in_before deadline.` identifies `AbsenceMarkingService` as the writer.
- whether a single row simply kept a stale flag, which happens only on rows closed
  before clock-out and auto-close began clearing `is_absent`.
- `mark_absent_if_violated` on the location constraint. Radius enforcement marks a
  day absent deliberately, on a row that legitimately has both punches.

### Constraint edit seems ignored

Check:

- constraint `is_active`.
- `start_date` and `end_date`.
- user pivot assignment.
- branch assignment.
- cache generation.
- tenancy/company id.
- whether active attendance is using an old snapshot by design.

## Related Repository Documents

Useful companion files at the repository root:

- `ATTENDANCE_MODULE_DEEP_REFERENCE.md`
- `ATTENDANCE_MODULE_REFACTORING_PLAN.md`
- `ATTENDANCE_CONSTRAINT_MANAGEMENT_APIS.md`
- `MULTIPLE_PERIODS_CONSTRAINT_GUIDE.md`
- `MULTIPLE_PERIODS_API_EXAMPLES.md`
- `DATA_CLASSES_USAGE_GUIDE.md`
- `openapi_attendance_module.yaml`
- `Contsrix_Core_Api_Attendance_Module.postman_collection.json`
- `Attendance_Constraint_Management_APIs.postman_collection.json`

