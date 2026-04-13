<?php

use Illuminate\Support\Facades\Route;
use Modules\ClientRequest\Controllers\ClientRequestController;
use Modules\ClientRequest\Controllers\ClientRequestTypeController;
use Modules\ClientRequest\Controllers\ClientRequestReceiverFromController;
use Modules\ClientRequest\Controllers\ClientRequestServiceController;
use Modules\RoleAndPermission\Enums\Permission;

Route::get('/client-request-types', [ClientRequestTypeController::class, 'index']);
Route::get('/client-request-receiver-from', [ClientRequestReceiverFromController::class, 'index']);
Route::get('/client-request-services', [ClientRequestServiceController::class, 'index']);

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/price-offer/widgets', [ClientRequestController::class, 'getPriceOfferWidgets'])
        ->permission(Permission::CLIENT_REQUEST_LIST());
    Route::get('/status/widgets', [ClientRequestController::class, 'getStatusWidgets'])
        ->permission(Permission::CLIENT_REQUEST_LIST());
    Route::get('/my-requests', [ClientRequestController::class, 'getMyRequests'])
        ->permission(Permission::CLIENT_REQUEST_LIST());
    Route::get('/', [ClientRequestController::class, 'index'])
        ->permission(Permission::CLIENT_REQUEST_LIST(),Permission::PRICE_OFFER_LIST());
    Route::post('/', [ClientRequestController::class, 'store'])
        ->permission(Permission::CLIENT_REQUEST_CREATE());
    Route::post('/export', [ClientRequestController::class, 'export'])
        ->permission(Permission::CLIENT_REQUEST_EXPORT());
    Route::get('/{id}', [ClientRequestController::class, 'show'])
        ->permission(Permission::CLIENT_REQUEST_VIEW());
    Route::put('/{id}', [ClientRequestController::class, 'update'])
        ->permission(Permission::CLIENT_REQUEST_UPDATE());
    Route::put('/{id}/full', [ClientRequestController::class, 'updateFull'])
        ->permission(Permission::CLIENT_REQUEST_UPDATE());
    Route::delete('/{id}', [ClientRequestController::class, 'delete'])
        ->permission(Permission::CLIENT_REQUEST_DELETE());
});
