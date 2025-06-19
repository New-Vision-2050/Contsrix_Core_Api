<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Controllers\AttendanceController;
use Modules\Attendance\Controllers\LeaveController;
use Modules\Attendance\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Attendance routes
Route::middleware(['auth:api', 'tenant.asset'])->group(function () {
    // Clock In/Out Endpoints
    Route::prefix('attendance')->group(function () {
        Route::post('clock-in', [AttendanceController::class, 'clockIn']);
        Route::post('clock-out', [AttendanceController::class, 'clockOut']);
        Route::post('break/start', [AttendanceController::class, 'startBreak']);
        Route::post('break/end', [AttendanceController::class, 'endBreak']);
        Route::get('status', [AttendanceController::class, 'currentStatus']);
        Route::get('history', [AttendanceController::class, 'history']);
        Route::get('today', [AttendanceController::class, 'today']);
    });

    // Leave Management Endpoints
    Route::prefix('leave')->group(function () {
        Route::get('/', [LeaveController::class, 'index']);
        Route::post('/', [LeaveController::class, 'store']);
        Route::get('/{id}', [LeaveController::class, 'show']);
        Route::put('/{id}', [LeaveController::class, 'update']);
        Route::delete('/{id}', [LeaveController::class, 'destroy']);
        Route::post('/{id}/approve', [LeaveController::class, 'approve']);
        Route::post('/{id}/reject', [LeaveController::class, 'reject']);
        Route::get('/balance', [LeaveController::class, 'balance']);
        Route::get('/types', [LeaveController::class, 'leaveTypes']);
    });

    // Reports Endpoints
    Route::prefix('attendance/reports')->group(function () {
        Route::get('/employee/{id}', [ReportController::class, 'employeeReport']);
        Route::get('/team', [ReportController::class, 'teamReport']);
        Route::get('/department', [ReportController::class, 'departmentReport']);
        Route::get('/overtime', [ReportController::class, 'overtimeReport']);
        Route::get('/absence', [ReportController::class, 'absenceReport']);
        Route::get('/leave-utilization', [ReportController::class, 'leaveUtilizationReport']);
        Route::post('/export', [ReportController::class, 'exportReport']);
    });

    // Admin Configuration Endpoints
    Route::middleware(['role:admin|hr_manager'])->prefix('attendance/settings')->group(function () {
        Route::get('/', [AttendanceController::class, 'getSettings']);
        Route::post('/', [AttendanceController::class, 'updateSettings']);
        Route::get('/leave-types', [LeaveController::class, 'getLeaveTypes']);
        Route::post('/leave-types', [LeaveController::class, 'createLeaveType']);
        Route::put('/leave-types/{id}', [LeaveController::class, 'updateLeaveType']);
        Route::delete('/leave-types/{id}', [LeaveController::class, 'deleteLeaveType']);
    });
});
