<?php

use Illuminate\Support\Facades\Route;
use Modules\ProcedureSetting\Controllers\InternalProcedureSettingController;
use Modules\ProcedureSetting\Controllers\ProcedureSettingController;
use Modules\ProcedureSetting\Controllers\ProcedureSettingStepController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [ProcedureSettingController::class, 'index']);
    Route::post('/', [ProcedureSettingController::class, 'store']);
    Route::post('/work_flows', [ProcedureSettingController::class, 'toggleBranchWorkFlows']);
    Route::post('/export', [ProcedureSettingController::class, 'export']);

    Route::get('/approval-responsibles', [ProcedureSettingController::class, 'approvalResponsibles']);
    Route::get('/types', [ProcedureSettingController::class, 'types']);

    Route::get('/{id}', [ProcedureSettingController::class, 'show']);
    Route::put('/{id}', [ProcedureSettingController::class, 'update']);
    Route::delete('/{id}', [ProcedureSettingController::class, 'delete']);

    // Internal Procedure Settings (child procedure settings with form key)
    Route::get('/{id}/available-forms', [InternalProcedureSettingController::class, 'availableForms']);
    Route::get('/{id}/internal-procedures', [InternalProcedureSettingController::class, 'index']);
    Route::post('/{id}/internal-procedures', [InternalProcedureSettingController::class, 'store']);
    Route::put('/{id}/internal-procedures/{internalProcedureId}', [InternalProcedureSettingController::class, 'update']);
    Route::delete('/{id}/internal-procedures/{internalProcedureId}', [InternalProcedureSettingController::class, 'destroy']);

    // Procedure Setting Steps
    Route::get('/{procedureSettingId}/steps', [ProcedureSettingStepController::class, 'index']);
    Route::post('/{procedureSettingId}/steps', [ProcedureSettingStepController::class, 'store']);
    Route::get('/{procedureSettingId}/steps/{stepId}', [ProcedureSettingStepController::class, 'show']);
    Route::put('/{procedureSettingId}/steps/{stepId}', [ProcedureSettingStepController::class, 'update']);
    Route::delete('/{procedureSettingId}/steps/{stepId}', [ProcedureSettingStepController::class, 'delete']);
});
