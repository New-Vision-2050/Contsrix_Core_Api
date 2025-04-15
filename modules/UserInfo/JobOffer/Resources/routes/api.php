<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\JobOffer\Controllers\JobOfferController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/user/{id}', [JobOfferController::class, 'index']);
    Route::post('/', [JobOfferController::class, 'store']);
    Route::get('/{id}', [JobOfferController::class, 'show']);
    Route::put('/{id}', [JobOfferController::class, 'update']);
    Route::delete('/{id}', [JobOfferController::class, 'delete']);
});
