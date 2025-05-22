<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\JobType\Controllers\JobTypeController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [JobTypeController::class, 'index']);
    Route::get('/list', [JobTypeController::class, 'listSimple']);
    Route::post('/export', [JobTypeController::class, 'export'])->name('job-type.export');
    Route::post('/', [JobTypeController::class, 'store']);
    Route::get('/{id}', [JobTypeController::class, 'show']);
    Route::put('/{id}', [JobTypeController::class, 'update']);
    Route::delete('/{id}', [JobTypeController::class, 'delete']);
    Route::patch('/{id}/status', [JobTypeController::class, 'changeStatus']);
});
