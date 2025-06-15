<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\AcademicQualification\Controllers\AcademicQualificationController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [AcademicQualificationController::class, 'index']);
    Route::post('/', [AcademicQualificationController::class, 'store']);
    Route::get('/{id}', [AcademicQualificationController::class, 'show']);
    Route::put('/{id}', [AcademicQualificationController::class, 'update']);
    Route::delete('/{id}', [AcademicQualificationController::class, 'delete']);
});
