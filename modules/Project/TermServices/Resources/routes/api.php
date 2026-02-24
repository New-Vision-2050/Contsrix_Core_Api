<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\TermServices\Controllers\TermServicesController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [TermServicesController::class, 'index']);
    Route::post('/', [TermServicesController::class, 'store']);
    Route::post('/export', [TermServicesController::class, 'export']);

    Route::get('/{id}', [TermServicesController::class, 'show']);
    Route::put('/{id}', [TermServicesController::class, 'update']);
    Route::delete('/{id}', [TermServicesController::class, 'delete']);
});
