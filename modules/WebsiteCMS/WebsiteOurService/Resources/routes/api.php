<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteOurService\Controllers\WebsiteOurServiceController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    // Service types lookup
//    Route::get('/service-types', [WebsiteOurServiceController::class, 'getServiceTypes'])
//        ->permission(Permission::WEBSITE_OUR_SERVICE_LIST());

    // Current company routes
    Route::get('/current', [WebsiteOurServiceController::class, 'getCurrentCompany'])
        ->permission(Permission::WEBSITE_OUR_SERVICE_VIEW());
    Route::post('/current', [WebsiteOurServiceController::class, 'updateCurrentCompany'])
        ->permission(Permission::WEBSITE_OUR_SERVICE_UPDATE());

    // Standard CRUD routes
//    Route::get('/', [WebsiteOurServiceController::class, 'index'])
//        ->permission(Permission::WEBSITE_OUR_SERVICE_LIST());
});
