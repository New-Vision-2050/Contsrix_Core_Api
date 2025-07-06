<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\CompanyAccessProgram\Controllers\CompanyAccessProgramController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [CompanyAccessProgramController::class, 'index']);
    Route::get('/counts', [CompanyAccessProgramController::class, 'counts']);
    Route::post('/', [CompanyAccessProgramController::class, 'store']);
    Route::get('/{id}', [CompanyAccessProgramController::class, 'show']);
    Route::put('/{id}', [CompanyAccessProgramController::class, 'update']);
    Route::put('/{id}/status', [CompanyAccessProgramController::class, 'updateStatus']);
    Route::delete('/{id}', [CompanyAccessProgramController::class, 'delete']);
    Route::get('/{id}/package-form-meta', [CompanyAccessProgramController::class, 'getPackageFormMeta']);
});
