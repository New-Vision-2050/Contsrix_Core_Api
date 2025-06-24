<?php

use Illuminate\Support\Facades\Route;
use Modules\SubscriptionSystem\Modules\Controllers\ModulesController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [ModulesController::class, 'index']);
    Route::post('/', [ModulesController::class, 'store']);
    Route::get('/{id}', [ModulesController::class, 'show']);
    Route::put('/{id}', [ModulesController::class, 'update']);
    Route::delete('/{id}', [ModulesController::class, 'delete']);
});
