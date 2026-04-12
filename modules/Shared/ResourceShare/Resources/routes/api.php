<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\ResourceShare\Controllers\ResourceShareController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/pending', [ResourceShareController::class, 'pending']);
    Route::post('/{id}/accept', [ResourceShareController::class, 'accept']);
    Route::post('/{id}/reject', [ResourceShareController::class, 'reject']);
});
