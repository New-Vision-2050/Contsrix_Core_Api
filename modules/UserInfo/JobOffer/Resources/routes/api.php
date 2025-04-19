<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\JobOffer\Controllers\JobOfferController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/user/{id}', [JobOfferController::class, 'index']);
    Route::post('/', [JobOfferController::class, 'store']);
});
