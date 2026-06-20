<?php

use Illuminate\Support\Facades\Route;
use Modules\Stakeholder\Controllers\StakeholderController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [StakeholderController::class, 'index']);
    Route::post('/', [StakeholderController::class, 'store']);

    Route::get('/{id}', [StakeholderController::class, 'show']);
    Route::put('/{id}', [StakeholderController::class, 'update']);
    Route::delete('/{id}', [StakeholderController::class, 'delete']);
});
