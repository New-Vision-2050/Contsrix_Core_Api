<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\UserExperience\Controllers\UserExperienceController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/user/{id}', [UserExperienceController::class, 'index']);
    Route::post('/', [UserExperienceController::class, 'store']);
    Route::get('/{id}', [UserExperienceController::class, 'show']);
    Route::put('/{id}', [UserExperienceController::class, 'update']);
    Route::delete('/{id}', [UserExperienceController::class, 'delete']);
});
