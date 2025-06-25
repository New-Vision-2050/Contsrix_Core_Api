<?php

use Illuminate\Support\Facades\Route;
use Modules\SubscriptionSystem\Feature\Controllers\FeatureController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [FeatureController::class, 'index']);
    Route::post('/', [FeatureController::class, 'store']);
    Route::get('/{id}', [FeatureController::class, 'show']);
    Route::put('/{id}', [FeatureController::class, 'update']);
    Route::delete('/{id}', [FeatureController::class, 'delete']);
});
