<?php

use Illuminate\Support\Facades\Route;
use Modules\Leave\PublicHoliday\Controllers\PublicHolidayController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [PublicHolidayController::class, 'index']);
    Route::post('/', [PublicHolidayController::class, 'store']);
    Route::post('/export', [PublicHolidayController::class, 'export']);

    Route::get('/{id}', [PublicHolidayController::class, 'show']);
    Route::put('/{id}', [PublicHolidayController::class, 'update']);
    Route::delete('/{id}', [PublicHolidayController::class, 'delete']);
});
