<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteHomePage\Controllers\WebsiteHomePageController;

Route::group(['middleware' => [ \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    // Get home page data (HomePageSetting, OurServices, Featured Projects)
    Route::get('/data', [WebsiteHomePageController::class, 'getHomePageData']);


});
