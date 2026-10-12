<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Controllers\AttendanceAttachmentController;
use Modules\Attendance\Controllers\AttendanceController;
use Modules\Attendance\Controllers\DashboardOverviewController;
use Modules\Attendance\Controllers\AttendanceReportController;
use Modules\Attendance\Controllers\LocationTrackingController;
use Modules\Attendance\Controllers\OutZoneWarningController;
use Modules\Attendance\Controllers\PenaltyExceptionController;
use Modules\Attendance\Controllers\UserAttendanceController;
use Modules\RoleAndPermission\Enums\Permission;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

/*
|--------------------------------------------------------------------------
| API Routes - Attendance Module
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Attendance module.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group and "auth:api" middleware.
|
*/
Route::middleware(['auth:api', InitializeTenancyByRequestData::class])->group(function () {

    // Attendance Management Routes
    Route::prefix('attendance')->group(function () {

Route::post('test', [AttendanceController::class, 'test'])
        ->name('attendance.test');

    // Face liveness session (anti-spoofing pre-check before clock-in/out)
    Route::post('face-liveness-session', [AttendanceController::class, 'createFaceLivenessSession'])
        ->name('attendance.face-liveness-session');

    // Employee Attendance Actions
    Route::post('clock-in', [AttendanceController::class, 'clockIn'])
        ->name('attendance.clock-in');

    Route::post('clock-out', [AttendanceController::class, 'clockOut'])
        ->name('attendance.clock-out');

    Route::get('live-tracking', [LocationTrackingController::class, 'getLiveTrackingData'])
        ->name('attendance.live-tracking');

    Route::post('track-location', [LocationTrackingController::class, 'store'])
        ->name('attendance.track-location');

    Route::get('out-zone-warning', [OutZoneWarningController::class, 'status'])
        ->name('attendance.out-zone-warning.status');

    Route::post('out-zone-warning/confirm-location', [OutZoneWarningController::class, 'confirmLocation'])
        ->name('attendance.out-zone-warning.confirm-location');

    // Penalty day-exception (استثناء اليوم): waive an applied auto clock-out
    // penalty by spending one of the monthly exceptions (default 3).
    Route::get('penalty-exceptions/current-month', [PenaltyExceptionController::class, 'currentMonth'])
        ->name('attendance.penalty-exceptions.current-month');

    Route::post('penalty-exceptions/{attendanceId}', [PenaltyExceptionController::class, 'decide'])
        ->name('attendance.penalty-exceptions.decide');

    Route::post('start-break', [AttendanceController::class, 'startBreak'])
        ->name('attendance.start-break');

    Route::post('end-break', [AttendanceController::class, 'endBreak'])
        ->name('attendance.end-break');

    // Attendance Status and History
    Route::get('current-status', [AttendanceController::class, 'getCurrentStatus'])
        ->name('attendance.current-status');

    Route::get('history', [AttendanceController::class, 'getHistory'])
        ->permission(Permission::EMPLOYEE_ATTENDANCE_VIEW())
        ->name('attendance.history');
            // // Export Reports
    Route::post('/team/export', [AttendanceController::class, 'exportTeamAttendance'])
        ->permission(Permission::EMPLOYEE_ATTENDANCE_EXPORT())
        ->name('reports.export-attendance');

    Route::get('summary', [AttendanceController::class, 'getSummary'])
        ->permission(Permission::EMPLOYEE_ATTENDANCE_VIEW())
        ->name('attendance.summary');

    Route::get('status', [AttendanceController::class, 'getStatus'])
        ->name('attendance.get-status');

    // User Constraint Routes mobile user
    Route::prefix('user-constraint')->group(function () {
        // Get current user's constraint for today
        Route::get('today', [UserAttendanceController::class, 'getMyConstraintForToday'])
//            ->permission(Permission::EMPLOYEE_ATTENDANCE_SELF_VIEW())
            ->name('attendance.user-constraint.today');
    });

    // User Attendance Status Routes
    Route::prefix('user-attendance')->group(function () {
        // Get current user's clock-in status
        Route::get('status', [UserAttendanceController::class, 'getMyClockInStatus'])
            ->name('attendance.user-attendance-status');
        Route::get('history', [UserAttendanceController::class, 'getUserAttendanceHistory'])
            ->name('attendance.user-attendance.get-history');
        // Get current user's attendance calendar
        Route::get('calendar', [UserAttendanceController::class, 'getAttendanceCalendar'])
//            ->permission(Permission::EMPLOYEE_ATTENDANCE_SELF_VIEW())
            ->name('attendance.user-attendance.calendar');
    });

    // Team Attendance (for supervisors)
    Route::get('open', [AttendanceController::class, 'getOpenAttendances'])
        ->permission(Permission::EMPLOYEE_ATTENDANCE_VIEW())
        ->name('attendance.open');

    Route::get('team', [AttendanceController::class, 'getTeamAttendance'])
       ->permission(Permission::EMPLOYEE_ATTENDANCE_VIEW())
        ->name('attendance.team');

    Route::get('team/user', [AttendanceController::class, 'getUserAttendance'])
        ->name('attendance.team');

    Route::get('{attendance}/team', [AttendanceController::class, 'teamAttendance'])
       ->permission(Permission::EMPLOYEE_ATTENDANCE_VIEW())
        ->name('attendance.team.show');

    Route::get('{attendance}/applied-attendance', [AttendanceController::class, 'appliedAttendanceConstraint'])
       // ->middleware('permission:view-team-attendance')
       ->permission(Permission::EMPLOYEE_ATTENDANCE_VIEW())
        ->name('attendance.appliedAttendanceConstraint.show');

    // Attendance Management (HR/Admin)
    // Route::middleware('permission:manage-attendance')->group(function () {
        Route::put('{attendanceId}', [AttendanceController::class, 'update'])
            ->permission(Permission::EMPLOYEE_ATTENDANCE_UPDATE())
            ->name('attendance.update');

        Route::post('{attendanceId}/approve', [AttendanceController::class, 'approve'])
            ->permission(Permission::EMPLOYEE_ATTENDANCE_UPDATE())
            ->name('attendance.approve');

        Route::post('{attendanceId}/reject', [AttendanceController::class, 'reject'])
            ->permission(Permission::EMPLOYEE_ATTENDANCE_UPDATE())
            ->name('attendance.reject');

        Route::delete('{attendanceId}', [AttendanceController::class, 'destroy'])
            ->permission(Permission::EMPLOYEE_ATTENDANCE_DELETE())
            ->name('attendance.destroy');

        Route::get('users/{userId}/face-verification-exception', [AttendanceController::class, 'getFaceVerificationException'])
            ->name('attendance.users.face-verification-exception.show');

        Route::put('users/{userId}/face-verification-exception', [AttendanceController::class, 'updateFaceVerificationException'])
            ->name('attendance.users.face-verification-exception.update');
    // });
});

// Leave Management lives in the Leave module (Modules\Leave\LeaveRequest):
// employee requests + workflow approval under api/v1/leave-requests,
// policy assignment and balances under api/v1/users/{userId}/leave-policies
// and api/v1/my/leave-policies.

    // HR Attendance & Contract Reports
    Route::prefix('hr/attendance/reports')->group(function () {
        Route::get('/', [AttendanceReportController::class, 'index'])
            ->permission(Permission::ATTENDANCE_REPORTS_VIEW())
            ->name('hr.attendance.reports.index');
    });

    Route::prefix('hr/attendance/attachments')->group(function () {
        Route::get('/', [AttendanceAttachmentController::class, 'show'])
            ->name('hr.attendance.attachments.show');

        Route::post('/', [AttendanceAttachmentController::class, 'store'])
            ->name('hr.attendance.attachments.store');
    });

// Dashboard Statistics (for different user roles)
    Route::prefix('dashboard')->group(function () {
        Route::get('overview', DashboardOverviewController::class)
            ->name('dashboard.overview');

        // Employee Dashboard
        Route::get('employee-stats', [AttendanceController::class, 'getEmployeeStats'])
            ->name('dashboard.employee-stats');

        // Supervisor Dashboard
        Route::get('supervisor-stats', [AttendanceController::class, 'getSupervisorStats'])
            ->middleware('permission:view-team-attendance')
            ->name('dashboard.supervisor-stats');

        // HR Dashboard
        Route::get('hr-stats', [AttendanceController::class, 'getHRStats'])
            ->middleware('permission:view-attendance-reports')
            ->name('dashboard.hr-stats');
        });
});
