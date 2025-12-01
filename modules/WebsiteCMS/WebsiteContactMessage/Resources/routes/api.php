<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteContactMessage\Controllers\WebsiteContactMessageController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [WebsiteContactMessageController::class, 'index']);
    Route::post('/', [WebsiteContactMessageController::class, 'store']);
    Route::post('/export', [WebsiteContactMessageController::class, 'export']);

    Route::get('/{id}', [WebsiteContactMessageController::class, 'show']);
    Route::put('/{id}', [WebsiteContactMessageController::class, 'update']);
    Route::delete('/{id}', [WebsiteContactMessageController::class, 'delete']);
});
