<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\Period\Controllers\PeriodController;
use Illuminate\Support\Facades\Artisan;



Route::get('/refresh-db', function () {
    Artisan::call('migrate:fresh --seed');
    return response()->json([
        'message' => 'Database has been refreshed and seeded.',
    ]);
});
Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [PeriodController::class, 'index']);
    Route::post('/', [PeriodController::class, 'store']);
    Route::get('/{id}', [PeriodController::class, 'show']);
    Route::put('/{id}', [PeriodController::class, 'update']);
    Route::delete('/{id}', [PeriodController::class, 'delete']);
});
