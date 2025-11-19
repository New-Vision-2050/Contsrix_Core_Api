<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoProduct\Controllers\Website\EcoProductWebsiteController;

Route::group(['middleware' => [\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [EcoProductWebsiteController::class, 'index']);
    Route::get('/{id}', [EcoProductWebsiteController::class, 'show']);

});

