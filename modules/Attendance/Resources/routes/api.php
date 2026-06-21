<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Controllers\AttendanceController;
use Modules\Attendance\Controllers\AttendanceReportController;
use Modules\Attendance\Controllers\LeaveBalanceController;
use Modules\Attendance\Controllers\LeaveRequestController;
use Modules\Attendance\Controllers\LeaveTypeController;
use Modules\Attendance\Controllers\LocationTrackingController;
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

    // Employee Attendance Actions
    Route::post('clock-in', [AttendanceController::class, 'clockIn'])
        ->name('attendance.clock-in');

    Route::post('clock-out', [AttendanceController::class, 'clockOut'])
        ->name('attendance.clock-out');

    Route::get('live-tracking', [LocationTrackingController::class, 'getLiveTrackingData'])
        ->name('attendance.live-tracking');

    Route::post('track-location', [LocationTrackingController::class, 'store'])
        ->name('attendance.track-location');

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
    // });
});

// Leave Management Routes
Route::prefix('leave')->group(function () {

    // Leave Requests
    Route::apiResource('requests', LeaveRequestController::class, [
        'names' => [
            'index' => 'leave-requests.index',
            'store' => 'leave-requests.store',
            'show' => 'leave-requests.show',
            'update' => 'leave-requests.update',
            'destroy' => 'leave-requests.destroy',
        ]
    ]);

    // My Leave Requests
    Route::get('my-requests', [LeaveRequestController::class, 'myRequests'])
        ->name('leave-requests.my-requests');

    // Leave Request Actions
    Route::post('requests/{leaveRequestId}/cancel', [LeaveRequestController::class, 'cancel'])
        ->name('leave-requests.cancel');

    // Leave Approvals (for supervisors/HR)
    Route::middleware('permission:approve-leave-requests')->group(function () {
        Route::get('pending-approvals', [LeaveRequestController::class, 'pendingApprovals'])
            ->name('leave-requests.pending-approvals');

        Route::post('requests/{leaveRequestId}/approve', [LeaveRequestController::class, 'approve'])
            ->name('leave-requests.approve');

        Route::post('requests/{leaveRequestId}/reject', [LeaveRequestController::class, 'reject'])
            ->name('leave-requests.reject');
    });

    // Leave Calendar and Conflicts
    Route::get('calendar', [LeaveRequestController::class, 'calendar'])
        ->name('leave-requests.calendar');

    Route::post('check-conflicts', [LeaveRequestController::class, 'checkConflicts'])
        ->name('leave-requests.check-conflicts');

        // Leave Types Management
        // Route::apiResource('types', LeaveTypeController::class, [
        //     'names' => [
        //         'index' => 'leave-types.index',
        //         'store' => 'leave-types.store',
        //         'show' => 'leave-types.show',
        //         'update' => 'leave-types.update',
        //         'destroy' => 'leave-types.destroy',
        //     ]
        // ])->middleware('permission:manage-leave-types');

        // Leave Balances
        Route::prefix('balances')->group(function () {
            // Route::get('/', [LeaveBalanceController::class, 'index'])
            //     ->name('leave-balances.index');

            // Route::get('my-balances', [LeaveBalanceController::class, 'myBalances'])
            //     ->name('leave-balances.my-balances');

            // Route::get('{userId}', [LeaveBalanceController::class, 'userBalances'])
            //     ->middleware('permission:view-user-leave-balances')
            //     ->name('leave-balances.user-balances');

            // // Balance Management (HR/Admin)
            // Route::middleware('permission:manage-leave-balances')->group(function () {
            //     Route::post('initialize', [LeaveBalanceController::class, 'initializeBalances'])
            //         ->name('leave-balances.initialize');

            //     Route::post('adjust', [LeaveBalanceController::class, 'adjustBalance'])
            //         ->name('leave-balances.adjust');

            //     Route::post('reset-year', [LeaveBalanceController::class, 'resetForNewYear'])
            //         ->name('leave-balances.reset-year');
            // });
        });
    });

    // HR Attendance & Contract Reports
    Route::prefix('hr/attendance/reports')->group(function () {
        Route::get('/', [AttendanceReportController::class, 'index'])
            ->permission(Permission::ATTENDANCE_REPORTS_VIEW())
            ->name('hr.attendance.reports.index');
    });

// Dashboard Statistics (for different user roles)
    Route::prefix('dashboard')->group(function () {

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
