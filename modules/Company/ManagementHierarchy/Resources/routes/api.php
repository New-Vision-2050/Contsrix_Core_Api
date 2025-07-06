<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\ManagementHierarchy\Controllers\ManagementHierarchyCloneController;
use Modules\Company\ManagementHierarchy\Controllers\ManagementHierarchyController;
use Modules\Company\ManagementHierarchy\Controllers\ManagementHierarchySettingController;
use Modules\Company\ManagementHierarchy\Controllers\WidgetsController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [ManagementHierarchyController::class, 'index'])->permission(Permission::ORGANIZATION_BRANCH_VIEW() . '|' . Permission::ORGANIZATION_MANAGEMENT_VIEW());
    Route::get('/widgets', [WidgetsController::class, 'getAllWidgets']);

    Route::get('/list', [ManagementHierarchyController::class, 'listWithoutPagination'])->permission(Permission::ORGANIZATION_BRANCH_VIEW() . '|' . Permission::ORGANIZATION_MANAGEMENT_VIEW());
    Route::get('/tree', [ManagementHierarchyController::class, 'presentTree'])->permission(Permission::ORGANIZATION_BRANCH_VIEW() . '|' . Permission::ORGANIZATION_MANAGEMENT_VIEW());
    Route::get('/tree-direct-children', [ManagementHierarchyController::class, 'directChildrenTree'])->permission(Permission::ORGANIZATION_BRANCH_VIEW() . '|' . Permission::ORGANIZATION_MANAGEMENT_VIEW());
    Route::get('/user', [ManagementHierarchyController::class, 'hierarchies']);
    Route::get('/user-lower-levels', [ManagementHierarchyController::class, 'getUserLowerLevels']);
    Route::get('/non-copied', [ManagementHierarchySettingController::class, 'getNonCopiedHierarchies']);
    Route::get('/non-copied/all', [ManagementHierarchySettingController::class, 'getAllNonCopiedHierarchies']);
    Route::get('/lookups', [ManagementHierarchySettingController::class, 'getLookupsForChoices']);
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
    Route::post('/create-department', [ManagementHierarchyController::class, 'createDepartment']);
    Route::post('/update-branch/{id}', [ManagementHierarchyController::class, 'updateBranch'])->permission(Permission::ORGANIZATION_BRANCH_UPDATE());
    Route::put('/update-management/{id}', [ManagementHierarchyController::class, 'updateManagement'])->permission(Permission::ORGANIZATION_MANAGEMENT_UPDATE());

    Route::post('/make-branch-main/{id}', [ManagementHierarchyController::class, 'makeBranchMain'])->permission(Permission::ORGANIZATION_BRANCH_UPDATE());
    Route::post('/', [ManagementHierarchyController::class, 'store']);
    Route::get('/{id}', [ManagementHierarchyController::class, 'show'])->permission(Permission::ORGANIZATION_BRANCH_VIEW() . '|' . Permission::ORGANIZATION_MANAGEMENT_VIEW());
    Route::put('/{id}', [ManagementHierarchyController::class, 'update'])->permission(Permission::ORGANIZATION_BRANCH_UPDATE() . '|' . Permission::ORGANIZATION_MANAGEMENT_UPDATE());
    Route::delete('/{id}', [ManagementHierarchyController::class, 'delete'])->permission(Permission::ORGANIZATION_BRANCH_DELETE() . '|' . Permission::ORGANIZATION_MANAGEMENT_DELETE());

    // Department cloning routes
    Route::post('/clone-department', [ManagementHierarchyCloneController::class, 'cloneManagement']);
    Route::get('/linked-departments/{departmentId}', [ManagementHierarchyCloneController::class, 'getLinkedDepartments']);
    Route::post('/sync-departments/{departmentId}', [ManagementHierarchyCloneController::class, 'syncLinkedDepartments']);

    // Widgets API - single endpoint for all widgets
});
