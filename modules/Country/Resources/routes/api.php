<?php

use Illuminate\Support\Facades\Route;
use Modules\Country\Controllers\CountryController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [CountryController::class, 'index']);
    Route::get('/get-country-states-cities', [CountryController::class, 'getCountryWithStateWithCity']);
    Route::get('/cities', [CountryController::class, 'getCity']);
    Route::get('/get-states-by-branch', [CountryController::class, 'getStatesByCurrentAuthUserBranch'])->middleware(\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class);
    Route::get('/currencies', [CountryController::class, 'currency']);

    Route::post('/', [CountryController::class, 'store']);
    Route::get('/{id}', [CountryController::class, 'show']);
    Route::put('/{id}', [CountryController::class, 'update']);
    Route::delete('/{id}', [CountryController::class, 'delete']);
});
