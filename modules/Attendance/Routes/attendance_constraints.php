<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Controllers\AttendanceConstraintController;

/*
|--------------------------------------------------------------------------
| Attendance Constraints API Routes
|--------------------------------------------------------------------------
|
| Here are the routes for managing attendance constraints and violations.
| All routes require authentication and appropriate permissions.
|
*/

Route::middleware(['auth:api'])->prefix('attendance/constraints')->group(function () {

    // Constraint Management Routes
    Route::get('/', [AttendanceConstraintController::class, 'index'])
        ->middleware('permission:view_attendance_constraints')
        ->name('attendance.constraints.index');

    Route::post('/', [AttendanceConstraintController::class, 'store'])
        ->middleware('permission:create_attendance_constraints')
        ->name('attendance.constraints.store');

    Route::get('/{constraint}', [AttendanceConstraintController::class, 'show'])
        ->middleware('permission:view_attendance_constraints')
        ->name('attendance.constraints.show');

    Route::put('/{constraint}', [AttendanceConstraintController::class, 'update'])
        ->middleware('permission:update_attendance_constraints')
        ->name('attendance.constraints.update');

    Route::delete('/{constraint}', [AttendanceConstraintController::class, 'destroy'])
        ->middleware('permission:delete_attendance_constraints')
        ->name('attendance.constraints.destroy');

    // Constraint Types and Validation Routes
    Route::get('/types', [AttendanceConstraintController::class, 'getConstraintTypes'])
        ->middleware('permission:view_attendance_constraints')
        ->name('attendance.constraints.types');

    Route::post('/validate', [AttendanceConstraintController::class, 'validate'])
        ->middleware('permission:validate_attendance_constraints')
        ->name('attendance.constraints.validate');

    // Branch-based Constraint Routes
    Route::get('/branches/{branchId}', [AttendanceConstraintController::class, 'getConstraintsByBranch'])
        ->middleware('permission:view_attendance_constraints')
        ->name('attendance.constraints.by-branch');

    Route::post('/branches/{branchId}/bulk-assign', [AttendanceConstraintController::class, 'bulkAssignToBranch'])
        ->middleware('permission:create_attendance_constraints')
        ->name('attendance.constraints.bulk-assign-branch');

    Route::get('/branches/{branchId}/inherited', [AttendanceConstraintController::class, 'getInheritedConstraints'])
        ->middleware('permission:view_attendance_constraints')
        ->name('attendance.constraints.inherited');

    // Violation Management Routes
    Route::get('/violations', [AttendanceConstraintController::class, 'getViolations'])
        ->middleware('permission:view_attendance_violations')
        ->name('attendance.constraints.violations.index');

    Route::put('/violations/{violation}/resolve', [AttendanceConstraintController::class, 'resolveViolation'])
        ->middleware('permission:resolve_attendance_violations')
        ->name('attendance.constraints.violations.resolve');

    Route::put('/violations/{violation}/dismiss', [AttendanceConstraintController::class, 'dismissViolation'])
        ->middleware('permission:resolve_attendance_violations')
        ->name('attendance.constraints.violations.dismiss');

    // Statistics and Reporting Routes
    Route::get('/statistics', [AttendanceConstraintController::class, 'getStatistics'])
        ->middleware('permission:view_attendance_reports')
        ->name('attendance.constraints.statistics');

    Route::get('/violations/summary', [AttendanceConstraintController::class, 'getViolationsSummary'])
        ->middleware('permission:view_attendance_reports')
        ->name('attendance.constraints.violations.summary');

    // Bulk Operations Routes
    Route::post('/bulk/activate', [AttendanceConstraintController::class, 'bulkActivate'])
        ->middleware('permission:update_attendance_constraints')
        ->name('attendance.constraints.bulk.activate');

    Route::post('/bulk/deactivate', [AttendanceConstraintController::class, 'bulkDeactivate'])
        ->middleware('permission:update_attendance_constraints')
        ->name('attendance.constraints.bulk.deactivate');

    Route::post('/bulk/delete', [AttendanceConstraintController::class, 'bulkDelete'])
        ->middleware('permission:delete_attendance_constraints')
        ->name('attendance.constraints.bulk.delete');
});

/*
|--------------------------------------------------------------------------
| Required Permissions
|--------------------------------------------------------------------------
|
| The following permissions should be defined in your permission system:
|
| - view_attendance_constraints: View attendance constraints
| - create_attendance_constraints: Create new attendance constraints
| - update_attendance_constraints: Update existing attendance constraints
| - delete_attendance_constraints: Delete attendance constraints
| - validate_attendance_constraints: Validate constraints for users
| - view_attendance_violations: View constraint violations
| - resolve_attendance_violations: Resolve or dismiss violations
| - view_attendance_reports: View constraint statistics and reports
|
*/
