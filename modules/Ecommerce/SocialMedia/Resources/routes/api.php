<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\SocialMedia\Controllers\SocialMediaController;

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function () {
    Route::get('/', [SocialMediaController::class, 'index']);
    Route::post('/', [SocialMediaController::class, 'store']);
    Route::post('/export', [SocialMediaController::class, 'export']);

    Route::get('/{id}', [SocialMediaController::class, 'show']);
    Route::put('/{id}', [SocialMediaController::class, 'update']);
    Route::patch('/{id}/toggle-status', [SocialMediaController::class, 'toggleStatus']);
    Route::delete('/{id}', [SocialMediaController::class, 'delete']);
});
