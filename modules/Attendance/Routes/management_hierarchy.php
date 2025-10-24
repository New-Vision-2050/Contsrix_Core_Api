<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Controllers\ManagementHierarchyController;

/*
|--------------------------------------------------------------------------
| Management Hierarchy API Routes for Attendance
|--------------------------------------------------------------------------
|
| Routes for accessing management hierarchy data needed for branch-based
| attendance constraints. These routes provide branch information and
| hierarchy structure for constraint management.
|
*/

Route::middleware(['auth:api', 'tenant'])->prefix('attendance/hierarchy')->group(function () {
    
    // Branch listing and details
    Route::get('/branches', [ManagementHierarchyController::class, 'getBranches'])
        ->middleware(['permission:view_attendance_constraints', 'deduplicate:2'])
        ->name('attendance.hierarchy.branches');
    
    Route::get('/branches/{branchId}', [ManagementHierarchyController::class, 'getBranchDetails'])
        ->middleware(['permission:view_attendance_constraints', 'deduplicate:2'])
        ->name('attendance.hierarchy.branch-details');
    
    // Branch hierarchy and relationships
    Route::get('/branches/{branchId}/children', [ManagementHierarchyController::class, 'getBranchChildren'])
        ->middleware(['permission:view_attendance_constraints', 'deduplicate:2'])
        ->name('attendance.hierarchy.branch-children');
    
    Route::get('/branches/{branchId}/parents', [ManagementHierarchyController::class, 'getBranchParents'])
        ->middleware(['permission:view_attendance_constraints', 'deduplicate:2'])
        ->name('attendance.hierarchy.branch-parents');
    
    // User-branch relationships
    Route::get('/users/{userId}/branch', [ManagementHierarchyController::class, 'getUserBranch'])
        ->middleware('permission:view_attendance_constraints')
        ->name('attendance.hierarchy.user-branch');
    
    Route::get('/branches/{branchId}/users', [ManagementHierarchyController::class, 'getBranchUsers'])
        ->middleware('permission:view_attendance_constraints')
        ->name('attendance.hierarchy.branch-users');
});
