<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\Qualification\Controllers\QualificationController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/user/{id}', [QualificationController::class, 'index']);
    Route::post('/', [QualificationController::class, 'store']);
    Route::get('/{id}', [QualificationController::class, 'show']);
    Route::post('/{id}', [QualificationController::class, 'update']);
    Route::delete('/{id}', [QualificationController::class, 'delete']);
});
