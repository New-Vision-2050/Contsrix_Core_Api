<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\JobType\Controllers\JobTypeController;
use Modules\RoleAndPermission\Enums\Permission;

Route::get('/export', [JobTypeController::class, 'export'])->name('job-type.export')->permission(Permission::ORGANIZATION_JOB_TYPE_VIEW());

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [JobTypeController::class, 'index'])->permission(Permission::ORGANIZATION_JOB_TYPE_VIEW());
    Route::get('/list', [JobTypeController::class, 'listSimple'])->permission(Permission::ORGANIZATION_JOB_TYPE_VIEW());
    Route::post('/export', [JobTypeController::class, 'export'])->name('job-type.export.auth')->permission(Permission::ORGANIZATION_JOB_TYPE_EXPORT());
    Route::post('/', [JobTypeController::class, 'store'])->permission(Permission::ORGANIZATION_JOB_TYPE_CREATE());
    Route::get('/{id}', [JobTypeController::class, 'show'])->permission(Permission::ORGANIZATION_JOB_TYPE_VIEW());
    Route::put('/{id}', [JobTypeController::class, 'update'])->permission(Permission::ORGANIZATION_JOB_TYPE_UPDATE());
    Route::delete('/{id}', [JobTypeController::class, 'delete'])->permission(Permission::ORGANIZATION_JOB_TYPE_DELETE());
    Route::patch('/{id}/status', [JobTypeController::class, 'changeStatus'])->permission(Permission::ORGANIZATION_JOB_TYPE_UPDATE());
});
