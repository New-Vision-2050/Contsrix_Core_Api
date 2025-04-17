<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\Period\Controllers\PeriodController;
use Illuminate\Support\Facades\Artisan;


Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [PeriodController::class, 'index']);
    Route::post('/', [PeriodController::class, 'store']);
    Route::get('/{id}', [PeriodController::class, 'show']);
    Route::put('/{id}', [PeriodController::class, 'update']);
    Route::delete('/{id}', [PeriodController::class, 'delete']);
});
