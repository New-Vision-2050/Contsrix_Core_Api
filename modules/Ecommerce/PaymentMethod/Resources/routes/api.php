<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\PaymentMethod\Controllers\PaymentMethodController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [PaymentMethodController::class, 'index'])->permission(Permission::ECOMMERCE_PAYMENT_METHOD_LIST());
    // Route::post('/', [PaymentMethodController::class, 'store'])->permission(Permission::ECOMMERCE_PAYMENT_METHOD_CREATE());
    // Route::post('/export', [PaymentMethodController::class, 'export'])->permission(Permission::ECOMMERCE_PAYMENT_METHOD_EXPORT());

    // Route::get('/{id}', [PaymentMethodController::class, 'show'])->permission(Permission::ECOMMERCE_PAYMENT_METHOD_VIEW());
    // Route::put('/{id}', [PaymentMethodController::class, 'update'])->permission(Permission::ECOMMERCE_PAYMENT_METHOD_UPDATE());
    // Route::delete('/{id}', [PaymentMethodController::class, 'delete'])->permission(Permission::ECOMMERCE_PAYMENT_METHOD_DELETE());
    
    // Toggle status route
    Route::patch('/{type}/toggle-status', [PaymentMethodController::class, 'toggleStatus'])->permission(Permission::ECOMMERCE_PAYMENT_METHOD_ACTIVATE());
});
