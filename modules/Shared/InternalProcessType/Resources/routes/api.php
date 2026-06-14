<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\InternalProcessType\Controllers\AdminInternalProcessTypeController;
use Modules\Shared\InternalProcessType\Controllers\InternalProcessTypeController;

Route::group([
    'middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class],
], function () {
    Route::prefix('admin/internal-process-types')->group(function () {
        Route::get('/', [AdminInternalProcessTypeController::class, 'index']);
        Route::post('/', [AdminInternalProcessTypeController::class, 'store']);
        Route::put('/{id}', [AdminInternalProcessTypeController::class, 'update']);
        Route::delete('/{id}', [AdminInternalProcessTypeController::class, 'destroy']);
    });

    Route::get('/internal-process-types', [InternalProcessTypeController::class, 'index']);
});
