<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Controllers\AttendanceController;
use Modules\Attendance\Controllers\MobileAttendanceController;
use Modules\Attendance\Controllers\AttendanceAnalyticsController;

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

Route::middleware(['auth:api'])->group(function () {
    // Standard Attendance Routes
    Route::prefix('attendance')->group(function () {
        Route::post('/clock-in', [AttendanceController::class, 'clockIn']);
        Route::post('/clock-out', [AttendanceController::class, 'clockOut']);
        Route::get('/current', [AttendanceController::class, 'getCurrentAttendance']);
        Route::get('/history', [AttendanceController::class, 'getAttendanceHistory']);
        Route::get('/dashboard', [AttendanceController::class, 'getDashboardData']);
    });
    
    // Mobile-optimized Attendance Routes
    Route::prefix('mobile/attendance')->group(function () {
        Route::post('/clock-in', [MobileAttendanceController::class, 'clockIn']);
        Route::post('/clock-out', [MobileAttendanceController::class, 'clockOut']);
        Route::get('/dashboard', [MobileAttendanceController::class, 'getDashboard']);
        Route::post('/sync', [MobileAttendanceController::class, 'syncOfflineRecords']);
        Route::get('/nearest-office', [MobileAttendanceController::class, 'getNearestOffice']);
    });
    
    // Analytics Dashboard Routes
    Route::prefix('attendance/analytics')->middleware(['permission:view_attendance_analytics'])->group(function () {
        Route::get('/company', [AttendanceAnalyticsController::class, 'getCompanyAnalytics']);
        Route::get('/department', [AttendanceAnalyticsController::class, 'getDepartmentAnalytics']);
        Route::get('/user/{userId}', [AttendanceAnalyticsController::class, 'getUserAnalytics']);
        Route::get('/location', [AttendanceAnalyticsController::class, 'getLocationAnalytics']);
        Route::get('/violations', [AttendanceAnalyticsController::class, 'getViolationAnalytics']);
    });
});
