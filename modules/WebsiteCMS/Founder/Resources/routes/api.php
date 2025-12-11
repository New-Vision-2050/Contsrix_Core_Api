<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\Founder\Controllers\FounderController;
use Modules\RoleAndPermission\Enums\Permission;

Route::get('/', [FounderController::class, 'index'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);

Route::get('/{id}', [FounderController::class, 'show'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);
Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {

    Route::post('/', [FounderController::class, 'store'])
        ->permission(Permission::FOUNDER_CREATE());
    Route::post('/export', [FounderController::class, 'export'])
        ->permission(Permission::FOUNDER_EXPORT());

    Route::post('/{id}', [FounderController::class, 'update'])
        ->permission(Permission::FOUNDER_UPDATE());
    Route::delete('/{id}', [FounderController::class, 'delete'])
        ->permission(Permission::FOUNDER_DELETE());
});
