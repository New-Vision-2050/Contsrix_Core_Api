<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\ResourceShare\Controllers\ResourceShareController;
use Modules\Shared\ResourceShare\Controllers\ProjectShareTypeController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    // Resource Shares
    Route::get('/pending', [ResourceShareController::class, 'pending']);
    Route::post('/{id}/accept', [ResourceShareController::class, 'accept']);
    Route::post('/{id}/reject', [ResourceShareController::class, 'reject']);

    // Project Share Types (Independent)
    Route::get('/project-share-types', [ProjectShareTypeController::class, 'getTypes']);
    Route::get('/project-share-types/relations', [ProjectShareTypeController::class, 'getRelations']);
    Route::get('/project-share-types/roles', [ProjectShareTypeController::class, 'getRoles']);
    Route::get('/project-share-types/all', [ProjectShareTypeController::class, 'getAll']);
});
