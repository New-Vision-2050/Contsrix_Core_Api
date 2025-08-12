<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\UserExperience\Controllers\UserExperienceController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/user/{id}', [UserExperienceController::class, 'index'])->permission(Permission::PROFILE_EXPERIENCE_VIEW());
    Route::post('/', [UserExperienceController::class, 'store'])->permission(Permission::PROFILE_EXPERIENCE_CREATE());
    Route::get('/{id}', [UserExperienceController::class, 'show'])->permission(Permission::PROFILE_EXPERIENCE_VIEW());
    Route::put('/{id}', [UserExperienceController::class, 'update'])->permission(Permission::PROFILE_EXPERIENCE_UPDATE());
    Route::delete('/{id}', [UserExperienceController::class, 'delete'])->permission(Permission::PROFILE_EXPERIENCE_DELETE());
});
