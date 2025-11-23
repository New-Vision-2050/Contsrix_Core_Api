<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Page\Controllers\Website\PageWebsiteController;

Route::group(['middleware' => [\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {

    Route::get('/type/{type}', [PageWebsiteController::class, 'getByType'])
        ->where('type', 'terms_conditions|privacy_policy|refund_policy|return_policy|cancellation_policy|shipping_policy|about_us|company_reliability|home');

});
