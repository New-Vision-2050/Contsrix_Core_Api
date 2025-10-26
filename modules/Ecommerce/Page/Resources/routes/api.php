<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Page\Controllers\PageController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [PageController::class, 'index']);
    Route::post('/', [PageController::class, 'store']);
    Route::post('/export', [PageController::class, 'export']);

    // Type-based routes (must come before UUID routes to avoid conflicts)
    Route::get('/type/{type}', [PageController::class, 'getByType'])
        ->where('type', 'terms_conditions|privacy_policy|refund_policy|return_policy|cancellation_policy|shipping_policy|about_us|company_reliability');
    Route::post('/type/{type}', [PageController::class, 'upsertByType'])
        ->where('type', 'terms_conditions|privacy_policy|refund_policy|return_policy|cancellation_policy|shipping_policy|about_us|company_reliability');

    // UUID-based routes
    Route::get('/{id}', [PageController::class, 'show'])->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
    Route::put('/{id}', [PageController::class, 'update'])->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
    Route::delete('/{id}', [PageController::class, 'delete'])->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
});
