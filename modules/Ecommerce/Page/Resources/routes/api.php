<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Page\Controllers\PageController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [PageController::class, 'index'])
        ->permission(Permission::ECOMMERCE_PAGE_LIST());
    Route::post('/', [PageController::class, 'store'])
        ->permission(Permission::ECOMMERCE_PAGE_CREATE());
    Route::post('/export', [PageController::class, 'export'])
        ->permission(Permission::ECOMMERCE_PAGE_EXPORT());

    // Type-based routes (must come before UUID routes to avoid conflicts)
    Route::get('/type/{type}', [PageController::class, 'getByType'])
        ->where('type', 'terms_conditions|privacy_policy|refund_policy|return_policy|cancellation_policy|shipping_policy|about_us|company_reliability')
        ->permission(Permission::ECOMMERCE_PAGE_VIEW());
    Route::post('/type/{type}', [PageController::class, 'upsertByType'])
        ->where('type', 'terms_conditions|privacy_policy|refund_policy|return_policy|cancellation_policy|shipping_policy|about_us|company_reliability')
        ->permission(Permission::ECOMMERCE_PAGE_CREATE(), Permission::ECOMMERCE_PAGE_UPDATE());

    // UUID-based routes
    Route::get('/{id}', [PageController::class, 'show'])
        ->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
        ->permission(Permission::ECOMMERCE_PAGE_VIEW(), Permission::ECOMMERCE_PAGE_UPDATE());
    Route::put('/{id}', [PageController::class, 'update'])
        ->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
        ->permission(Permission::ECOMMERCE_PAGE_UPDATE());
    Route::delete('/{id}', [PageController::class, 'delete'])
        ->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
        ->permission(Permission::ECOMMERCE_PAGE_DELETE());
});
