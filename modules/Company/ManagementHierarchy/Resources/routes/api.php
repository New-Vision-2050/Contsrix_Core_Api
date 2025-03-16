<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\ManagementHierarchy\Controllers\ManagementHierarchyController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [ManagementHierarchyController::class, 'index']);
    Route::post('/', [ManagementHierarchyController::class, 'store']);
    Route::get('/{id}', [ManagementHierarchyController::class, 'show']);
    Route::put('/{id}', [ManagementHierarchyController::class, 'update']);
    Route::delete('/{id}', [ManagementHierarchyController::class, 'delete']);
});
