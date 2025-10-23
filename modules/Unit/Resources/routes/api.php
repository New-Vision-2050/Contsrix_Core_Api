<?php

use Illuminate\Support\Facades\Route;
use Modules\Unit\Controllers\UnitController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [UnitController::class, 'index']);
    Route::post('/', [UnitController::class, 'store']);
    Route::post('/export', [UnitController::class, 'export']);

    Route::get('/{id}', [UnitController::class, 'show']);
    Route::put('/{id}', [UnitController::class, 'update']);
    Route::delete('/{id}', [UnitController::class, 'delete']);
});
