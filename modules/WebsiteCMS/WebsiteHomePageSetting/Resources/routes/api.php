<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Controllers\WebsiteHomePageSettingController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    // Get current company's home page setting
    Route::get('/current', [WebsiteHomePageSettingController::class, 'show']);
    
    // Update current company's home page setting
    Route::post('/current', [WebsiteHomePageSettingController::class, 'update']);
});
