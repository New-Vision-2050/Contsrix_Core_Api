<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteContactInfo\Controllers\WebsiteContactInfoController;
use Modules\RoleAndPermission\Enums\Permission;

Route::get('/current', [WebsiteContactInfoController::class, 'getCurrentCompanyContactInfo'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    // Current company contact info endpoints

    Route::put('/current', [WebsiteContactInfoController::class, 'updateCurrentCompanyContactInfo'])
        ->permission(Permission::WEBSITE_CONTACT_INFO_UPDATE());

    // Standard CRUD endpoints
//    Route::get('/', [WebsiteContactInfoController::class, 'index'])
//        ->permission(Permission::WEBSITE_CONTACT_INFO_LIST());
//    Route::post('/export', [WebsiteContactInfoController::class, 'export'])
//        ->permission(Permission::WEBSITE_CONTACT_INFO_EXPORT());

//    Route::get('/{id}', [WebsiteContactInfoController::class, 'show'])
//        ->permission(Permission::WEBSITE_CONTACT_INFO_VIEW());
});
