<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Controllers\SubscriptionController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [SubscriptionController::class, 'index']);
    Route::post('/', [SubscriptionController::class, 'store']);
    Route::get('/{id}', [SubscriptionController::class, 'show']);
    Route::put('/{id}', [SubscriptionController::class, 'update']);
    Route::delete('/{id}', [SubscriptionController::class, 'delete']);
});
