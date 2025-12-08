<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Controllers\WebsiteTermAndConditionController;
use Modules\RoleAndPermission\Enums\Permission;


Route::get('/current', [WebsiteTermAndConditionController::class, 'getForCurrentCompany'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
//    Route::get('/', [WebsiteTermAndConditionController::class, 'index'])
//        ->permission(Permission::WEBSITE_TERM_AND_CONDITION_LIST());
    Route::put('/current', [WebsiteTermAndConditionController::class, 'updateForCurrentCompany'])
        ->permission(Permission::WEBSITE_TERM_AND_CONDITION_UPDATE());
//    Route::post('/export', [WebsiteTermAndConditionController::class, 'export'])
//        ->permission(Permission::WEBSITE_TERM_AND_CONDITION_EXPORT());
//
//    Route::get('/{id}', [WebsiteTermAndConditionController::class, 'show'])
//        ->permission(Permission::WEBSITE_TERM_AND_CONDITION_VIEW());
//    Route::put('/{id}', [WebsiteTermAndConditionController::class, 'update'])
//        ->permission(Permission::WEBSITE_TERM_AND_CONDITION_UPDATE());
//    Route::delete('/{id}', [WebsiteTermAndConditionController::class, 'delete'])
//        ->permission(Permission::WEBSITE_TERM_AND_CONDITION_DELETE());
});
