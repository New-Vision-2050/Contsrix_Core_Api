<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\TermSetting\Controllers\TermSettingController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [TermSettingController::class, 'index']);
    Route::post('/', [TermSettingController::class, 'store']);
    Route::post('/export', [TermSettingController::class, 'export']);
    Route::get('/tree', [TermSettingController::class, 'getTree']);

    Route::get('/{id}/children', [TermSettingController::class, 'getChildren']);
    Route::put('/{id}/services', [TermSettingController::class, 'updateServices']);
    Route::put('/{id}/status', [TermSettingController::class, 'updateStatus']);
    Route::get('/{id}', [TermSettingController::class, 'show']);
    Route::put('/{id}', [TermSettingController::class, 'update']);
    Route::delete('/{id}', [TermSettingController::class, 'delete']);
});
