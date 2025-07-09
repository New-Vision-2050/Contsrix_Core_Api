<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Package\Controllers\PackageController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [PackageController::class, 'index']);
    Route::get('/counts', [PackageController::class, 'counts']);
    Route::post('/', [PackageController::class, 'store']);
    Route::put('/{id}/status', [PackageController::class, 'updateStatus']);
    Route::post('/attach-features', [PackageController::class, 'attachFeatures']);
    Route::get('/{id}', [PackageController::class, 'show']);
    Route::put('/{id}', [PackageController::class, 'update']);
    Route::delete('/{id}', [PackageController::class, 'delete']);
    Route::post('/{package}/assign-permissions', [PackageController::class, 'syncPermissions']);
    Route::get('/{package}/permissions', [PackageController::class, 'getPermissions']);
});
