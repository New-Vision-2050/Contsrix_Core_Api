<?php

use Illuminate\Support\Facades\Route;
use Modules\ArchiveLibrary\File\Controllers\FileController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [FileController::class, 'index']);
    Route::get('/widgets', [FileController::class, 'getFilesWithWidgets']);
    Route::post('/', [FileController::class, 'store']);
    Route::post('/copy', [FileController::class, 'copyFile']);
    Route::post('/cut', [FileController::class, 'cutFile']);
    Route::get('/{id}', [FileController::class, 'show']);
    Route::post('/{id}', [FileController::class, 'update']);
    Route::delete('/{id}', [FileController::class, 'delete']);
});
