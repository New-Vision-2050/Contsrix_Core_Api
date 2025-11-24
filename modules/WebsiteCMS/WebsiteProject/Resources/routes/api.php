<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteProject\Controllers\WebsiteProjectController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [WebsiteProjectController::class, 'index']);
    Route::post('/', [WebsiteProjectController::class, 'store']);
    Route::post('/export', [WebsiteProjectController::class, 'export']);

    Route::get('/{id}', [WebsiteProjectController::class, 'show']);
    Route::put('/{id}', [WebsiteProjectController::class, 'update']);
    Route::delete('/{id}', [WebsiteProjectController::class, 'delete']);
});
