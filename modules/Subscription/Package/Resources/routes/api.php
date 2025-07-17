<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Package\Controllers\PackageController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [PackageController::class, 'index'])->permission(Permission::PACKAGE_LIST());
    Route::get('/counts', [PackageController::class, 'counts'])->permission(Permission::PACKAGE_VIEW());
    Route::post('/export', [PackageController::class, 'export'])->permission(Permission::PACKAGE_EXPORT());
    Route::post('/', [PackageController::class, 'store'])->permission(Permission::PACKAGE_CREATE());
    Route::put('/{id}/status', [PackageController::class, 'updateStatus'])->permission(Permission::PACKAGE_UPDATE());
    Route::post('/attach-features', [PackageController::class, 'attachFeatures'])->permission(Permission::PACKAGE_UPDATE());
    Route::post('/assign-to-company', [PackageController::class, 'assignPackagesToCompany'])->permission(Permission::PACKAGE_UPDATE());
    Route::get('/{id}', [PackageController::class, 'show'])->permission(Permission::PACKAGE_VIEW());
    Route::put('/{id}', [PackageController::class, 'update'])->permission(Permission::PACKAGE_UPDATE());
    Route::delete('/{id}', [PackageController::class, 'delete'])->permission(Permission::PACKAGE_DELETE());
    Route::post('/{package}/assign-permissions', [PackageController::class, 'syncPermissions'])->permission(Permission::PACKAGE_UPDATE());
    Route::get('/{package}/permissions', [PackageController::class, 'getPermissions'])->permission(Permission::PACKAGE_VIEW());
});
