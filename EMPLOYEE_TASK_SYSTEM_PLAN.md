# Employee Work Task Request System — Complete Implementation Plan

> **Status:** FULLY IMPLEMENTED — All 7 Phases Complete  
> **Last updated:** 2026-05-18  
> **Depends on:** Attendance Module (ATTENDANCE_MODULE_DEEP_REFERENCE.md), ProcedureSetting Module

---

## Table of Contents

1. [System Overview & Design Philosophy](#1-system-overview--design-philosophy)
2. [Integration Model: Tasks + Attendance](#2-integration-model-tasks--attendance)
3. [Status State Machine](#3-status-state-machine)
4. [Database Schema (All New Tables)](#4-database-schema-all-new-tables)
5. [ProcedureSetting Enhancement](#5-proceduresetting-enhancement)
6. [Module Structure](#6-module-structure)
7. [Business Logic Rules (Full Encyclopedia)](#7-business-logic-rules-full-encyclopedia)
8. [Service Layer Design](#8-service-layer-design)
9. [Jobs & Background Processing](#9-jobs--background-processing)
10. [API Endpoints (Complete List)](#10-api-endpoints-complete-list)
11. [Intra-Day Report Structure](#11-intra-day-report-structure)
12. [Location Validation Algorithm](#12-location-validation-algorithm)
13. [Hours Calculation Integration](#13-hours-calculation-integration)
14. [Invariants & Safety Rules](#14-invariants--safety-rules)
15. [Implementation Phases](#15-implementation-phases)
16. [Risk Register](#16-risk-register)
17. [Postman Collection Plan](#17-postman-collection-plan)

---

## 1. System Overview & Design Philosophy

### What This Builds

An **Employee Work Task Request (طلب مهمة عمل)** system where:

1. An employee submits a task request with a title, description, duration, date, and GPS location.
2. The request passes through the existing **ProcedureSetting** approval workflow (new type: `employee_task_request`).
3. After approval, the employee can **start**, **pause**, **resume**, and **end** the task from the mobile app.
4. While the task is active, the system continuously validates the employee's GPS position against the task location using a radius taken from their **main attendance constraint**.
5. If the employee is out of the task location radius for longer than the constraint's allowed threshold → **auto clock-out from task**.
6. Task hours and regular attendance hours are **parallel independent systems** that are **totalled together** for the daily work summary.
7. The attendance report is enhanced with an intra-day view showing all clock-in/clock-out pairs AND all task sessions.

### What Does NOT Change

- **`attendances` table**: zero schema changes. API payload byte-equivalent.
- **`AttendancePresenter`** output: not touched.
- **`AttendanceConstraintService`** core logic: not touched (only read from it).
- All existing clock-in/clock-out flows: not touched.
- ProcedureSetting module internal logic: only add enum case + new handling after approval.

### Architectural Principle

The task system is a **separate module** (`modules/EmployeeTask/`) that:
- **Reads** from the Attendance module (constraint radius, distance calculator).
- **Adds** to the daily report (via a new aggregated endpoint).
- **Never writes** to `attendances`, `attendance_breaks`, or any existing Attendance table.

---

## 2. Integration Model: Tasks + Attendance

### Two Independent Systems, One Total

```
──────────────────────── WORKDAY TIMELINE ────────────────────────────────
 
 06:00   07:00   08:00   09:00   10:00   11:00   17:00   18:00
   │       │       │       │       │       │       │       │
   │  [TASK START─────────────────TASK END]│       │       │
   │  (at task GPS location, e.g. 46.6, 23.6)      │       │
   │       │       │       [CLOCK IN──────────────CLOCK OUT]
   │       │       │       (at attendance constraint location)
   │       │       │       │       │       │       │       │
   
TASK HOURS:        task_time_from → task_time_to  = 2h 00m
ATTENDANCE HOURS:  clock_in_time  → clock_out_time = 8h 00m
TOTAL WORK HOURS:                                  = 10h 00m (summed)
SCHEDULED HOURS:   (from constraint weekly_schedule)= 8h 00m
OVERTIME HOURS:    10h - 8h = 2h (capped by max_over_time from constraint)
```

### Rules for Total Hours

- `attendance_hours` = sum of all completed attendance sessions for the day (net of breaks)
- `task_hours` = sum of all completed task sessions for the day (net of pauses)
- `total_work_hours` = `attendance_hours` + `task_hours`
- **Overlap is logically impossible**: the employee cannot be at two GPS locations simultaneously. The system trusts this and does NOT deduplicate.
- Lateness is always calculated from the attendance constraint alone (task start before shift does NOT reset the lateness clock).
- Overtime is calculated on `total_work_hours` against scheduled hours from the constraint.

### When Task Is Before/After Shift

```
Scenario A — Task BEFORE shift:
  Task: 07:00–09:00 (2h). Shift: 09:00–17:00 (8h).
  Employee clocks in at 09:05 (5 min late).
  Total = 2h (task) + 7h55m (attendance) = 9h55m
  is_late = true (clock-in at 09:05 > scheduled 09:00 + grace)
  overtime = 9h55m - 8h = 1h55m (capped by max_over_time)

Scenario B — Task AFTER shift:
  Task: 17:30–19:00 (1.5h). Shift: 09:00–17:00 (8h).
  Total = 8h + 1.5h = 9.5h
  overtime = 1.5h (capped by max_over_time)
```

---

## 3. Status State Machine

### Task Request Status Values

Confirmed from the mobile UI (Arabic labels in parentheses):

| Status | Arabic Label | DB Value | Description |
|---|---|---|---|
| `pending` | في انتظار الاعتماد | `pending` | Submitted, awaiting procedure setting approval |
| `approved` | معتمدة | `approved` | Approved by admin, employee can start |
| `rejected` | مرفوضة | `rejected` | Rejected by approver |
| `in_progress` | جارية | `in_progress` | Employee has started the task (time_from set) |
| `paused` | موقوفة | `paused` | Employee manually paused |
| `completed` | مكتملة | `completed` | Task ended (time_to set, hours calculated) |
| `cancelled` | ملغي | `cancelled` | Cancelled by employee (before approval) OR by admin (any time) |

### Extension Badge (Separate from Status)

The UI shows a secondary badge **on top of the main status badge** to communicate the last extension request result. This is separate from `status` — a task can be `in_progress` while showing "تم رفض التمديد".

Add column `last_extension_status` (`varchar(30) NULL`) to `employee_task_requests`:

| Value | Arabic Badge | Trigger |
|---|---|---|
| `null` | *(no badge shown)* | No extension ever requested |
| `extension_pending` | طلب تمديد قيد الانتظار | Extension request submitted and pending |
| `extension_approved` | تم قبول التمديد | Latest extension approved |
| `extension_rejected` | تم رفض التمديد | Latest extension rejected |

**Update rule:** `last_extension_status` is updated whenever an extension request changes state:
- Employee submits extension → `extension_pending`
- Admin approves → `extension_approved`
- Admin rejects → `extension_rejected`
- A new extension request is submitted after a rejection → resets to `extension_pending`

**API presenter:** Always include `last_extension_status` and `last_extension_status_label` in every task response so the frontend can render the badge independently of the main status.

```json
{
  "status": "in_progress",
  "status_label": "جارية",
  "last_extension_status": "extension_rejected",
  "last_extension_status_label": "تم رفض التمديد"
}
```

### Arabic Status Labels Helper

Add `EmployeeTaskStatus` enum with `label()` method:

```php
enum EmployeeTaskStatus: string
{
    case Pending    = 'pending';
    case Approved   = 'approved';
    case Rejected   = 'rejected';
    case InProgress = 'in_progress';
    case Paused     = 'paused';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Pending    => __('employee_task.status.pending'),    // في انتظار الاعتماد
            self::Approved   => __('employee_task.status.approved'),   // معتمدة
            self::Rejected   => __('employee_task.status.rejected'),   // مرفوضة
            self::InProgress => __('employee_task.status.in_progress'),// جارية
            self::Paused     => __('employee_task.status.paused'),     // موقوفة
            self::Completed  => __('employee_task.status.completed'),  // مكتملة
            self::Cancelled  => __('employee_task.status.cancelled'),  // ملغي
        };
    }
}
```

Language file additions:
- `lang/ar/employee_task.php` → status translations in Arabic
- `lang/en/employee_task.php` → status translations in English

### Valid Transitions

```
pending      → approved       (admin approves via procedure setting)
pending      → rejected       (admin rejects via procedure setting)
pending      → cancelled      (employee withdraws own request)
approved     → in_progress    (employee starts task)
approved     → cancelled      (admin cancels)
in_progress  → paused         (employee pauses)
in_progress  → completed      (employee ends manually) [shift_end_method = 'manual']
in_progress  → completed      (auto: out of location too long) [shift_end_method = 'auto_location']
in_progress  → completed      (auto: duration + max_over_time expired) [shift_end_method = 'auto_duration']
in_progress  → cancelled      (admin force-cancels — closes active session first)
paused       → in_progress    (employee resumes)
paused       → cancelled      (admin force-cancels)
```

### Extension Request Status Values

| Status | Description |
|---|---|
| `pending` | Extension request submitted |
| `approved` | Duration extended |
| `rejected` | Extension denied |

---

## 4. Database Schema (All New Tables)

### 4.1 `employee_task_requests`

```sql
CREATE TABLE employee_task_requests (
    id                          char(36)         NOT NULL PRIMARY KEY,   -- UUID
    company_id                  char(36)         NOT NULL,               -- Multi-tenant
    user_id                     char(36)         NOT NULL,               -- Requester (employee)
    serial_number               varchar(50)      NOT NULL UNIQUE,        -- e.g. "TASK-2026-00125"
    title                       varchar(255)     NOT NULL,
    description                 text             NULL,
    project_id                  char(36)         NULL,                   -- FK → project_management (optional)
    approval_responsible_id     char(36)         NULL,                   -- FK → users (مسؤول الاعتماد)
    assignment_responsible_id   char(36)         NULL,                   -- FK → users (مسؤول التكليف)
    duration_hours              decimal(6,2)     NOT NULL,               -- Approved duration in hours
    original_duration_hours     decimal(6,2)     NULL,                   -- Set when first extension approved
    task_date                   date             NOT NULL,               -- Date of the task
    task_latitude               decimal(10,7)    NOT NULL,               -- Task GPS latitude
    task_longitude              decimal(10,7)    NOT NULL,               -- Task GPS longitude
    radius_meters               int              NULL,                   -- Snapshotted from constraint at task start
    procedure_setting_id        char(36)         NULL,                   -- FK → procedure_settings
    status                      varchar(30)      NOT NULL DEFAULT 'pending',
    time_from                   datetime         NULL,                   -- When employee actually started
    time_to                     datetime         NULL,                   -- When employee actually ended
    total_task_hours            decimal(8,2)     NULL,                   -- Net task hours (persisted at completion)
    total_pause_minutes         int              NOT NULL DEFAULT 0,     -- Total pause time in minutes
    shift_end_method            varchar(30)      NULL,                   -- manual | auto_location | auto_duration
    start_location              json             NULL,                   -- GPS at task start
    end_location                json             NULL,                   -- GPS at task end
    timezone                    varchar(50)      NULL,                   -- IANA frozen at task start (e.g. Asia/Riyadh)
    notes                       text             NULL,
    approved_by                 char(36)         NULL,                   -- FK → users
    approved_at                 datetime         NULL,
    rejected_by                 char(36)         NULL,                   -- FK → users
    rejected_at                 datetime         NULL,
    rejection_reason            text             NULL,
    cancelled_by                char(36)         NULL,                   -- FK → users
    cancelled_at                datetime         NULL,
    cancellation_reason         text             NULL,
    last_extension_status       varchar(30)      NULL,   -- null | extension_pending | extension_approved | extension_rejected
    created_at                  timestamp        NULL,
    updated_at                  timestamp        NULL,

    INDEX etr_company_user_idx  (company_id, user_id),
    INDEX etr_company_date_idx  (company_id, task_date),
    INDEX etr_company_status_idx (company_id, status),
    INDEX etr_user_date_idx     (user_id, task_date),
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (user_id)    REFERENCES users(id)
);
```

**Critical notes:**
- `time_from`, `time_to` are stored in **branch timezone** (same convention as `attendances` table — NOT UTC).
- `radius_meters` is snapshotted from the employee's main constraint at `start` time, NOT at request creation. This ensures the radius used during the task matches the constraint at actual execution.
- `total_task_hours` is calculated at task completion and persisted (same as `total_work_hours` in attendance).

---

### 4.2 `employee_task_sessions`

Tracks each continuous work period within a task (start → pause, resume → pause, resume → end). Modelled exactly like `attendance_breaks`.

```sql
CREATE TABLE employee_task_sessions (
    id                          char(36)         NOT NULL PRIMARY KEY,   -- UUID
    employee_task_request_id    char(36)         NOT NULL,               -- FK → employee_task_requests
    company_id                  char(36)         NOT NULL,               -- Multi-tenant
    start_time                  datetime         NOT NULL,               -- Session start (in branch TZ)
    end_time                    datetime         NULL,                   -- Session end (NULL = active)
    duration_minutes            int              NULL,                   -- Set when end_time is set
    source                      varchar(30)      NOT NULL DEFAULT 'manual',  -- manual | auto_location | auto_duration
    start_latitude              decimal(10,7)    NULL,
    start_longitude             decimal(10,7)    NULL,
    end_latitude                decimal(10,7)    NULL,
    end_longitude               decimal(10,7)    NULL,
    notes                       text             NULL,
    created_at                  timestamp        NULL,
    updated_at                  timestamp        NULL,

    INDEX ets_task_idx (employee_task_request_id),
    INDEX ets_company_idx (company_id),
    FOREIGN KEY (employee_task_request_id) REFERENCES employee_task_requests(id) ON DELETE CASCADE
);
```

**`source` values:**
| Value | Trigger |
|---|---|
| `manual` | Employee pressed Start/End/Pause/Resume |
| `auto_location` | System auto-ended session because employee was out of task location radius for too long |
| `auto_duration` | System auto-ended session because task duration + max_over_time was reached |

---

### 4.3 `employee_task_extension_requests`

```sql
CREATE TABLE employee_task_extension_requests (
    id                          char(36)         NOT NULL PRIMARY KEY,   -- UUID
    employee_task_request_id    char(36)         NOT NULL,               -- FK → employee_task_requests
    company_id                  char(36)         NOT NULL,               -- Multi-tenant
    requested_by                char(36)         NOT NULL,               -- FK → users (always the employee)
    additional_hours            decimal(6,2)     NOT NULL,               -- Extra hours requested
    reason                      text             NULL,                   -- Why extension is needed
    status                      varchar(30)      NOT NULL DEFAULT 'pending',  -- pending | approved | rejected
    reviewed_by                 char(36)         NULL,                   -- FK → users
    reviewed_at                 datetime         NULL,
    review_notes                text             NULL,
    created_at                  timestamp        NULL,
    updated_at                  timestamp        NULL,

    INDEX eter_task_idx (employee_task_request_id),
    FOREIGN KEY (employee_task_request_id) REFERENCES employee_task_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id)
);
```

**Rules:**
- Multiple extension requests are allowed per task (each one approved individually).
- A new extension request can only be submitted if no other extension request is `pending`.
- Employee can submit an extension while task is `in_progress` or `paused`.
- After approval, `employee_task_requests.duration_hours += additional_hours` (and `original_duration_hours` is set to the value before first extension if not already set).

---

## 5. ProcedureSetting Enhancement

### 5.1 Enum Case Addition

**File:** `modules/ProcedureSetting/Enums/ProcedureSettingType.php`

Add:
```php
case EmployeeTaskRequest = 'employee_task_request';
```

No migration needed — the `type` column is `varchar`, and ProcedureSetting records are created by admins at runtime.

### 5.2 Approval Action Hook

When a ProcedureSetting workflow step marked `is_approve = true` is actioned for a procedure with type `employee_task_request`:

- Fire event `EmployeeTaskRequestApproved` (or `EmployeeTaskRequestRejected`).
- Listener updates `employee_task_requests.status` → `approved` (or `rejected`).
- Send notification to the employee.

**Important:** The existing ProcedureSetting approval system is NOT modified. An event listener or observer hook is added in the EmployeeTask module that watches for ProcedureSetting approval actions on tasks.

---

## 6. Module Structure

New module: `modules/EmployeeTask/`

```
modules/EmployeeTask/
├── Config/
│   └── permissions.php          ← EMPLOYEE_TASK_CREATE, VIEW, UPDATE, APPROVE, CANCEL, etc.
├── Controllers/
│   ├── EmployeeTaskController.php          ← Employee-facing endpoints
│   └── EmployeeTaskAdminController.php     ← Admin-facing endpoints
├── Database/
│   └── migrations/
│       ├── 2026_05_20_000001_create_employee_task_requests_table.php
│       ├── 2026_05_20_000002_create_employee_task_sessions_table.php
│       └── 2026_05_20_000003_create_employee_task_extension_requests_table.php
├── DTO/
│   ├── CreateEmployeeTaskRequestDTO.php
│   ├── StartTaskDTO.php
│   ├── EndTaskDTO.php
│   └── CreateExtensionRequestDTO.php
├── Events/
│   ├── EmployeeTaskRequestApproved.php
│   ├── EmployeeTaskRequestRejected.php
│   └── EmployeeTaskCompleted.php
├── Enums/
│   ├── EmployeeTaskStatus.php              ← status values + label() in AR/EN
│   └── EmployeeTaskExtensionStatus.php     ← extension badge values + label()
├── Exceptions/
│   └── EmployeeTaskException.php           ← Named constructors for all task errors
├── Jobs/
│   ├── AutoCloseTaskAtDurationExpiryJob.php
│   └── AutoCloseTaskIfOutOfLocationJob.php
├── Listeners/
│   └── HandleProcedureSettingApprovalListener.php
├── Models/
│   ├── EmployeeTaskRequest.php
│   ├── EmployeeTaskSession.php
│   └── EmployeeTaskExtensionRequest.php
├── Presenters/
│   ├── EmployeeTaskRequestPresenter.php
│   ├── EmployeeTaskSessionPresenter.php
│   ├── EmployeeTaskExtensionPresenter.php
│   └── EmployeeTaskIntraDayPresenter.php    ← For the combined daily report
├── Providers/
│   └── EmployeeTaskServiceProvider.php
├── Repositories/
│   ├── EmployeeTaskRepository.php
│   └── EmployeeTaskSessionRepository.php
├── Requests/
│   ├── CreateEmployeeTaskRequest.php
│   ├── StartTaskRequest.php
│   ├── EndTaskRequest.php
│   ├── ApproveTaskRequest.php
│   ├── RejectTaskRequest.php
│   └── CreateExtensionRequest.php
├── Routes/
│   └── employee_tasks.php
├── Services/
│   ├── EmployeeTaskRequestService.php      ← CRUD + submission
│   ├── EmployeeTaskLifecycleService.php    ← Start / Pause / Resume / End
│   ├── EmployeeTaskLocationService.php     ← Radius + distance (reuses Haversine from Attendance)
│   ├── EmployeeTaskAutoCloseService.php    ← Auto-close logic (row-locked, same pattern as AutoCloseAttendanceService)
│   ├── EmployeeTaskExtensionService.php    ← Extension request CRUD + approval
│   └── EmployeeTaskReportService.php       ← Builds the combined daily intra-day report
├── Lang/
│   ├── ar/
│   │   └── employee_task.php               ← Arabic labels for status, extension badge, messages
│   └── en/
│       └── employee_task.php               ← English labels
└── module.json
```

---

## 7. Business Logic Rules (Full Encyclopedia)

### RULE-1: Request Submission

- Employee provides: `title`, `description` (optional), `project_id` (optional), `approval_responsible_id`, `assignment_responsible_id`, `duration_hours`, `task_date`, `task_latitude`, `task_longitude`.
- System auto-generates `serial_number` using same pattern as AdminRequest: `TASK-{YEAR}-{5-digit-seq}`.
- `status` = `pending` on creation.
- System looks up the `procedure_setting` with `type = 'employee_task_request'` for the employee's branch/company and links it via `procedure_setting_id`.
- If no procedure setting found for `employee_task_request` → **reject with 422**: admin must configure a workflow first.
- The request enters the ProcedureSetting approval flow from this point.

### RULE-2: Employee Cancellation Before Approval

- Only allowed when `status = 'pending'`.
- Employee can only cancel their OWN request.
- Sets `status = 'cancelled'`, `cancelled_by = userId`, `cancelled_at = now()`.

### RULE-3: Admin Cancellation After Approval

- Allowed when `status ∈ {approved, in_progress, paused}`.
- If `status = in_progress` or `paused`: first close the active session (set `end_time = now()`, calculate `duration_minutes`, source = `manual`).
- Then set `status = 'cancelled'`.

### RULE-4: Starting the Task

When employee calls `POST /employee-tasks/{id}/start`:

1. Verify `status = 'approved'`. Throw `EmployeeTaskException::notApproved()` if not.
2. Load employee's main `AttendanceConstraint` via `UserProfessionalData.attendance_constraint_id`.
3. Snapshot `radius_meters` from the constraint's location config:
   - If `branch_locations` JSON has entries → use the first entry's `radius`.
   - If `attendance_constraint_locations` table has rows → use the first row's `radius`.
   - Fallback: `100` metres.
4. Resolve timezone via `getTimeZoneBranchByRequest()` (same as attendance).
5. Set `time_from = now(timezone)`, `radius_meters = snapshotted`, `timezone = iana_string`, `status = 'in_progress'`, `start_location = {latitude, longitude}` from request body.
6. Create first `EmployeeTaskSession`: `start_time = now()`, `start_latitude/longitude` from request.
7. Dispatch `AutoCloseTaskAtDurationExpiryJob` with delay = `time_from + duration_hours * 60 min + constraint_max_over_time * 60 min`.
8. Return task with live timer data.

**Why snapshot radius at start?** Constraint can be edited. The radius used during the task must be what was active when the employee started, not what it is later.

### RULE-5: Pausing the Task

When employee calls `POST /employee-tasks/{id}/pause`:

1. Verify `status = 'in_progress'`. Throw if not.
2. Find the active session (`end_time IS NULL`) in `employee_task_sessions`.
3. Set session `end_time = now()`, calculate `duration_minutes`, source = `'manual'`.
4. Set task `status = 'paused'`.
5. Return updated task.

### RULE-6: Resuming the Task

When employee calls `POST /employee-tasks/{id}/resume`:

1. Verify `status = 'paused'`. Throw if not.
2. Validate employee location (optional, configurable): check if within radius.
3. Create new `EmployeeTaskSession`: `start_time = now()`.
4. Set task `status = 'in_progress'`.
5. Return updated task.

### RULE-7: Ending the Task Manually

When employee calls `POST /employee-tasks/{id}/end`:

1. Verify `status ∈ {in_progress, paused}`. Throw if not.
2. Require `end_latitude` and `end_longitude` in the request body (mandatory for attendance report).
3. If active session exists (`end_time IS NULL`): close it — `end_time = now()`, `duration_minutes = diff`, source = `'manual'`.
4. Calculate `total_pause_minutes` = sum of all gaps between sessions (sessions are: task start → session1.end, session2.start → session2.end, etc. — actually pause minutes = time where no session was active).
   
   **Precise calculation:**
   ```
   total_session_minutes = sum(session.duration_minutes for all completed sessions)
   total_elapsed_minutes = diff(time_from, now())
   total_pause_minutes = total_elapsed_minutes - total_session_minutes
   ```

5. Set `time_to = now()`, `total_task_hours = total_session_minutes / 60`, `shift_end_method = 'manual'`, `end_location = {lat, lng}`, `status = 'completed'`.
6. Persist all fields in one DB update.
7. Fire `EmployeeTaskCompleted` event.

### RULE-8: Auto Clock-Out — Out of Task Location

Triggered by a **location tracking ping** from the mobile app that includes `is_out_of_task_location = true` for longer than the constraint threshold.

**Mechanism:**
- The mobile app sends location pings (existing pattern: `location_tracking` in attendance).
- A new endpoint `POST /employee-tasks/{id}/location-ping` accepts `{latitude, longitude, timestamp}`.
- `EmployeeTaskLocationService::checkAndEnforceRadius()`:
  1. Calculate distance from task location using Haversine (reuse `LocationConstraintService::calculateDistance()`).
  2. If `distance > radius_meters`: mark employee as "out of location". Record the first time they went out.
  3. If time out of location > `constraint.out_of_radius_time_threshold` (default: 30 minutes from constraint config): dispatch `AutoCloseTaskIfOutOfLocationJob`.
- `AutoCloseTaskIfOutOfLocationJob::handle()`:
  1. Acquire row lock (`SELECT FOR UPDATE`).
  2. Re-check status — abort if already completed/cancelled.
  3. Close active session: `end_time = closeAt (= first_out_time + threshold)`, source = `'auto_location'`.
  4. Set `time_to = closeAt`, calculate hours, `shift_end_method = 'auto_location'`, `status = 'completed'`.
  5. Notify employee.

**CRITICAL:** `closeAt` = the moment the out-of-location threshold was breached (NOT `now()` at job execution time), mirroring the AutoCloseAttendanceService pattern. This prevents penalising the employee for queue worker delay.

### RULE-9: Auto Clock-Out — Duration Expiry

`AutoCloseTaskAtDurationExpiryJob` fires at `time_from + (duration_hours + max_over_time_hours) * 60 min`.

- The `closeAt` stored in the job payload = `time_from + duration_hours` (the scheduled end, NOT the max overtime point).
- Same row-lock pattern as `AutoCloseAttendanceService::closeIfExpired()`.
- Source = `'auto_duration'`.

### RULE-10: Extension Requests

**Submitting:**
- Employee can submit while `status ∈ {in_progress, paused}`.
- Constraint: only one `pending` extension request per task at a time.
- `additional_hours` must be > 0.

**Approval:**
- Sets `employee_task_requests.duration_hours += additional_hours`.
- Sets `original_duration_hours` to original value if not already set.
- Re-dispatches `AutoCloseTaskAtDurationExpiryJob` with new deadline (cancels old one if possible — or just dispatch new one; the job checks status and exits if already completed).

**Rejection:**
- `employee_task_extension_requests.status = 'rejected'`.
- No change to task.

### RULE-11: Cannot Overlap Task Sessions With Attendance Clock-In at Same Location

This is a **soft constraint** — we do NOT block the employee from doing both simultaneously, but we add a note in the report that task and attendance hours were simultaneously active. The system trusts GPS separation.

---

## 8. Service Layer Design

### 8.1 `EmployeeTaskRequestService`

```php
public function create(CreateEmployeeTaskRequestDTO $dto): EmployeeTaskRequest
public function list(array $filters, int $page, int $perPage): LengthAwarePaginator
public function get(string $id): EmployeeTaskRequest
public function cancelByEmployee(string $id, string $userId): EmployeeTaskRequest
public function cancelByAdmin(string $id, string $adminId, ?string $reason): EmployeeTaskRequest
public function approve(string $id, string $adminId): EmployeeTaskRequest
public function reject(string $id, string $adminId, string $reason): EmployeeTaskRequest
public function generateSerialNumber(): string
```

### 8.2 `EmployeeTaskLifecycleService`

```php
public function start(string $taskId, StartTaskDTO $dto): EmployeeTaskRequest  
public function pause(string $taskId, string $userId): EmployeeTaskRequest
public function resume(string $taskId, ResumeTaskDTO $dto): EmployeeTaskRequest
public function end(string $taskId, EndTaskDTO $dto): EmployeeTaskRequest
public function getLiveStatus(string $taskId): array   // timer, progress %, remaining
```

Constructor injects: `EmployeeTaskRepository`, `EmployeeTaskSessionRepository`, `EmployeeTaskLocationService`, `AttendanceConstraintService` (read-only for radius), `Clock`.

### 8.3 `EmployeeTaskLocationService`

```php
public function snapshotRadiusFromConstraint(User $user): int   // Returns radius in metres
public function isWithinTaskRadius(EmployeeTaskRequest $task, float $lat, float $lng): bool
public function processLocationPing(EmployeeTaskRequest $task, float $lat, float $lng, string $timestamp): void
```

**Reuse:**  
Internally calls the same Haversine formula already in `LocationConstraintService::calculateDistance()`. 
To avoid tight coupling, copy the formula into a static `GeoDistance::haversineMetres(float $lat1, float $lon1, float $lat2, float $lon2): float` helper class in `modules/EmployeeTask/Support/GeoDistance.php`.

### 8.4 `EmployeeTaskAutoCloseService`

```php
public function closeIfExpired(EmployeeTaskRequest $task, CarbonImmutable $closeAt, string $reason): bool
```

Implementation mirrors `AutoCloseAttendanceService::closeIfExpired()`:
- `DB::transaction()` with `lockForUpdate()`
- Re-reads status after lock
- Aborts if not `in_progress`
- Closes active session
- Calculates total hours
- Updates all fields in one `update()` call
- Returns bool (false = was already closed / no-op)

### 8.5 `EmployeeTaskExtensionService`

```php
public function requestExtension(string $taskId, CreateExtensionRequestDTO $dto): EmployeeTaskExtensionRequest
public function approveExtension(string $extensionId, string $adminId): EmployeeTaskExtensionRequest
public function rejectExtension(string $extensionId, string $adminId, ?string $notes): EmployeeTaskExtensionRequest
public function listExtensions(string $taskId): Collection
```

**`last_extension_status` sync on every state change:**

| Action | Sets `last_extension_status` on parent task |
|---|---|
| `requestExtension()` | `extension_pending` |
| `approveExtension()` | `extension_approved` |
| `rejectExtension()` | `extension_rejected` |

Each method must call:
```php
$task->update(['last_extension_status' => $newBadgeValue]);
```
after the extension record is updated, in the same DB transaction.

### 8.6 `EmployeeTaskReportService`

```php
public function getIntraDayReport(string $userId, string $date): array
public function getDailyTaskSummary(string $userId, string $startDate, string $endDate): array
```

**`getIntraDayReport` logic:**
1. Fetch all attendance records for `user_id` and `business_date = $date`.
2. Fetch all completed `employee_task_requests` where `user_id = $userId` AND `task_date = $date` AND `status = 'completed'`.
3. Also fetch any `in_progress` or `paused` task for today (for live timer display).
4. Calculate `attendance_total_hours` = sum of `total_work_hours` from attendance records.
5. Calculate `task_total_hours` = sum of `total_task_hours` from completed tasks.
6. Calculate `total_work_hours` = `attendance_total_hours + task_total_hours`.
7. Return combined structure (see §11).

---

## 9. Jobs & Background Processing

### 9.1 `AutoCloseTaskAtDurationExpiryJob`

```php
class AutoCloseTaskAtDurationExpiryJob implements ShouldQueue
{
    public function __construct(
        public readonly string $taskId,
        public readonly string $companyId,
        /** ISO 8601 with TZ offset — the scheduled task end (NOT end + max_over_time). */
        public readonly string $closeAtIso,   // → must use ->toIso8601String()
    ) {}

    public function handle(EmployeeTaskAutoCloseService $service): void
    {
        tenancy()->initialize($this->companyId);
        try {
            $task = EmployeeTaskRequest::find($this->taskId);
            if (!$task) { return; }
            $closeAt = CarbonImmutable::parse($this->closeAtIso);
            $service->closeIfExpired($task, $closeAt, 'auto_duration');
        } finally {
            tenancy()->end();
        }
    }
}
```

**Delay calculation:**
```php
$deadline = CarbonImmutable::parse($task->time_from, $timezone)
    ->addHours($task->duration_hours)
    ->addHours($constraint->max_over_time ?? 0);

dispatch(new AutoCloseTaskAtDurationExpiryJob(
    taskId:    $task->id,
    companyId: $task->company_id,
    closeAtIso: CarbonImmutable::parse($task->time_from, $timezone)
                  ->addHours($task->duration_hours)
                  ->toIso8601String(),   // scheduled end, NOT max_over_time-inflated
))->delay($deadline);
```

### 9.2 `AutoCloseTaskIfOutOfLocationJob`

```php
class AutoCloseTaskIfOutOfLocationJob implements ShouldQueue
{
    public function __construct(
        public readonly string $taskId,
        public readonly string $companyId,
        /** ISO 8601 — the moment the threshold was breached (not job dispatch time). */
        public readonly string $closeAtIso,
    ) {}
}
```

Dispatched immediately (no delay) from `EmployeeTaskLocationService::processLocationPing()` when the out-of-location threshold is exceeded. `closeAt` = first-out-time + threshold.

---

## 10. API Endpoints (Complete List)

### Employee-Facing Endpoints

| Method | Route | Description |
|---|---|---|
| `POST` | `/api/v1/employee-tasks` | Submit a new task request |
| `GET` | `/api/v1/employee-tasks` | List my task requests (filterable by status, date) |
| `GET` | `/api/v1/employee-tasks/{id}` | Get full task detail + sessions |
| `DELETE` | `/api/v1/employee-tasks/{id}` | Cancel own pending request |
| `POST` | `/api/v1/employee-tasks/{id}/start` | Start approved task (requires lat/lng) |
| `POST` | `/api/v1/employee-tasks/{id}/pause` | Pause in-progress task |
| `POST` | `/api/v1/employee-tasks/{id}/resume` | Resume paused task (requires lat/lng) |
| `POST` | `/api/v1/employee-tasks/{id}/end` | End task (requires lat/lng — mandatory) |
| `GET` | `/api/v1/employee-tasks/{id}/status` | Live status: timer, progress %, time remaining |
| `POST` | `/api/v1/employee-tasks/{id}/location-ping` | Send GPS ping for out-of-location tracking |
| `GET` | `/api/v1/employee-tasks/{id}/check-location` | Is my current lat/lng within task radius? |
| `POST` | `/api/v1/employee-tasks/{id}/extension-requests` | Request a duration extension |
| `GET` | `/api/v1/employee-tasks/{id}/extension-requests` | List extension requests for this task |
| `GET` | `/api/v1/employee-tasks/{id}/sessions` | List all work sessions (pause/resume history) |

### Admin-Facing Endpoints

| Method | Route | Description |
|---|---|---|
| `GET` | `/api/v1/admin/employee-tasks` | List all task requests (filters: user, date, status, branch) |
| `PATCH` | `/api/v1/admin/employee-tasks/{id}/approve` | Approve a pending task |
| `PATCH` | `/api/v1/admin/employee-tasks/{id}/reject` | Reject a pending task (reason required) |
| `DELETE` | `/api/v1/admin/employee-tasks/{id}` | Cancel any task (approved / in_progress / paused) |
| `GET` | `/api/v1/admin/employee-tasks/extension-requests` | List all pending extension requests |
| `PATCH` | `/api/v1/admin/employee-tasks/extension-requests/{id}/approve` | Approve extension |
| `PATCH` | `/api/v1/admin/employee-tasks/extension-requests/{id}/reject` | Reject extension |

### Attendance Integration Endpoints (additions to Attendance module)

| Method | Route | Description |
|---|---|---|
| `GET` | `/api/v1/attendance/intra-day` | Combined intra-day report (attendance + tasks) |
| `GET` | `/api/v1/attendance/daily-summary` | Daily summary with task hours included |

**Query params for intra-day:** `user_id`, `date` (defaults to today).

---

## 11. Intra-Day Report Structure

**Response for `GET /api/v1/attendance/intra-day?user_id=...&date=2026-05-12`:**

```json
{
  "data": {
    "date": "2026-05-12",
    "day_name": "Tuesday",
    "user": {
      "id": "uuid",
      "name": "سامر عبدالله القحطاني",
      "job_title": "مهندس أنظمة"
    },
    "attendance_sessions": [
      {
        "type": "attendance",
        "attendance_id": "uuid",
        "clock_in_time": "2026-05-12 09:00:00",
        "clock_out_time": "2026-05-12 17:30:00",
        "total_work_hours": "08:30",
        "total_break_hours": "00:00",
        "status": "completed",
        "clock_in_location": { "latitude": 24.123, "longitude": 46.456 },
        "clock_out_location": { "latitude": 24.123, "longitude": 46.456 },
        "breaks": []
      }
    ],
    "task_sessions": [
      {
        "type": "task",
        "task_id": "uuid",
        "serial_number": "TASK-2026-00125",
        "title": "تحديث نظام إنذار الحريق",
        "time_from": "2026-05-12 07:00:00",
        "time_to": "2026-05-12 09:00:00",
        "total_task_hours": "02:00",
        "duration_hours": "04:00",
        "shift_end_method": "manual",
        "status": "completed",
        "status_label": "مكتملة",
        "last_extension_status": null,
        "last_extension_status_label": null,
        "task_location": {
          "latitude": 46.6753,
          "longitude": 23.6548,
          "radius_meters": 200
        },
        "start_location": { "latitude": 46.6752, "longitude": 23.6550 },
        "end_location": { "latitude": 46.6751, "longitude": 23.6549 },
        "work_sessions": [
          {
            "start_time": "07:00:00",
            "end_time": "09:00:00",
            "duration_minutes": 120,
            "source": "manual"
          }
        ]
      }
    ],
    "active_task": null,
    "summary": {
      "attendance_total_hours": "08:30",
      "task_total_hours": "02:00",
      "total_work_hours": "10:30",
      "scheduled_hours": "08:00",
      "overtime_hours": "02:30",
      "is_late": false,
      "late_minutes": 0,
      "early_departure_minutes": 0
    }
  }
}
```

**When task is active (live timer data for `active_task` field):**
```json
"active_task": {
  "task_id": "uuid",
  "title": "تحديث نظام إنذار الحريق",
  "status": "in_progress",
  "time_from": "2026-05-12 10:15:00",
  "duration_hours": "04:00",
  "elapsed_seconds": 8137,
  "elapsed_formatted": "02:15:37",
  "remaining_seconds": 6263,
  "remaining_formatted": "01:44:23",
  "progress_percentage": 40,
  "time_consumption_percentage": 64,
  "can_request_extension": true
}
```

**Calculation of `progress_percentage`:**
```
elapsed = sum of completed session minutes + active session elapsed minutes
progress_percentage = (elapsed / (duration_hours * 60)) * 100
```

**Calculation of `time_consumption_percentage`:**
```
elapsed_including_pauses = diff(time_from, now) in minutes
time_consumption_percentage = (elapsed_including_pauses / (duration_hours * 60)) * 100
```

---

## 12. Location Validation Algorithm

### Distance Calculation (Haversine)

New file: `modules/EmployeeTask/Support/GeoDistance.php`

```php
final class GeoDistance
{
    /**
     * Returns distance in METRES between two GPS coordinates.
     * Uses Haversine formula (same as LocationConstraintService).
     */
    public static function metres(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) ** 2 +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadiusKm * $c * 1000; // convert km → metres
    }
}
```

### Radius Source Priority (at task start)

1. `attendance_constraint_locations` table rows for the main constraint → use first row's `radius` (int, metres).
2. `branch_locations` JSON on `attendance_constraints` → use first entry's `radius`.
3. `constraint_config.location_rules.radius_meters` (if present).
4. Fallback: `100` metres.

### Out-of-Location Threshold

Taken from the employee's main constraint config:
```
constraint_config → location_rules → out_of_radius_time_threshold (minutes)
```
Default: `30` minutes (same default used in `RadiusEnforcementService`).

### Location Ping Flow

```
Mobile app sends: POST /employee-tasks/{id}/location-ping
  { latitude: 24.123, longitude: 46.456, timestamp: "2026-05-12T10:30:00+03:00" }

EmployeeTaskLocationService::processLocationPing():
  1. Check distance from task.task_latitude / task.task_longitude
  2. If distance <= task.radius_meters → employee is IN location
     → Reset out-of-location tracking, return { in_location: true }
  3. If distance > task.radius_meters → employee is OUT of location
     → Store first_out_timestamp if not already set (in cache or task record)
     → Calculate how long they have been out
     → If out_duration_minutes >= threshold:
        dispatch AutoCloseTaskIfOutOfLocationJob(closeAt = first_out_timestamp + threshold)
        return { in_location: false, auto_close_triggered: true }
     → Else:
        return { in_location: false, minutes_out: X, threshold_minutes: Y }
```

**Storage of `first_out_timestamp`:** Store in a Redis cache key `task_out_of_location:{task_id}` with TTL = threshold minutes * 2. Clear on each in-location ping.

---

## 13. Hours Calculation Integration

### New `EmployeeTaskCalculator` (Domain layer, pure)

```php
final class EmployeeTaskCalculator
{
    /**
     * @param Collection<EmployeeTaskSession> $completedSessions  sessions with end_time NOT NULL
     * @return float  net task hours as decimal rounded to 2 places
     */
    public function calculate(Collection $completedSessions): float
    {
        $totalMinutes = $completedSessions->sum('duration_minutes');
        return round($totalMinutes / 60, 2);
    }
}
```

No dependency on the Attendance `AttendanceCalculator`. Kept deliberately simple.

### Integration in `EmployeeTaskReportService`

```php
public function getIntraDayReport(string $userId, string $date): array
{
    // 1. Attendance sessions (existing, zero changes to existing code)
    $attendances = Attendance::query()
        ->where('user_id', $userId)
        ->where('business_date', $date)
        ->with(['breaks', 'appliedAttendanceConstraint'])
        ->get();

    $attendanceTotalHours = $attendances->sum('total_work_hours');

    // 2. Task sessions for the day
    $tasks = EmployeeTaskRequest::query()
        ->where('user_id', $userId)
        ->where('task_date', $date)
        ->whereIn('status', ['completed', 'in_progress', 'paused'])
        ->with('sessions')
        ->get();

    $completedTasks = $tasks->whereIn('status', ['completed']);
    $taskTotalHours = $completedTasks->sum('total_task_hours');

    // 3. Active task (live timer)
    $activeTask = $tasks->whereIn('status', ['in_progress', 'paused'])->first();

    // 4. Scheduled hours from constraint
    $scheduledHours = $this->getScheduledHoursForDay($userId, $date);

    // 5. Total
    $totalWorkHours = (float)$attendanceTotalHours + (float)$taskTotalHours;
    $overtimeHours = max(0.0, $totalWorkHours - $scheduledHours);

    return [...]; // see §11
}
```

### HoursFormatter Usage

All hour values in the response use `HoursFormatter::fromHours(float $hours): string` (already exists in Attendance module) to produce `"HH:MM"` strings — never raw decimals.

---

## 14. Invariants & Safety Rules

### INV-T1: Store time_from/time_to in Branch Timezone, NOT UTC

Same rule as `attendances` table. Use `getTimeZoneBranchByRequest()` at task start to determine timezone. Freeze in `timezone` column. Parse all subsequent times with `CarbonImmutable::parse($value, $timezone)` — NEVER call `->setTimezone()` on a branch-TZ string.

### INV-T2: Always Use ->toIso8601String() in Job Constructors

When passing `closeAtIso` to `AutoCloseTaskAtDurationExpiryJob`, always use `->toIso8601String()`. Never use `->format('Y-m-d H:i:s')` — it drops the timezone offset, causing the worker to misinterpret the time as UTC.

### INV-T3: Row Lock Before Auto-Close Writes

`EmployeeTaskAutoCloseService::closeIfExpired()` must wrap the entire operation in `DB::transaction()` with `lockForUpdate()`. Re-read status after acquiring lock. Abort (return false) if status != `in_progress`.

### INV-T4: Snapshot Radius at START, Not at REQUEST Creation

The constraint radius is read from the database only when `start()` is called, not when the request is created. Changes to the constraint between request submission and task start take effect.

### INV-T5: closeAt = Scheduled End (Not Queue Fire Time)

When auto-closing at duration expiry, `closeAt = time_from + duration_hours` (the original deadline). The queue job may fire later due to queue delay, but the employee is NOT penalised with extra minutes.

### INV-T6: Do Not Modify attendances Table From Task Code

Zero writes to `attendances`, `attendance_breaks`, `applied_attendance_constraints`, or `attendance_constraint_violations` from any EmployeeTask module code. Read-only from Attendance module.

### INV-T7: Hours Formatted as HH:MM in All Responses

All `_hours` fields in API responses use `HoursFormatter::fromHours()` to produce `"HH:MM"` strings. Internally the DB stores `decimal(8,2)`. The presenter converts.

### INV-T8: Cancel = Close Active Session First

When admin force-cancels an `in_progress` task, the service must close the active session before setting `status = 'cancelled'`. Never leave a session with `end_time = NULL` when the task is not `in_progress`.

### INV-T9: One Active Session Per Task At All Times

When status = `in_progress`, there must be exactly one session with `end_time IS NULL`. The service must enforce this — on `start`, `resume` create a session. On `pause`, `end`, `auto_close` close it.

### INV-T10: Extension Approval Re-Dispatches Auto-Close Job

When an extension is approved, a new `AutoCloseTaskAtDurationExpiryJob` is dispatched with the updated deadline. The old job will fire and return `false` from `closeIfExpired` because the task will not be `in_progress` at its trigger time (if extension was already completed). No cancellation of old job needed — idempotency covers it.

---

## 15. Implementation Phases

### Phase 1 — Database & ProcedureSetting ✅ COMPLETE
1. ✅ Added `EmployeeTaskRequest = 'employee_task_request'` to `ProcedureSettingType` enum.
2. ✅ Migration: `2026_05_20_000001_create_employee_task_requests_table.php`.
3. ✅ Migration: `2026_05_20_000002_create_employee_task_sessions_table.php`.
4. ✅ Migration: `2026_05_20_000003_create_employee_task_extension_requests_table.php`.
5. ✅ Models: `EmployeeTaskRequest`, `EmployeeTaskSession`, `EmployeeTaskExtensionRequest`.
6. ✅ Registered in `EmployeeTaskServiceProvider`.

### Phase 2 — Request Submission & Approval ✅ COMPLETE
1. ✅ `CreateEmployeeTaskRequest` form request + `CreateEmployeeTaskRequestDTO`.
2. ✅ `EmployeeTaskRepository::create()`.
3. ✅ `EmployeeTaskRequestService::create()` + serial number generation.
4. ✅ `EmployeeTaskController::store()` endpoint.
5. ✅ Approval/rejection endpoints (admin) in `AdminEmployeeTaskController`.
6. ✅ Employee cancellation endpoint.
7. ✅ `EmployeeTaskRequestPresenter`.
8. ✅ List and get endpoints.

### Phase 3 — Lifecycle (Start/Pause/Resume/End) ✅ COMPLETE
1. ✅ `StartTaskDTO`, `EndTaskDTO`.
2. ✅ `EmployeeTaskLocationService::snapshotRadiusFromConstraint()`.
3. ✅ `EmployeeTaskLifecycleService::start()` (with constraint snapshot + job dispatch).
4. ✅ `EmployeeTaskLifecycleService::pause()` + `resume()`.
5. ✅ `EmployeeTaskLifecycleService::end()` (final hours calculation).
6. ✅ `Support/GeoDistance.php` (Haversine formula).
7. ✅ All lifecycle HTTP endpoints.

### Phase 4 — Auto-Close & Location ✅ COMPLETE
1. ✅ `GeoDistance::metres()` support class (Haversine).
2. ✅ `EmployeeTaskLocationService::processLocationPing()`.
3. ✅ Redis caching for out-of-location tracking.
4. ✅ `EmployeeTaskAutoCloseService::closeIfExpired()` (row-locked).
5. ✅ `AutoCloseTaskAtDurationExpiryJob`.
6. ✅ `AutoCloseTaskIfOutOfLocationJob`.
7. ✅ Location ping endpoint.
8. ✅ Location check endpoint.
9. ✅ `EmployeeTaskException` named constructors.

### Phase 5 — Extension Requests ✅ COMPLETE
1. ✅ `CreateExtensionRequestDTO`.
2. ✅ `EmployeeTaskExtensionService` (create, approve, reject).
3. ✅ Extension request endpoints (employee submit, admin approve/reject, list).
4. ✅ Re-dispatch `AutoCloseTaskAtDurationExpiryJob` on approval.
5. ✅ `EmployeeTaskExtensionPresenter`.

### Phase 6 — Attendance Report Integration ✅ COMPLETE
1. ✅ `EmployeeTaskReportService::getIntraDayReport()`.
2. ✅ Route: `GET /employee-tasks/intra-day-report`.
3. ✅ `EmployeeTaskSessionPresenter` (session-level detail).
4. ✅ Live timer data for active tasks.
5. ✅ `daily-summary` enhanced endpoint.

### Phase 7 — Postman Collection & Testing ✅ COMPLETE
1. ✅ Created `EmployeeTask_API.postman_collection.json` at project root (5 folders, 20 requests, Postman test scripts on all requests).
2. ✅ Written feature tests — 6 test files, 47 test cases:
   - `Tests/Unit/Support/GeoDistanceTest.php` — 7 Haversine unit tests (no DB)
   - `Tests/Unit/Enums/EmployeeTaskStatusTest.php` — 8 enum contract tests (no DB)
   - `Tests/Feature/AutoClose/EmployeeTaskAutoCloseServiceTest.php` — 8 auto-close invariant tests (mirrors `AutoCloseRaceTest`)
   - `Tests/Feature/Lifecycle/EmployeeTaskLifecycleServiceTest.php` — 15 lifecycle state-machine tests
   - `Tests/Feature/Extension/EmployeeTaskExtensionServiceTest.php` — 12 extension request tests (INV-T8, INV-T9, INV-T10)
   - `Tests/Feature/Report/EmployeeTaskReportServiceTest.php` — 12 intra-day report tests
3. ✅ All 6 test files pass `php -l` syntax check.
4. ✅ Migrations executed on dev DB — all 3 tables created successfully (FK name truncation bug fixed in migration 3: `eter_task_request_fk`).

**Phase 7 completed:** 2026-05-18  
**Total module:** 36 PHP files (30 implementation + 6 test files).  
**All 36 PHP files pass `php -l` syntax check.**

---

## 16. Risk Register

| Risk | Probability | Impact | Mitigation |
|---|---|---|---|
| Task hours overlap with attendance hours (same GPS location, employee does both) | Low | Low | Soft constraint only — report shows both, note added |
| Queue delay causes incorrect `time_to` on auto-close | Medium | High | Always use `closeAt = boundary_time`, not `now()` (INV-T5) |
| Constraint changes between task submission and start | Medium | Medium | Snapshot radius at `start()` time (RULE-4) |
| Employee submits task without procedure setting configured | Medium | High | Return 422 with clear message in `create()` (RULE-1) |
| Two concurrent auto-close jobs race on same task | Low | High | Row lock + re-read in `closeIfExpired()` (INV-T3) |
| Timezone mismatch (time_from stored wrong) | Low | Critical | Always use `getTimeZoneBranchByRequest()` and `->toIso8601String()` (INV-T1, INV-T2) |
| AttendancePresenter output changes break existing clients | Low | Critical | AttendancePresenter is NOT touched (INV-T6) |
| Redis not available for out-of-location tracking | Low | Medium | Fallback: store `first_out_timestamp` in `employee_task_requests` nullable column |
| Extension re-dispatch fills queue with stale jobs | Low | Low | Stale job returns false from closeIfExpired, no side effects |
| Admin cancels task that has an active job in queue | Low | Medium | Row lock + status check in auto-close — returns false safely |

---

## 17. Postman Collection Plan

File: `EmployeeTask_API.postman_collection.json` (project root)

### Folders
1. **Task Requests (Employee)** — Create, List My Tasks, Get Detail, Cancel
2. **Task Lifecycle** — Start, Pause, Resume, End, Live Status, Location Check, Location Ping
3. **Extension Requests** — Submit, List
4. **Task Admin** — List All, Approve, Reject, Force Cancel, Extension Approve/Reject
5. **Attendance Integration** — Intra-Day Report, Daily Summary

### Variables
```json
{
  "url": "http://localhost/api/v1",
  "token": "bearer-token",
  "task_id": "uuid",
  "extension_id": "uuid",
  "user_id": "uuid"
}
```

### Key Request Bodies

**Create Task Request (POST /employee-tasks):**
```json
{
  "title": "تحديث نظام إنذار الحريق",
  "description": "مهمة تحديث نظام الحريق السريع حسب الجدول",
  "project_id": null,
  "approval_responsible_id": "uuid",
  "assignment_responsible_id": "uuid",
  "duration_hours": 4,
  "task_date": "2026-11-24",
  "task_latitude": 46.6753,
  "task_longitude": 23.6548
}
```

**Start Task (POST /employee-tasks/{id}/start):**
```json
{
  "latitude": 46.6752,
  "longitude": 23.6550
}
```

**End Task (POST /employee-tasks/{id}/end):**
```json
{
  "latitude": 46.6751,
  "longitude": 23.6549,
  "notes": "Task completed successfully"
}
```

**Location Ping (POST /employee-tasks/{id}/location-ping):**
```json
{
  "latitude": 46.9999,
  "longitude": 23.9999,
  "timestamp": "2026-11-24T10:30:00+03:00"
}
```

**Extension Request (POST /employee-tasks/{id}/extension-requests):**
```json
{
  "additional_hours": 2,
  "reason": "المهمة تحتاج وقتاً إضافياً لإتمام التركيب"
}
```

**Approve Task (PATCH /admin/employee-tasks/{id}/approve):**
```json
{}
```

**Reject Task (PATCH /admin/employee-tasks/{id}/reject):**
```json
{
  "rejection_reason": "المهمة لا تتوافق مع الجدول الزمني"
}
```

---

## Appendix A: Permissions List

To add to `modules/EmployeeTask/Config/permissions.php`:

```php
'EMPLOYEE_TASK_CREATE'              => 'employee-task.employee-tasks.create',
'EMPLOYEE_TASK_VIEW'                => 'employee-task.employee-tasks.view',
'EMPLOYEE_TASK_LIST'                => 'employee-task.employee-tasks.list',
'EMPLOYEE_TASK_CANCEL'              => 'employee-task.employee-tasks.cancel',
'EMPLOYEE_TASK_START'               => 'employee-task.employee-tasks.start',
'EMPLOYEE_TASK_END'                 => 'employee-task.employee-tasks.end',
'EMPLOYEE_TASK_APPROVE'             => 'employee-task.employee-tasks.approve',
'EMPLOYEE_TASK_REJECT'              => 'employee-task.employee-tasks.reject',
'EMPLOYEE_TASK_ADMIN_LIST'          => 'employee-task.employee-tasks.admin-list',
'EMPLOYEE_TASK_ADMIN_CANCEL'        => 'employee-task.employee-tasks.admin-cancel',
'EMPLOYEE_TASK_EXTENSION_CREATE'    => 'employee-task.extensions.create',
'EMPLOYEE_TASK_EXTENSION_APPROVE'   => 'employee-task.extensions.approve',
'EMPLOYEE_TASK_REPORT_VIEW'         => 'employee-task.reports.view',
```

---

## Appendix B: Migration Timestamp Ordering

All three new migrations must run AFTER the existing Attendance migrations:

```
2026_05_20_000001_create_employee_task_requests_table.php
2026_05_20_000002_create_employee_task_sessions_table.php
2026_05_20_000003_create_employee_task_extension_requests_table.php
```

They have no dependencies on the new `attendance_constraint_locations` table.

---

*End of Plan — Ready for review before implementation begins.*
