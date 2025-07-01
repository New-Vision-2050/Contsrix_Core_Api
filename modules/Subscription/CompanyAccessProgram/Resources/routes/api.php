<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\CompanyAccessProgram\Controllers\CompanyAccessProgramController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [CompanyAccessProgramController::class, 'index']);
    Route::post('/', [CompanyAccessProgramController::class, 'store']);
    Route::get('/{id}', [CompanyAccessProgramController::class, 'show']);
    Route::put('/{id}', [CompanyAccessProgramController::class, 'update']);
    Route::delete('/{id}', [CompanyAccessProgramController::class, 'delete']);
    Route::get('/{id}/package-form-meta', [CompanyAccessProgramController::class, 'getPackageFormMeta']);
});
