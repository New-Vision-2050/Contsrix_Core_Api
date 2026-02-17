<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\ProjectType\Controllers\ProjectTypeController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [ProjectTypeController::class, 'index']);
    Route::get('/filter', [ProjectTypeController::class, 'getByFilter']);
    Route::post('/', [ProjectTypeController::class, 'store']);
    Route::post('/second-level', [ProjectTypeController::class, 'createSecondLevel']);
    Route::post('/export', [ProjectTypeController::class, 'export']);
    Route::get('/roots', [ProjectTypeController::class, 'getRootProjectTypes']);

    Route::get('/{id}', [ProjectTypeController::class, 'show']);
    Route::get('/{id}/children', [ProjectTypeController::class, 'getDirectChildren']);
    Route::get('/{id}/schemas', [ProjectTypeController::class, 'getSchemas']);
    Route::put('/{id}', [ProjectTypeController::class, 'update']);
    Route::delete('/{id}', [ProjectTypeController::class, 'delete']);
});
