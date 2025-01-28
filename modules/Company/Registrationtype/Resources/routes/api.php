<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\RegistrationType\Controllers\RegistrationTypeController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [RegistrationTypeController::class, 'index']);
    Route::post('/', [RegistrationTypeController::class, 'store']);
    Route::get('/{id}', [RegistrationTypeController::class, 'show']);
    Route::put('/{id}', [RegistrationTypeController::class, 'update']);
    Route::delete('/{id}', [RegistrationTypeController::class, 'delete']);
});
