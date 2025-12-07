<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Coupon\Controllers\CouponController;
use Modules\RoleAndPermission\Enums\Permission;

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {
    Route::get('/', [CouponController::class, 'index'])
        ->permission(Permission::ECOMMERCE_COUPON_LIST());
    Route::post('/', [CouponController::class, 'store'])
        ->permission(Permission::ECOMMERCE_COUPON_CREATE());
    Route::post('/export', [CouponController::class, 'export'])
        ->permission(Permission::ECOMMERCE_COUPON_EXPORT());

    Route::get('/{id}', [CouponController::class, 'show'])
        ->permission(Permission::ECOMMERCE_COUPON_VIEW(), Permission::ECOMMERCE_COUPON_UPDATE());
    Route::put('/{id}', [CouponController::class, 'update'])
        ->permission(Permission::ECOMMERCE_COUPON_UPDATE());
    Route::patch('/{id}/toggle-status', [CouponController::class, 'toggleStatus'])
        ->permission(Permission::ECOMMERCE_COUPON_ACTIVATE());
    Route::delete('/{id}', [CouponController::class, 'delete'])
        ->permission(Permission::ECOMMERCE_COUPON_DELETE());
});
