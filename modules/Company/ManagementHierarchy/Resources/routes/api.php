<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\ManagementHierarchy\Controllers\ManagementHierarchyCloneController;
use Modules\Company\ManagementHierarchy\Controllers\ManagementHierarchyController;
<<<<<<< HEAD
use Modules\Company\ManagementHierarchy\Controllers\ManagementHierarchySettingController;
=======
>>>>>>> 7be6c72c (merge with stage (first version ))
use Modules\Company\ManagementHierarchy\Controllers\WidgetsController;


Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [ManagementHierarchyController::class, 'index']);
    Route::get('/widgets', [WidgetsController::class, 'getAllWidgets']);

    Route::get('/list', [ManagementHierarchyController::class, 'listWithoutPagination']);
    Route::get('/tree', [ManagementHierarchyController::class, 'presentTree']);
    Route::get('/tree-direct-children', [ManagementHierarchyController::class, 'directChildrenTree']);
    Route::get('/user', [ManagementHierarchyController::class, 'hierarchies']);
    Route::get('/user-lower-levels', [ManagementHierarchyController::class, 'getUserLowerLevels']);
<<<<<<< HEAD
    Route::get('/non-copied', [ManagementHierarchySettingController::class, 'getNonCopiedHierarchies']);
    Route::get('/non-copied/all', [ManagementHierarchySettingController::class, 'getAllNonCopiedHierarchies']);
    Route::get('/non-copied/{id}', [ManagementHierarchySettingController::class, 'showNonCopiedHierarchy']);
    Route::get('/lookups', [ManagementHierarchySettingController::class, 'getLookupsForChoise']);
    Route::get('/job_titles', [ManagementHierarchySettingController::class, 'getJobTitles']);
    Route::post('/create-branch', [ManagementHierarchyController::class, 'createBranch']);
    Route::post('/create-management', [ManagementHierarchyController::class, 'createManagement']);
    Route::group(["prefix" => "management-with-relations"], function () {
        Route::post('/', [ManagementHierarchySettingController::class, 'createManagementWithLookupsForChoise']);
        Route::post('/{id}', [ManagementHierarchySettingController::class, 'updateManagementWithLookupsForChoise']);
        Route::delete('/{id}', [ManagementHierarchySettingController::class, 'deleteManagementWithLookupsForChoise']);

    });
    Route::group(["prefix" => "department-with-relations"], function () {
        Route::post('/', [ManagementHierarchySettingController::class, 'createDepartmentWithManagementsForDropDown']);
        Route::post('/{id}', [ManagementHierarchySettingController::class, 'updateDepartmentWithManagementsForDropDown']);
        Route::delete('/{id}', [ManagementHierarchySettingController::class, 'deleteDepartmentWithManagementsForDropDown']);

    });
    Route::post('/create-management-with-relations', [ManagementHierarchySettingController::class, 'createManagementWithLookupsForChoise']);
    Route::post('/create-department-with-relations', [ManagementHierarchySettingController::class, 'createDepartmentWithManagementsForDropDown']);
=======
    Route::post('/create-branch', [ManagementHierarchyController::class, 'createBranch']);
    Route::post('/create-management', [ManagementHierarchyController::class, 'createManagement']);
>>>>>>> 7be6c72c (merge with stage (first version ))
    Route::post('/create-department', [ManagementHierarchyController::class, 'createDepartment']);
    Route::post('/update-branch/{id}', [ManagementHierarchyController::class, 'updateBranch']);
    Route::put('/update-management/{id}', [ManagementHierarchyController::class, 'updateManagement']);

    Route::post('/make-branch-main/{id}', [ManagementHierarchyController::class, 'makeBranchMain']);
    Route::post('/', [ManagementHierarchyController::class, 'store']);
    Route::get('/{id}', [ManagementHierarchyController::class, 'show']);
    Route::put('/{id}', [ManagementHierarchyController::class, 'update']);
    Route::delete('/{id}', [ManagementHierarchyController::class, 'delete']);

<<<<<<< HEAD
    // Department cloning routes
    Route::post('/clone-department', [ManagementHierarchyCloneController::class, 'cloneManagement']);
    Route::get('/linked-departments/{departmentId}', [ManagementHierarchyCloneController::class, 'getLinkedDepartments']);
    Route::post('/sync-departments/{departmentId}', [ManagementHierarchyCloneController::class, 'syncLinkedDepartments']);

=======
>>>>>>> 7be6c72c (merge with stage (first version ))
    // Widgets API - single endpoint for all widgets
});
