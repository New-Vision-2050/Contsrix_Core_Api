<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\JobOffer\Controllers\JobOfferController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/user/{id}', [JobOfferController::class, 'index'])->permission(Permission::PROFILE_JOB_OFFER_VIEW());
    Route::post('/', [JobOfferController::class, 'store'])->permission(Permission::PROFILE_JOB_OFFER_UPDATE());
});
