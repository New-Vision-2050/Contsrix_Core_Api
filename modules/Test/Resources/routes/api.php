<?php

use Illuminate\Support\Facades\Route;
use Modules\Test\Controllers\TestController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [TestController::class, 'index']);
    Route::post('/', [TestController::class, 'store']);
    Route::post('/export', [TestController::class, 'export']);

    Route::get('/{id}', [TestController::class, 'show']);
    Route::put('/{id}', [TestController::class, 'update']);
    Route::delete('/{id}', [TestController::class, 'delete']);
});
