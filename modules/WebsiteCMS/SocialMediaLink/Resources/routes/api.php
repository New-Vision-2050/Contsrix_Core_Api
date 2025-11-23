<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\SocialMediaLink\Controllers\SocialMediaLinkController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [SocialMediaLinkController::class, 'index']);
    Route::get('/types', [SocialMediaLinkController::class, 'getTypes']);
    Route::post('/', [SocialMediaLinkController::class, 'store']);
    Route::post('/export', [SocialMediaLinkController::class, 'export']);

    Route::get('/{id}', [SocialMediaLinkController::class, 'show']);
    Route::put('/{id}', [SocialMediaLinkController::class, 'update']);
    Route::put('/{id}/status', [SocialMediaLinkController::class, 'updateStatus']);
    Route::delete('/{id}', [SocialMediaLinkController::class, 'delete']);
});
