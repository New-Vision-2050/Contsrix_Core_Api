<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\UserEducationalCourse\Controllers\UserEducationalCourseController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/user/{id}', [UserEducationalCourseController::class, 'index']);
    Route::post('/', [UserEducationalCourseController::class, 'store']);
    Route::get('/{id}', [UserEducationalCourseController::class, 'show']);
    Route::put('/{id}', [UserEducationalCourseController::class, 'update']);
    Route::delete('/{id}', [UserEducationalCourseController::class, 'delete']);
});
