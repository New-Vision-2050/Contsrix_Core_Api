<?php

use Illuminate\Support\Facades\Route;
use Modules\ArchiveLibrary\File\Controllers\FileController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [FileController::class, 'index']);
    Route::post('/', [FileController::class, 'store']);
    Route::get('/{id}', [FileController::class, 'show']);
    Route::post('/{id}', [FileController::class, 'update']);
    Route::delete('/{id}', [FileController::class, 'delete']);
});
