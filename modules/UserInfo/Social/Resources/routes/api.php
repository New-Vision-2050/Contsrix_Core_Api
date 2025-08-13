<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\Social\Controllers\SocialController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/{id}', [SocialController::class, 'show'])->permission(Permission::PROFILE_SOCIAL_MEDIA_VIEW());
    Route::put('/{id}', [SocialController::class, 'update'])->permission(Permission::PROFILE_SOCIAL_MEDIA_UPDATE());
});
