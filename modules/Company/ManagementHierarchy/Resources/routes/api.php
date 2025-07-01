<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\ManagementHierarchy\Controllers\ManagementHierarchyCloneController;
use Modules\Company\ManagementHierarchy\Controllers\ManagementHierarchyController;
use Modules\Company\ManagementHierarchy\Controllers\WidgetsController;


Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [ManagementHierarchyController::class, 'index']);
    Route::get('/widgets', [WidgetsController::class, 'getAllWidgets']);

    Route::get('/list', [ManagementHierarchyController::class, 'listWithoutPagination']);
    Route::get('/tree', [ManagementHierarchyController::class, 'presentTree']);
    Route::get('/tree-direct-children', [ManagementHierarchyController::class, 'directChildrenTree']);
    Route::get('/user', [ManagementHierarchyController::class, 'hierarchies']);
    Route::get('/user-lower-levels', [ManagementHierarchyController::class, 'getUserLowerLevels']);
    Route::get('/non-copied', [ManagementHierarchyController::class, 'getNonCopiedHierarchies']);
    Route::get('/non-copied/all', [ManagementHierarchyController::class, 'getAllNonCopiedHierarchies']);
    Route::get('/lookups', [ManagementHierarchyController::class, 'getLookups']);
    Route::post('/create-branch', [ManagementHierarchyController::class, 'createBranch']);
    Route::post('/create-management', [ManagementHierarchyController::class, 'createManagement']);
    Route::post('/create-management-with-relations', [ManagementHierarchyController::class, 'createManagementWithLookupsForChoise']);
    Route::post('/create-department', [ManagementHierarchyController::class, 'createDepartment']);
    Route::post('/update-branch/{id}', [ManagementHierarchyController::class, 'updateBranch']);
    Route::put('/update-management/{id}', [ManagementHierarchyController::class, 'updateManagement']);

    Route::post('/make-branch-main/{id}', [ManagementHierarchyController::class, 'makeBranchMain']);
    Route::post('/', [ManagementHierarchyController::class, 'store']);
    Route::get('/{id}', [ManagementHierarchyController::class, 'show']);
    Route::put('/{id}', [ManagementHierarchyController::class, 'update']);
    Route::delete('/{id}', [ManagementHierarchyController::class, 'delete']);

    // Department cloning routes
    Route::post('/clone-department', [ManagementHierarchyCloneController::class, 'cloneDepartment']);
    Route::get('/linked-departments/{departmentId}', [ManagementHierarchyCloneController::class, 'getLinkedDepartments']);
    Route::post('/sync-departments/{departmentId}', [ManagementHierarchyCloneController::class, 'syncLinkedDepartments']);

    // Widgets API - single endpoint for all widgets
});
