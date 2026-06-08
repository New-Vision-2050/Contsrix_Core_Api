<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Controllers\AttendanceConstraintController;
use Modules\RoleAndPermission\Enums\Permission;
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
        // Temporarily commented out for development
        // // ->middleware('permission:view_attendance_constraints')
        ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW())
        ->name('attendance.constraints.index');

    Route::get('/list', [AttendanceConstraintController::class, 'list'])
        // Temporarily commented out for development
        // // ->middleware('permission:view_attendance_constraints')
        ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW())
        ->name('attendance.constraints.list');

    Route::post('/', [AttendanceConstraintController::class, 'store'])
        // ->middleware('permission:create_attendance_constraints')
        ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_CREATE())
        ->name('attendance.constraints.store');
    // User-specific Constraint Routes

    Route::get('/user', [AttendanceConstraintController::class, 'userConstraint'])
        // ->middleware('permission:view_attendance_constraints')
        ->name('attendance.constraints.user');

    Route::put('/{constraint}', [AttendanceConstraintController::class, 'update'])
        // ->middleware('permission:update_attendance_constraints')
        ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE())
        ->name('attendance.constraints.update');

    Route::delete('/{constraint}', [AttendanceConstraintController::class, 'destroy'])
        // ->middleware('permission:delete_attendance_constraints')
        ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_DELETE())
        ->name('attendance.constraints.destroy');

    // Constraint Types and Validation Routes
    Route::get('/types', [AttendanceConstraintController::class, 'getConstraintTypes'])
        // ->middleware('permission:view_attendance_constraints')
        ->name('attendance.constraints.types');

    Route::post('/validate', [AttendanceConstraintController::class, 'validate'])
        // ->middleware('permission:validate_attendance_constraints')
        ->name('attendance.constraints.validate');

    // Branch-based Constraint Routes
    Route::get('/branches/{branchId}', [AttendanceConstraintController::class, 'getConstraintsByBranch'])
        // ->middleware('permission:view_attendance_constraints')
        ->name('attendance.constraints.by-branch');

    Route::post('/branches/{branchId}/bulk-assign', [AttendanceConstraintController::class, 'bulkAssignToBranch'])
        // ->middleware('permission:create_attendance_constraints')
        ->name('attendance.constraints.bulk-assign-branch');

    Route::get('/branches/{branchId}/inherited', [AttendanceConstraintController::class, 'getInheritedConstraints'])
        // ->middleware('permission:view_attendance_constraints')
        ->name('attendance.constraints.inherited');

    // Violation Management Routes
    Route::get('/violations', [AttendanceConstraintController::class, 'getViolations'])
        // ->middleware('permission:view_attendance_violations')
        ->name('attendance.constraints.violations.index');

    Route::put('/violations/{violation}/resolve', [AttendanceConstraintController::class, 'resolveViolation'])
        // ->middleware('permission:resolve_attendance_violations')
        ->name('attendance.constraints.violations.resolve');

    Route::put('/violations/{violation}/dismiss', [AttendanceConstraintController::class, 'dismissViolation'])
        // ->middleware('permission:resolve_attendance_violations')
        ->name('attendance.constraints.violations.dismiss');

    // Statistics and Reporting Routes
    Route::get('/statistics', [AttendanceConstraintController::class, 'getStatistics'])
        // ->middleware('permission:view_attendance_reports')
        ->name('attendance.constraints.statistics');

    Route::get('/violations/summary', [AttendanceConstraintController::class, 'getViolationsSummary'])
        // ->middleware('permission:view_attendance_reports')
        ->name('attendance.constraints.violations.summary');

    // Bulk Operations Routes
    Route::post('/bulk/activate', [AttendanceConstraintController::class, 'bulkActivate'])
        // ->middleware('permission:update_attendance_constraints')
        ->name('attendance.constraints.bulk.activate');

    Route::post('/bulk/deactivate', [AttendanceConstraintController::class, 'bulkDeactivate'])
        // ->middleware('permission:update_attendance_constraints')
        ->name('attendance.constraints.bulk.deactivate');

    Route::post('/bulk/delete', [AttendanceConstraintController::class, 'bulkDelete'])
        // ->middleware('permission:delete_attendance_constraints')
        ->name('attendance.constraints.bulk.delete');

    Route::get('/{constraint}', [AttendanceConstraintController::class, 'show'])
        // ->middleware('permission:view_attendance_constraints')
        ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW())
        ->name('attendance.constraints.show');

    // Employee constraint location and assignment routes
    Route::prefix('/employees/{userId}')->group(function () {
        Route::get('/constraint-locations', [AttendanceConstraintController::class, 'getEmployeeConstraintLocations'])
            ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW())
            ->name('attendance.constraints.employee.locations');

        Route::put('/assign-constraint', [AttendanceConstraintController::class, 'updateEmployeeConstraint'])
            ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE())
            ->name('attendance.constraints.employee.assign');
    });

    // Per-user additional constraint management
    Route::prefix('/users/{userId}/additional')->group(function () {
        Route::get('/', [AttendanceConstraintController::class, 'getUserAdditionalConstraints'])
            ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW())
            ->name('attendance.constraints.user.additional.index');

        Route::post('/', [AttendanceConstraintController::class, 'assignUserConstraints'])
            ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE())
            ->name('attendance.constraints.user.additional.assign');

        Route::delete('/{constraintId}', [AttendanceConstraintController::class, 'removeUserConstraint'])
            ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE())
            ->name('attendance.constraints.user.additional.remove');
    });

    // Update basic info (name, constraint_type, branches)
    Route::patch('/{constraint}/basic-info', [AttendanceConstraintController::class, 'updateBasicInfo'])
        ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE())
        ->name('attendance.constraints.update-basic-info');

    // Constraint employees
    Route::get('/{constraint}/employees', [AttendanceConstraintController::class, 'getConstraintEmployees'])
        ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW())
        ->name('attendance.constraints.employees.index');

    Route::post('/{constraint}/employees', [AttendanceConstraintController::class, 'assignEmployeeToConstraint'])
        ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE())
        ->name('attendance.constraints.employees.assign');

    // Additional locations CRUD
    Route::get('/{constraint}/locations', [AttendanceConstraintController::class, 'getLocations'])
        ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW())
        ->name('attendance.constraints.locations.index');

    Route::post('/{constraint}/locations', [AttendanceConstraintController::class, 'createLocations'])
        ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE())
        ->name('attendance.constraints.locations.store');

    Route::put('/locations/{location}', [AttendanceConstraintController::class, 'updateLocation'])
        ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE())
        ->name('attendance.constraints.locations.update');

    Route::delete('/locations/{location}', [AttendanceConstraintController::class, 'deleteLocation'])
        ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_DELETE())
        ->name('attendance.constraints.locations.destroy');

    // Day shifts — read
    Route::get('/{constraint}/day-shifts', [AttendanceConstraintController::class, 'getDayShifts'])
        ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW())
        ->name('attendance.constraints.day-shifts');

    // Read shifts in frontend-ready format (detects weekly vs daily mode)
    Route::get('/{constraint}/shifts', [AttendanceConstraintController::class, 'getShifts'])
        ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW())
        ->name('attendance.constraints.shifts.get');

    // Assign / replace weekly schedule shifts (weekly or daily mode)
    Route::post('/{constraint}/shifts', [AttendanceConstraintController::class, 'assignShifts'])
        ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE())
        ->name('attendance.constraints.shifts.assign');

    // Update constraint-level rules (lateness, early-clock-in, max overtime)
    Route::patch('/{constraint}/rules', [AttendanceConstraintController::class, 'updateRules'])
        ->permission(Permission::EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE())
        ->name('attendance.constraints.rules.update');
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
