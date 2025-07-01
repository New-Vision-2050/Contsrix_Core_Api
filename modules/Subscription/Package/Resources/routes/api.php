<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Package\Controllers\PackageController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [PackageController::class, 'index']);
    Route::post('/', [PackageController::class, 'store']);
    Route::get('/{id}', [PackageController::class, 'show']);
    Route::put('/{id}', [PackageController::class, 'update']);
    Route::delete('/{id}', [PackageController::class, 'delete']);
});
