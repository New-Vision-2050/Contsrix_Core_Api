<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\ProjectType\Controllers\OrderPermitController;
use Modules\Project\ProjectType\Controllers\OrderPermitDepartmentController;
use Modules\Project\ProjectType\Controllers\OrderPermitProcedureController;
use Modules\Project\ProjectType\Controllers\OrderPermitTaskController;
use Modules\Project\ProjectType\Controllers\ReportFormController;
use Modules\Project\ProjectType\Controllers\OrderPermitTasksSettingController;
use Modules\Project\ProjectType\Controllers\ProjectManagementController;
use Modules\Project\ProjectType\Controllers\ProjectDistrictController;
use Modules\Project\ProjectType\Controllers\CompletionStatusController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {

    // Order permits (order_permit table)
    Route::get('/order-permits', [OrderPermitController::class, 'index'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    // Route::post('/order-permits', [OrderPermitController::class, 'store'])
    //     ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::get('/order-permits/{id}', [OrderPermitController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/order-permits/{id}', [OrderPermitController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::delete('/order-permits/{id}', [OrderPermitController::class, 'delete'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());


    // Order permit departments
    Route::get('/order-permit-departments', [OrderPermitDepartmentController::class, 'index'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::post('/order-permit-departments', [OrderPermitDepartmentController::class, 'store'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::get('/order-permit-departments/{id}', [OrderPermitDepartmentController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/order-permit-departments/{id}', [OrderPermitDepartmentController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::delete('/order-permit-departments/{id}', [OrderPermitDepartmentController::class, 'delete'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Order permit procedures
    Route::get('/order-permit-procedures', [OrderPermitProcedureController::class, 'index'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::post('/order-permit-procedures', [OrderPermitProcedureController::class, 'store'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::get('/order-permit-procedures/{id}', [OrderPermitProcedureController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/order-permit-procedures/{id}', [OrderPermitProcedureController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::delete('/order-permit-procedures/{id}', [OrderPermitProcedureController::class, 'delete'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Order permit tasks
    Route::get('/order-permit-tasks', [OrderPermitTaskController::class, 'index'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::post('/order-permit-tasks', [OrderPermitTaskController::class, 'store'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::get('/order-permit-tasks/{id}', [OrderPermitTaskController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/order-permit-tasks/{id}', [OrderPermitTaskController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::delete('/order-permit-tasks/{id}', [OrderPermitTaskController::class, 'delete'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Order permit report forms
    Route::get('/order-permit-report-forms', [ReportFormController::class, 'index'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::post('/order-permit-report-forms', [ReportFormController::class, 'store'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::get('/order-permit-report-forms/{id}', [ReportFormController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/order-permit-report-forms/{id}', [ReportFormController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::delete('/order-permit-report-forms/{id}', [ReportFormController::class, 'delete'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Order permit tasks setting
    Route::get('/order-permit-tasks-setting', [OrderPermitTasksSettingController::class, 'index'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::post('/order-permit-tasks-setting', [OrderPermitTasksSettingController::class, 'store'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::get('/order-permit-tasks-setting/{id}', [OrderPermitTasksSettingController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/order-permit-tasks-setting/{id}', [OrderPermitTasksSettingController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::delete('/order-permit-tasks-setting/{id}', [OrderPermitTasksSettingController::class, 'delete'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Project managements (project_managements table)
    Route::get('/project-managements', [ProjectManagementController::class, 'index'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::post('/project-managements', [ProjectManagementController::class, 'store'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::get('/project-managements/{id}', [ProjectManagementController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/project-managements/{id}', [ProjectManagementController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::delete('/project-managements/{id}', [ProjectManagementController::class, 'delete'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Project districts (projects_districts table)
    Route::get('/projects-districts', [ProjectDistrictController::class, 'index'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::post('/projects-districts', [ProjectDistrictController::class, 'store'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::get('/projects-districts/{id}', [ProjectDistrictController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/projects-districts/{id}', [ProjectDistrictController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::delete('/projects-districts/{id}', [ProjectDistrictController::class, 'delete'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Completion phases and statuses lookup
    Route::get('/completion-data', [CompletionStatusController::class, 'completionData']);

    Route::get('/project-completion-phases', [CompletionStatusController::class, 'projectPhases'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::get('/project-phase-statuses', [CompletionStatusController::class, 'projectStatuses'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::get('/connection-completion-phases', [CompletionStatusController::class, 'connectionPhases'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::get('/connection-phase-statuses', [CompletionStatusController::class, 'connectionStatuses'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
});
