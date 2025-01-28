<?php

use Illuminate\Support\Facades\Route;
use Modules\Country\Controllers\CountryController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [CountryController::class, 'index']);
    Route::post('/', [CountryController::class, 'store']);
    Route::get('/{id}', [CountryController::class, 'show']);
    Route::put('/{id}', [CountryController::class, 'update']);
    Route::delete('/{id}', [CountryController::class, 'delete']);
});
