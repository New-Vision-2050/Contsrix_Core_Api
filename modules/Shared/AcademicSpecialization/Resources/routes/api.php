<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\AcademicSpecialization\Controllers\AcademicSpecializationController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [AcademicSpecializationController::class, 'index']);
    Route::post('/', [AcademicSpecializationController::class, 'store']);
    Route::get('/{id}', [AcademicSpecializationController::class, 'show']);
    Route::put('/{id}', [AcademicSpecializationController::class, 'update']);
    Route::delete('/{id}', [AcademicSpecializationController::class, 'delete']);
});
