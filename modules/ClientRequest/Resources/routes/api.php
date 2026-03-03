<?php

use Illuminate\Support\Facades\Route;
use Modules\ClientRequest\Controllers\ClientRequestController;
use Modules\ClientRequest\Controllers\ClientRequestTypeController;
use Modules\ClientRequest\Controllers\ClientRequestReceiverFromController;
use Modules\ClientRequest\Controllers\ClientRequestServiceController;

Route::get('/client-request-types', [ClientRequestTypeController::class, 'index']);
Route::get('/client-request-receiver-from', [ClientRequestReceiverFromController::class, 'index']);
Route::get('/client-request-services', [ClientRequestServiceController::class, 'index']);

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/price-offer/widgets', [ClientRequestController::class, 'getPriceOfferWidgets']);
    Route::get('/status/widgets', [ClientRequestController::class, 'getStatusWidgets']);
    Route::get('/', [ClientRequestController::class, 'index']);
    Route::post('/', [ClientRequestController::class, 'store']);
    Route::post('/export', [ClientRequestController::class, 'export']);
    Route::get('/{id}', [ClientRequestController::class, 'show']);
    Route::put('/{id}', [ClientRequestController::class, 'update']);
    Route::put('/{id}/full', [ClientRequestController::class, 'updateFull']);
    Route::delete('/{id}', [ClientRequestController::class, 'delete']);


});

Route::group(['middleware' => ['auth:api']], function () {



});
