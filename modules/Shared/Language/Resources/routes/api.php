<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\Language\Controllers\LanguageController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [LanguageController::class, 'index']);
    Route::post('/', [LanguageController::class, 'store']);
    Route::get('/{id}', [LanguageController::class, 'show']);
    Route::put('/{id}', [LanguageController::class, 'update']);
    Route::delete('/{id}', [LanguageController::class, 'delete']);
});
