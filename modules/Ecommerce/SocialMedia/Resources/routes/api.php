<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\SocialMedia\Controllers\SocialMediaController;
use Modules\RoleAndPermission\Enums\Permission;

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function () {
    Route::get('/', [SocialMediaController::class, 'index'])
        ->permission(Permission::ECOMMERCE_SOCIAL_MEDIA_LIST());
    Route::post('/', [SocialMediaController::class, 'store'])
        ->permission(Permission::ECOMMERCE_SOCIAL_MEDIA_CREATE());
    Route::post('/export', [SocialMediaController::class, 'export'])
        ->permission(Permission::ECOMMERCE_SOCIAL_MEDIA_EXPORT());

    Route::get('/{id}', [SocialMediaController::class, 'show'])
        ->permission(Permission::ECOMMERCE_SOCIAL_MEDIA_VIEW(), Permission::ECOMMERCE_SOCIAL_MEDIA_UPDATE());
    Route::put('/{id}', [SocialMediaController::class, 'update'])
        ->permission(Permission::ECOMMERCE_SOCIAL_MEDIA_UPDATE());
    Route::patch('/{id}/toggle-status', [SocialMediaController::class, 'toggleStatus'])
        ->permission(Permission::ECOMMERCE_SOCIAL_MEDIA_ACTIVATE());
    Route::delete('/{id}', [SocialMediaController::class, 'delete'])
        ->permission(Permission::ECOMMERCE_SOCIAL_MEDIA_DELETE());
});
