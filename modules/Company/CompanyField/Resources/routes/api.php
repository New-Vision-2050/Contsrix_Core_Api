<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\CompanyField\Controllers\CompanyFieldController;

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/', [CompanyFieldController::class, 'index']);
    Route::post('/', [CompanyFieldController::class, 'store']);
    Route::get('/{id}', [CompanyFieldController::class, 'show']);
    Route::put('/{id}', [CompanyFieldController::class, 'update']);
    Route::delete('/{id}', [CompanyFieldController::class, 'delete']);
});
