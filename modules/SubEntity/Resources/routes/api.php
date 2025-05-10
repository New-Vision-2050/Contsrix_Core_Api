<?php

use Illuminate\Support\Facades\Route;
use Modules\SubEntity\Controllers\SubEntityController;
use Modules\SubEntity\Controllers\SuperEntityController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [SubEntityController::class, 'index']);
    Route::post('/', [SubEntityController::class, 'store']);
    Route::get('/{id}', [SubEntityController::class, 'show']);
    Route::put('/{id}', [SubEntityController::class, 'update']);
    Route::delete('/{id}', [SubEntityController::class, 'delete']);
    Route::get('/{id}/show/attributes', [SubEntityController::class, 'showAttributes']);
    Route::put('/{id}/update/attributes', [SubEntityController::class, 'updateAttributes']);



    Route::get('/programs/sub_tables', [SubEntityController::class, 'getByProgram']);
    Route::get('/super_entity/sub_tables', [SubEntityController::class, 'getBySuperEntity']);

    // super entity attributes
    Route::get('/super_entities/list', [SuperEntityController::class, 'index']);

    Route::get('/super_entities/{id}/attributes', [SuperEntityController::class, 'getAvailableAttributes']);
});
