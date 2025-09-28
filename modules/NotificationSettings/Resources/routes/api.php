<?php

use Illuminate\Support\Facades\Route;
use Modules\NotificationSettings\Controllers\NotificationSettingsController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
//    Route::get('/', [NotificationSettingsController::class, 'index']);
//    Route::post('/', [NotificationSettingsController::class, 'store']);
//    Route::post('/export', [NotificationSettingsController::class, 'export']);

    Route::get('/', [NotificationSettingsController::class, 'show']);
    Route::put('/', [NotificationSettingsController::class, 'update']);
    Route::delete('/{id}', [NotificationSettingsController::class, 'delete']);
});
