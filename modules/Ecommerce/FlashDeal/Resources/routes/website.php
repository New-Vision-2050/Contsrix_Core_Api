<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\FlashDeal\Controllers\Website\FlashDealWebsiteController;

Route::group(['middleware' => [\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [FlashDealWebsiteController::class, 'index']);
    Route::get('/{id}', [FlashDealWebsiteController::class, 'show'])->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
});

