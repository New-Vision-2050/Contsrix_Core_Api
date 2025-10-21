<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoLanguage\Controllers\EcoLanguageController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [EcoLanguageController::class, 'index']);
    Route::post('/', [EcoLanguageController::class, 'upsert']);
    Route::post('/export', [EcoLanguageController::class, 'export']);

    Route::get('/{id}', [EcoLanguageController::class, 'show']);
    Route::delete('/{id}', [EcoLanguageController::class, 'delete']);
});
