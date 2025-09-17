<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\Contactinfo\Controllers\ContactinfoController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/{id}', [ContactinfoController::class, 'show'])->permission(Permission::PROFILE_CONTACT_INFO_VIEW(),Permission::PROFILE_ADDRESS_INFO_VIEW());
    Route::put('/{id}', [ContactinfoController::class, 'update'])->permission(Permission::PROFILE_CONTACT_INFO_UPDATE());
    Route::put('address/{id}', [ContactinfoController::class, 'updateAddress'])->permission(Permission::PROFILE_ADDRESS_INFO_UPDATE());

});
