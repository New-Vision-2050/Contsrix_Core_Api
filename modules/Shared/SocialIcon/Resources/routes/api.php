<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\SocialIcon\Controllers\SocialIconController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [SocialIconController::class, 'index']);
    Route::post('/', [SocialIconController::class, 'store']);
    Route::post('/export', [SocialIconController::class, 'export']);

    Route::get('/{id}', [SocialIconController::class, 'show']);
    Route::put('/{id}', [SocialIconController::class, 'update']);
    Route::delete('/{id}', [SocialIconController::class, 'delete']);
});
