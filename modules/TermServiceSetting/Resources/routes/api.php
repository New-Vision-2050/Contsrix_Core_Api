<?php

use Illuminate\Support\Facades\Route;
use Modules\TermServiceSetting\Controllers\TermServiceSettingController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [TermServiceSettingController::class, 'index']);
    Route::post('/', [TermServiceSettingController::class, 'store']);
    Route::post('/export', [TermServiceSettingController::class, 'export']);
    Route::get('/all', [TermServiceSettingController::class, 'getAll']);

    Route::get('/{id}', [TermServiceSettingController::class, 'show']);
    Route::put('/{id}', [TermServiceSettingController::class, 'update']);
    Route::delete('/{id}', [TermServiceSettingController::class, 'delete']);
});
