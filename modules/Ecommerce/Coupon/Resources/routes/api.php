<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Coupon\Controllers\CouponController;

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {
    Route::get('/', [CouponController::class, 'index']);
    Route::post('/', [CouponController::class, 'store']);
    Route::post('/export', [CouponController::class, 'export']);

    Route::get('/{id}', [CouponController::class, 'show']);
    Route::put('/{id}', [CouponController::class, 'update']);
    Route::patch('/{id}/toggle-status', [CouponController::class, 'toggleStatus']);
    Route::delete('/{id}', [CouponController::class, 'delete']);
});
