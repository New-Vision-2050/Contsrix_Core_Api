<?php

use Illuminate\Support\Facades\Route;
use Modules\ProcedureSetting\Controllers\ProcedureSettingController;
use Modules\ProcedureSetting\Controllers\ProcedureSettingStepController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [ProcedureSettingController::class, 'index']);
    Route::post('/', [ProcedureSettingController::class, 'store']);
    Route::post('/work_flows', [ProcedureSettingController::class, 'toggleBranchWorkFlows']);
    Route::post('/export', [ProcedureSettingController::class, 'export']);

    Route::get('/{id}', [ProcedureSettingController::class, 'show']);
    Route::put('/{id}', [ProcedureSettingController::class, 'update']);
    Route::delete('/{id}', [ProcedureSettingController::class, 'delete']);

    // Procedure Setting Steps
    Route::get('/{procedureSettingId}/steps', [ProcedureSettingStepController::class, 'index']);
    Route::post('/{procedureSettingId}/steps', [ProcedureSettingStepController::class, 'store']);
    Route::get('/{procedureSettingId}/steps/{stepId}', [ProcedureSettingStepController::class, 'show']);
    Route::put('/{procedureSettingId}/steps/{stepId}', [ProcedureSettingStepController::class, 'update']);
    Route::delete('/{procedureSettingId}/steps/{stepId}', [ProcedureSettingStepController::class, 'delete']);
});
