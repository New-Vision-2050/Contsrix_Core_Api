<?php

use Illuminate\Support\Facades\Route;
use Modules\DocumentType\Controllers\DocumentTypeController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [DocumentTypeController::class, 'index']);
    Route::post('/', [DocumentTypeController::class, 'store']);
    Route::post('/export', [DocumentTypeController::class, 'export']);

    Route::get('/{id}', [DocumentTypeController::class, 'show']);
    Route::put('/{id}', [DocumentTypeController::class, 'update']);
    Route::delete('/{id}', [DocumentTypeController::class, 'delete']);
});
