<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\InternalProcessType\Controllers\AdminInternalProcessTypeController;
use Modules\Shared\InternalProcessType\Controllers\InternalProcessTypeController;

Route::group([
    'middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class],
], function () {
    Route::get('/admin/internal_procedure_setting_forms', [AdminInternalProcessTypeController::class, 'formOptions']);
    Route::get('/admin/forms_conditions', [AdminInternalProcessTypeController::class, 'formsConditions']);

    Route::prefix('admin/internal_procedure_settings')->group(function () {
        Route::get('/', [AdminInternalProcessTypeController::class, 'index']);
        Route::post('/', [AdminInternalProcessTypeController::class, 'store']);
        Route::put('/{id}', [AdminInternalProcessTypeController::class, 'update']);
        Route::delete('/{id}', [AdminInternalProcessTypeController::class, 'destroy']);
    });

    Route::get('/internal-process-types', [InternalProcessTypeController::class, 'index']);
});
