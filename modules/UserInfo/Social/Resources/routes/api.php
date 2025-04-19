<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\Social\Controllers\SocialController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/{id}', [SocialController::class, 'show']);
    Route::put('/{id}', [SocialController::class, 'update']);
});
