<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\Qualification\Controllers\QualificationController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/user/{id}', [QualificationController::class, 'index'])->permission(Permission::PROFILE_QUALIFICATION_VIEW());
    Route::post('/', [QualificationController::class, 'store'])->permission(Permission::PROFILE_QUALIFICATION_CREATE());
    Route::get('/{id}', [QualificationController::class, 'show'])->permission(Permission::PROFILE_QUALIFICATION_VIEW());
    Route::post('/{id}', [QualificationController::class, 'update'])->permission(Permission::PROFILE_QUALIFICATION_UPDATE());
    Route::delete('/{id}', [QualificationController::class, 'delete'])->permission(Permission::PROFILE_QUALIFICATION_DELETE());
});
