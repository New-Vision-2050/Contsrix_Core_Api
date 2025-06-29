<?php

use Illuminate\Support\Facades\Route;
use Modules\SubscriptionSystem\ProgramSystem\Controllers\ProgramSystemController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [ProgramSystemController::class, 'index']);
    Route::get('/widget', [ProgramSystemController::class, 'widget']);
    Route::post('/', [ProgramSystemController::class, 'store']);
    Route::put('/{id}', [ProgramSystemController::class, 'update'])->whereUuid('id');
    Route::put('/{id}/toggle-status', [ProgramSystemController::class, 'toggleIsActive'])->whereUuid('id');
    Route::delete('/{id}', [ProgramSystemController::class, 'delete'])->whereUuid('id');
    Route::get('/{id}', [ProgramSystemController::class, 'show'])->whereUuid('id');
});
