<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteAddress\Controllers\WebsiteAddressController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [WebsiteAddressController::class, 'index']);
    Route::post('/', [WebsiteAddressController::class, 'store']);
    Route::post('/export', [WebsiteAddressController::class, 'export']);

    Route::get('/{id}', [WebsiteAddressController::class, 'show']);
    Route::put('/{id}', [WebsiteAddressController::class, 'update']);
    Route::delete('/{id}', [WebsiteAddressController::class, 'delete']);
});
