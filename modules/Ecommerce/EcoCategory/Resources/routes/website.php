<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoCategory\Controllers\Website\EcoCategoryWebsiteController;

Route::group(['middleware' => [\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [EcoCategoryWebsiteController::class, 'index']);
    Route::get('/{id}', [EcoCategoryWebsiteController::class, 'show']);
});

