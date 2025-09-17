<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\UserAbout\Controllers\UserAboutController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/user/{id}', [UserAboutController::class, 'index'])->permission(Permission::PROFILE_ABOUT_ME_VIEW());
    Route::post('/', [UserAboutController::class, 'store'])->permission(Permission::PROFILE_ABOUT_ME_UPDATE());
});
