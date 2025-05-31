<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\Contactinfo\Controllers\ContactinfoController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/{id}', [ContactinfoController::class, 'show']);
    Route::put('address/{id}', [ContactinfoController::class, 'updateAddress']);
    Route::put('/{id}', [ContactinfoController::class, 'update']);


});
