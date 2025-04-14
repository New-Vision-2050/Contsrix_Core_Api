<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\UserRelative\Controllers\UserRelativeController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/user/{id}', [UserRelativeController::class, 'index']);
    Route::post('/', [UserRelativeController::class, 'store']);
    Route::get('/{id}', [UserRelativeController::class, 'show']);
    Route::put('/{id}', [UserRelativeController::class, 'update']);
    Route::delete('/{id}', [UserRelativeController::class, 'delete']);
});
