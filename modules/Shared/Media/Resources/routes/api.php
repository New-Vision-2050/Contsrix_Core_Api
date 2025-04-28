<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\Media\Controllers\MediaController;

Route::group(['middleware' => ['auth:api']], function () {
//    Route::get('/', [MediaController::class, 'index']);
//    Route::post('/', [MediaController::class, 'store']);
//    Route::get('/{id}', [MediaController::class, 'show']);
//    Route::put('/{id}', [MediaController::class, 'update']);
   Route::delete('/{id}', [MediaController::class, 'delete']);
});
