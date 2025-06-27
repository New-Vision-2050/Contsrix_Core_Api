<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Module\Controllers\ModuleController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [ModuleController::class, 'index']);
    Route::post('/', [ModuleController::class, 'store']);
    Route::get('/{id}', [ModuleController::class, 'show']);
    Route::put('/{id}', [ModuleController::class, 'update']);
    Route::delete('/{id}', [ModuleController::class, 'delete']);
});
