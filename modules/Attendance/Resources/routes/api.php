<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Controllers\AttendanceController;
use Modules\Attendance\Controllers\LeaveRequestController;
use Modules\Attendance\Controllers\LeaveTypeController;
use Modules\Attendance\Controllers\LeaveBalanceController;
use Modules\Attendance\Controllers\AttendanceReportController;

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

// Attendance Management Routes
Route::prefix('attendance')->group(function () {

    // Employee Attendance Actions
    Route::post('clock-in', [AttendanceController::class, 'clockIn'])
        ->name('attendance.clock-in');

    Route::post('clock-out', [AttendanceController::class, 'clockOut'])
        ->name('attendance.clock-out');

    Route::post('start-break', [AttendanceController::class, 'startBreak'])
        ->name('attendance.start-break');

    Route::post('end-break', [AttendanceController::class, 'endBreak'])
        ->name('attendance.end-break');

    // Attendance Status and History
    Route::get('current-status', [AttendanceController::class, 'getCurrentStatus'])
        ->name('attendance.current-status');

    Route::get('history', [AttendanceController::class, 'getHistory'])
        ->name('attendance.history');

    Route::get('summary', [AttendanceController::class, 'getSummary'])
        ->name('attendance.summary');

    Route::get('status', [AttendanceController::class, 'getStatus'])
        ->name('attendance.get-status');

    // Team Attendance (for supervisors)
    Route::get('team', [AttendanceController::class, 'getTeamAttendance'])
        ->middleware('permission:view-team-attendance')
        ->name('attendance.team');

    // Attendance Management (HR/Admin)
    Route::middleware('permission:manage-attendance')->group(function () {
        Route::put('{attendanceId}', [AttendanceController::class, 'update'])
            ->name('attendance.update');

        Route::post('{attendanceId}/approve', [AttendanceController::class, 'approve'])
            ->name('attendance.approve');

        Route::post('{attendanceId}/reject', [AttendanceController::class, 'reject'])
            ->name('attendance.reject');

        Route::delete('{attendanceId}', [AttendanceController::class, 'destroy'])
            ->name('attendance.destroy');
    });
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
    Route::apiResource('types', LeaveTypeController::class, [
        'names' => [
            'index' => 'leave-types.index',
            'store' => 'leave-types.store',
            'show' => 'leave-types.show',
            'update' => 'leave-types.update',
            'destroy' => 'leave-types.destroy',
        ]
    ])->middleware('permission:manage-leave-types');

    // Leave Balances
    Route::prefix('balances')->group(function () {
        Route::get('/', [LeaveBalanceController::class, 'index'])
            ->name('leave-balances.index');

        Route::get('my-balances', [LeaveBalanceController::class, 'myBalances'])
            ->name('leave-balances.my-balances');

        Route::get('{userId}', [LeaveBalanceController::class, 'userBalances'])
            ->middleware('permission:view-user-leave-balances')
            ->name('leave-balances.user-balances');

        // Balance Management (HR/Admin)
        Route::middleware('permission:manage-leave-balances')->group(function () {
            Route::post('initialize', [LeaveBalanceController::class, 'initializeBalances'])
                ->name('leave-balances.initialize');

            Route::post('adjust', [LeaveBalanceController::class, 'adjustBalance'])
                ->name('leave-balances.adjust');

            Route::post('reset-year', [LeaveBalanceController::class, 'resetForNewYear'])
                ->name('leave-balances.reset-year');
        });
    });
});

// Attendance Reports
Route::prefix('reports')->middleware('permission:view-attendance-reports')->group(function () {

    Route::get('attendance-summary', [AttendanceReportController::class, 'attendanceSummary'])
        ->name('reports.attendance-summary');

    Route::get('overtime-report', [AttendanceReportController::class, 'overtimeReport'])
        ->name('reports.overtime-report');

    Route::get('leave-utilization', [AttendanceReportController::class, 'leaveUtilization'])
        ->name('reports.leave-utilization');

    Route::get('punctuality-report', [AttendanceReportController::class, 'punctualityReport'])
        ->name('reports.punctuality-report');

    Route::get('absence-report', [AttendanceReportController::class, 'absenceReport'])
        ->name('reports.absence-report');

    Route::get('team-attendance', [AttendanceReportController::class, 'teamAttendanceReport'])
        ->name('reports.team-attendance');

    // Export Reports
    Route::post('export/attendance', [AttendanceReportController::class, 'exportAttendance'])
        ->name('reports.export-attendance');

    Route::post('export/leave-requests', [AttendanceReportController::class, 'exportLeaveRequests'])
        ->name('reports.export-leave-requests');
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
