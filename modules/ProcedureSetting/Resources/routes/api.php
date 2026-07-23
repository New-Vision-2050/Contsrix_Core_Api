<?php

use Illuminate\Support\Facades\Route;
use Modules\ProcedureSetting\Controllers\InternalProcedureSettingController;
use Modules\ProcedureSetting\Controllers\ProcedureSettingController;
use Modules\ProcedureSetting\Controllers\ProcedureSettingStepController;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

Route::group(['middleware' => ['auth:api', InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [ProcedureSettingController::class, 'index']);
    Route::post('/', [ProcedureSettingController::class, 'store']);
    Route::post('/work_flows', [ProcedureSettingController::class, 'toggleBranchWorkFlows']);
    Route::post('/export', [ProcedureSettingController::class, 'export']);

    Route::get('/approval-responsibles', [ProcedureSettingController::class, 'approvalResponsibles']);
    Route::get('/types', [ProcedureSettingController::class, 'types']);

    // Condition definitions for frontend rendering
    Route::get('/forms-conditions', [InternalProcedureSettingController::class, 'formsConditions']);
    Route::get('/job-attributes', [InternalProcedureSettingController::class, 'jobAttributes']);

    // Internal Procedure Settings (child procedure settings with form key)
    Route::get('/internal-procedures', [InternalProcedureSettingController::class, 'index']);

    Route::get('/{id}', [ProcedureSettingController::class, 'show'])->whereUuid('id');
    Route::put('/{id}', [ProcedureSettingController::class, 'update'])->whereUuid('id');
    Route::delete('/{id}', [ProcedureSettingController::class, 'delete'])->whereUuid('id');
    Route::get('/{id}/available-forms', [InternalProcedureSettingController::class, 'availableForms'])->whereUuid('id');
    Route::get('/{id}/internal-procedures/by-form/{formKey}', [InternalProcedureSettingController::class, 'showByForm'])->whereUuid('id');
    Route::post('/internal-procedures', [InternalProcedureSettingController::class, 'store']);
    Route::post('/{id}/internal-procedures/{internalProcedureId}', [InternalProcedureSettingController::class, 'update'])
        ->whereUuid('id')
        ->whereUuid('internalProcedureId');
    Route::put('/{id}/internal-procedures/{internalProcedureId}/set-status', [InternalProcedureSettingController::class, 'setStatus'])
        ->whereUuid('id')
        ->whereUuid('internalProcedureId');
    Route::delete('/{id}/internal-procedures/{internalProcedureId}', [InternalProcedureSettingController::class, 'destroy'])
        ->whereUuid('id')
        ->whereUuid('internalProcedureId');

    // Procedure Setting Steps
    Route::get('/{procedureSettingId}/steps', [ProcedureSettingStepController::class, 'index'])->whereUuid('procedureSettingId');
    Route::post('/{procedureSettingId}/steps', [ProcedureSettingStepController::class, 'store'])->whereUuid('procedureSettingId');
    Route::get('/{procedureSettingId}/steps/{stepId}', [ProcedureSettingStepController::class, 'show'])
        ->whereUuid('procedureSettingId')
        ->whereNumber('stepId');
    Route::post('/{procedureSettingId}/steps/{stepId}', [ProcedureSettingStepController::class, 'update'])
        ->whereUuid('procedureSettingId')
        ->whereNumber('stepId');
    Route::delete('/{procedureSettingId}/steps/{stepId}', [ProcedureSettingStepController::class, 'delete'])
        ->whereUuid('procedureSettingId')
        ->whereNumber('stepId');
});
