<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\FeatureDeal\Controllers\FeatureDealController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [FeatureDealController::class, 'index'])
        ->permission(Permission::ECOMMERCE_FEATURE_DEAL_LIST());
    Route::post('/', [FeatureDealController::class, 'store'])
        ->permission(Permission::ECOMMERCE_FEATURE_DEAL_CREATE());
    Route::post('/export', [FeatureDealController::class, 'export'])
        ->permission(Permission::ECOMMERCE_FEATURE_DEAL_EXPORT());
    
    Route::get('/statistics', [FeatureDealController::class, 'getStatistics'])
        ->permission(Permission::ECOMMERCE_FEATURE_DEAL_LIST());

    Route::get('/{id}', [FeatureDealController::class, 'show'])
        ->permission(Permission::ECOMMERCE_FEATURE_DEAL_VIEW(), Permission::ECOMMERCE_FEATURE_DEAL_UPDATE());
    Route::put('/{id}', [FeatureDealController::class, 'update'])
        ->permission(Permission::ECOMMERCE_FEATURE_DEAL_UPDATE());
    Route::patch('/{id}/toggle-status', [FeatureDealController::class, 'toggleStatus'])
        ->permission(Permission::ECOMMERCE_FEATURE_DEAL_ACTIVATE());
    Route::delete('/{id}', [FeatureDealController::class, 'delete'])
        ->permission(Permission::ECOMMERCE_FEATURE_DEAL_DELETE());
});
