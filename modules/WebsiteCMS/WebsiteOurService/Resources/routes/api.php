<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteOurService\Controllers\WebsiteOurServiceController;
use Modules\RoleAndPermission\Enums\Permission;

Route::get('/current', [WebsiteOurServiceController::class, 'getCurrentCompany'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);
Route::get('/service-types', [WebsiteOurServiceController::class, 'getServiceTypes']);


Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    // Service types lookup
//        ->permission(Permission::WEBSITE_OUR_SERVICE_LIST());

    // Current company routes

    Route::post('/current', [WebsiteOurServiceController::class, 'updateCurrentCompany'])
        ->permission(Permission::WEBSITE_OUR_SERVICE_UPDATE());

    // Standard CRUD routes
//    Route::get('/', [WebsiteOurServiceController::class, 'index'])
//        ->permission(Permission::WEBSITE_OUR_SERVICE_LIST());
});
