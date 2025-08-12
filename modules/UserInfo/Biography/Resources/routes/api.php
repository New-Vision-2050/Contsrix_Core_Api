<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\Biography\Controllers\BiographyController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/user/{id}', [BiographyController::class, 'index'])->permission(Permission::PROFILE_CV_VIEW());
    Route::post('/', [BiographyController::class, 'store'])->permission(Permission::PROFILE_CV_UPDATE());
    Route::get('/{id}', [BiographyController::class, 'show'])->permission(Permission::PROFILE_CV_VIEW());
    Route::put('/{id}', [BiographyController::class, 'update'])->permission(Permission::PROFILE_CV_UPDATE());
    Route::delete('/{id}', [BiographyController::class, 'delete'])->permission(Permission::PROFILE_CV_UPDATE());
});
