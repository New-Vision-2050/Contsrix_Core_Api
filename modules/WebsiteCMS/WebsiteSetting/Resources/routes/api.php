<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteSetting\Controllers\WebsiteSettingController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [WebsiteSettingController::class, 'index']);
    Route::get('/current', [WebsiteSettingController::class, 'current']);
    Route::post('/current', [WebsiteSettingController::class, 'updateCurrent']);
    Route::post('/', [WebsiteSettingController::class, 'store']);
    Route::post('/export', [WebsiteSettingController::class, 'export']);

    Route::get('/{id}', [WebsiteSettingController::class, 'show']);
    Route::put('/{id}', [WebsiteSettingController::class, 'update']);
    Route::delete('/{id}', [WebsiteSettingController::class, 'delete']);
});
