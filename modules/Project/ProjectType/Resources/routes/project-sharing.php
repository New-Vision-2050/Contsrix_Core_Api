<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\ProjectType\Controllers\ProjectSharingWorkOrderController;
use Modules\Project\ProjectType\Controllers\ProjectSharingDepartmentController;
use Modules\Project\ProjectType\Controllers\ProjectSharingProcedureController;
use Modules\Project\ProjectType\Controllers\ProjectSharingTaskController;
use Modules\Project\ProjectType\Controllers\ReportFormController;
use Modules\Project\ProjectType\Controllers\ProjectSharingTasksSettingController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {

    // Project Sharing Work Orders
    Route::get('/project-sharing-work-orders', [ProjectSharingWorkOrderController::class, 'index'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::post('/project-sharing-work-orders', [ProjectSharingWorkOrderController::class, 'store'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::get('/project-sharing-work-orders/{id}', [ProjectSharingWorkOrderController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/project-sharing-work-orders/{id}', [ProjectSharingWorkOrderController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::delete('/project-sharing-work-orders/{id}', [ProjectSharingWorkOrderController::class, 'delete'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Project Sharing Departments
    Route::get('/project-sharing-department', [ProjectSharingDepartmentController::class, 'index'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::post('/project-sharing-department', [ProjectSharingDepartmentController::class, 'store'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::get('/project-sharing-department/{id}', [ProjectSharingDepartmentController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/project-sharing-department/{id}', [ProjectSharingDepartmentController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::delete('/project-sharing-department/{id}', [ProjectSharingDepartmentController::class, 'delete'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Project Sharing Procedures
    Route::get('/project-sharing-procedure', [ProjectSharingProcedureController::class, 'index'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::post('/project-sharing-procedure', [ProjectSharingProcedureController::class, 'store'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::get('/project-sharing-procedure/{id}', [ProjectSharingProcedureController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/project-sharing-procedure/{id}', [ProjectSharingProcedureController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::delete('/project-sharing-procedure/{id}', [ProjectSharingProcedureController::class, 'delete'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Project Sharing Tasks
    Route::get('/project-sharing-tasks', [ProjectSharingTaskController::class, 'index'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::post('/project-sharing-tasks', [ProjectSharingTaskController::class, 'store'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::get('/project-sharing-tasks/{id}', [ProjectSharingTaskController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/project-sharing-tasks/{id}', [ProjectSharingTaskController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::delete('/project-sharing-tasks/{id}', [ProjectSharingTaskController::class, 'delete'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Report Forms
    Route::get('/report-forms', [ReportFormController::class, 'index'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::post('/report-forms', [ReportFormController::class, 'store'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::get('/report-forms/{id}', [ReportFormController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/report-forms/{id}', [ReportFormController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::delete('/report-forms/{id}', [ReportFormController::class, 'delete'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Project Sharing Tasks Setting
    Route::get('/project-sharing-tasks-setting', [ProjectSharingTasksSettingController::class, 'index'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::post('/project-sharing-tasks-setting', [ProjectSharingTasksSettingController::class, 'store'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::get('/project-sharing-tasks-setting/{id}', [ProjectSharingTasksSettingController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/project-sharing-tasks-setting/{id}', [ProjectSharingTasksSettingController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::delete('/project-sharing-tasks-setting/{id}', [ProjectSharingTasksSettingController::class, 'delete'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
});
