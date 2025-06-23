<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Controllers\SubscriptionController;
use Modules\Subscription\Controllers\FeatureController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [SubscriptionController::class, 'index']);
    Route::post('/', [SubscriptionController::class, 'store']);
    Route::get('/{id}', [SubscriptionController::class, 'show']);
    Route::put('/{id}', [SubscriptionController::class, 'update']);
    Route::delete('/{id}', [SubscriptionController::class, 'delete']);

    // Feature routes
    Route::prefix('features')->group(function () {
        Route::post('/permissions', [FeatureController::class, 'getFeaturePermissions']);
    });
});
