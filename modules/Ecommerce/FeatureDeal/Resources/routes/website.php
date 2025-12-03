<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\FeatureDeal\Controllers\Website\FeatureDealWebsiteController;

Route::group(['middleware' => [\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [FeatureDealWebsiteController::class, 'index']);
    Route::get('/{id}', [FeatureDealWebsiteController::class, 'show'])->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
});

