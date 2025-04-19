<?php

use Illuminate\Support\Facades\Route;
use Modules\AdminRequest\Controllers\AdminRequestController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [AdminRequestController::class, 'index']);
    Route::get('/{id}', [AdminRequestController::class, 'show']);
    Route::post('/{id}/take-action', [AdminRequestController::class, 'takeActionRequest']);
    Route::delete('/{id}', [AdminRequestController::class, 'delete']);
});
