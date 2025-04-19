<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\ProfessionalBodie\Controllers\ProfessionalBodieController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [ProfessionalBodieController::class, 'index']);
    Route::post('/', [ProfessionalBodieController::class, 'store']);
    Route::get('/{id}', [ProfessionalBodieController::class, 'show']);
    Route::put('/{id}', [ProfessionalBodieController::class, 'update']);
    Route::delete('/{id}', [ProfessionalBodieController::class, 'delete']);
});
