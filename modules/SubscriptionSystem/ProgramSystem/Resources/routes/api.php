<?php

use Illuminate\Support\Facades\Route;
use Modules\SubscriptionSystem\ProgramSystem\Controllers\ProgramSystemController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [ProgramSystemController::class, 'index']);
    Route::get('/widget', [ProgramSystemController::class, 'widget']);
    Route::post('/', [ProgramSystemController::class, 'store']);
    Route::get('/{id}', [ProgramSystemController::class, 'show']);
    Route::put('/{id}', [ProgramSystemController::class, 'update']);
    Route::put('/{id}/toggle-status', [ProgramSystemController::class, 'toggleIsActive']);
    Route::delete('/{id}', [ProgramSystemController::class, 'delete']);
});
