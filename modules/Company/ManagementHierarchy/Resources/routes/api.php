<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\ManagementHierarchy\Controllers\ManagementHierarchyController;
use Modules\Company\ManagementHierarchy\Controllers\WidgetsController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [ManagementHierarchyController::class, 'index'])->middleware("permission:" . Permission::ORGANIZATION_BRANCH_VIEW() . '|' . Permission::ORGANIZATION_MANAGEMENT_VIEW());
    Route::get('/widgets', [WidgetsController::class, 'getAllWidgets']);

    Route::get('/list', [ManagementHierarchyController::class, 'listWithoutPagination'])->middleware("permission:" . Permission::ORGANIZATION_BRANCH_VIEW() . '|' . Permission::ORGANIZATION_MANAGEMENT_VIEW());
    Route::get('/tree', [ManagementHierarchyController::class, 'presentTree'])->middleware("permission:" . Permission::ORGANIZATION_BRANCH_VIEW() . '|' . Permission::ORGANIZATION_MANAGEMENT_VIEW());
    Route::get('/tree-direct-children', [ManagementHierarchyController::class, 'directChildrenTree'])->middleware("permission:" . Permission::ORGANIZATION_BRANCH_VIEW() . '|' . Permission::ORGANIZATION_MANAGEMENT_VIEW());
    Route::get('/user', [ManagementHierarchyController::class, 'hierarchies']);
    Route::get('/user-lower-levels', [ManagementHierarchyController::class, 'getUserLowerLevels']);
    Route::post('/create-branch', [ManagementHierarchyController::class, 'createBranch'])->middleware("permission:" . Permission::ORGANIZATION_BRANCH_CREATE());
    Route::post('/create-management', [ManagementHierarchyController::class, 'createManagement'])->middleware("permission:" . Permission::ORGANIZATION_MANAGEMENT_CREATE());
    Route::post('/create-department', [ManagementHierarchyController::class, 'createDepartment']);
    Route::post('/update-branch/{id}', [ManagementHierarchyController::class, 'updateBranch'])->middleware("permission:" . Permission::ORGANIZATION_BRANCH_UPDATE());
    Route::put('/update-management/{id}', [ManagementHierarchyController::class, 'updateManagement'])->middleware("permission:" . Permission::ORGANIZATION_MANAGEMENT_UPDATE());

    Route::post('/make-branch-main/{id}', [ManagementHierarchyController::class, 'makeBranchMain'])->middleware("permission:" . Permission::ORGANIZATION_BRANCH_UPDATE());
    Route::post('/', [ManagementHierarchyController::class, 'store']);
    Route::get('/{id}', [ManagementHierarchyController::class, 'show'])->middleware("permission:" . Permission::ORGANIZATION_BRANCH_VIEW() . '|' . Permission::ORGANIZATION_MANAGEMENT_VIEW());
    Route::put('/{id}', [ManagementHierarchyController::class, 'update'])->middleware("permission:" . Permission::ORGANIZATION_BRANCH_UPDATE() . '|' . Permission::ORGANIZATION_MANAGEMENT_UPDATE());
    Route::delete('/{id}', [ManagementHierarchyController::class, 'delete'])->middleware("permission:" . Permission::ORGANIZATION_BRANCH_DELETE() . '|' . Permission::ORGANIZATION_MANAGEMENT_DELETE());

    // Widgets API - single endpoint for all widgets
});
