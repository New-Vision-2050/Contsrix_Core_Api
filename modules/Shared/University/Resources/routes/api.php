<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\University\Controllers\UniversityController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [UniversityController::class, 'index']);
    Route::post('/', [UniversityController::class, 'store']);
    Route::get('/{id}', [UniversityController::class, 'show']);
    Route::put('/{id}', [UniversityController::class, 'update']);
    Route::delete('/{id}', [UniversityController::class, 'delete']);
});
