<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\ProjectManagement\Controllers\ProjectManagementController;
use Modules\Project\ProjectManagement\Controllers\ProjectShareController;
use Modules\Project\ProjectManagement\Controllers\ProjectEmployeeController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [ProjectManagementController::class, 'index'])
        ->permission(Permission::PROJECT_MANAGEMENT_LIST());
    Route::post('/', [ProjectManagementController::class, 'store'])
        ->permission(Permission::PROJECT_MANAGEMENT_CREATE());
    Route::post('/export', [ProjectManagementController::class, 'export'])
        ->permission(Permission::PROJECT_MANAGEMENT_EXPORT());
    Route::get('/widgets', [ProjectManagementController::class, 'widgets'])
        ->permission(Permission::PROJECT_MANAGEMENT_LIST());

    // Project Sharing Routes
    Route::prefix('sharing')->group(function () {
        Route::post('/share', [ProjectShareController::class, 'shareProject']);
        Route::get('/projects/{id}/shares', [ProjectShareController::class, 'getProjectShares']);
        Route::get('/invitations/pending', [ProjectShareController::class, 'getPendingInvitations']);
        Route::post('/invitations/respond', [ProjectShareController::class, 'respondToShare']);
        Route::delete('/shares/{id}', [ProjectShareController::class, 'removeShare']);
        Route::get('/shared-with-me', [ProjectShareController::class, 'getSharedWithMe']);
    });

    // Project Employees Routes
    Route::prefix('employees')->group(function () {
        Route::post('/assign', [ProjectEmployeeController::class, 'assignEmployees']);
        Route::get('/project/{project_id}', [ProjectEmployeeController::class, 'getProjectEmployees']);
        Route::delete('/{id}', [ProjectEmployeeController::class, 'removeEmployee']);
    });

    Route::get('/{id}', [ProjectManagementController::class, 'show'])
        ->permission(Permission::PROJECT_MANAGEMENT_VIEW());
    Route::put('/{id}', [ProjectManagementController::class, 'update'])
        ->permission(Permission::PROJECT_MANAGEMENT_UPDATE());
    Route::delete('/{id}', [ProjectManagementController::class, 'delete'])
        ->permission(Permission::PROJECT_MANAGEMENT_DELETE());


});
