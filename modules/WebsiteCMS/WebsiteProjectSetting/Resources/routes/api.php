<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteProjectSetting\Controllers\WebsiteProjectSettingController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [WebsiteProjectSettingController::class, 'index']);
    Route::post('/', [WebsiteProjectSettingController::class, 'store']);
    Route::post('/export', [WebsiteProjectSettingController::class, 'export']);

    Route::get('/{id}', [WebsiteProjectSettingController::class, 'show']);
    Route::put('/{id}', [WebsiteProjectSettingController::class, 'update']);
    Route::delete('/{id}', [WebsiteProjectSettingController::class, 'delete']);
});
