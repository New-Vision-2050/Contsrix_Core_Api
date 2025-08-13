<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\UserEducationalCourse\Controllers\UserEducationalCourseController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/user/{id}', [UserEducationalCourseController::class, 'index'])->permission(Permission::PROFILE_COURSES_VIEW());
    Route::post('/', [UserEducationalCourseController::class, 'store'])->permission(Permission::PROFILE_COURSES_CREATE());
    Route::get('/{id}', [UserEducationalCourseController::class, 'show'])->permission(Permission::PROFILE_COURSES_VIEW());
    Route::post('/{id}', [UserEducationalCourseController::class, 'update'])->permission(Permission::PROFILE_COURSES_UPDATE());
    Route::delete('/{id}', [UserEducationalCourseController::class, 'delete'])->permission(Permission::PROFILE_COURSES_DELETE());
});
