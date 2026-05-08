<?php

use Illuminate\Support\Facades\Route;
use Modules\JobTitle\Controllers\JobTitleController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [JobTitleController::class, 'index']);
    Route::get('/list', [JobTitleController::class, 'listSimple']);
    Route::post('/export', [JobTitleController::class, 'export'])->permission(Permission::ORGANIZATION_JOB_TITLE_EXPORT());
    Route::post('/', [JobTitleController::class, 'store'])->permission(Permission::ORGANIZATION_JOB_TITLE_CREATE());
    Route::get('/{id}', [JobTitleController::class, 'show'])->permission(Permission::ORGANIZATION_JOB_TITLE_VIEW());
    Route::put('/{id}', [JobTitleController::class, 'update'])->permission(Permission::ORGANIZATION_JOB_TITLE_UPDATE());
    Route::delete('/{id}', [JobTitleController::class, 'delete'])->permission(Permission::ORGANIZATION_JOB_TITLE_DELETE());
    Route::patch('/{id}/status', [JobTitleController::class, 'changeStatus'])->permission(Permission::ORGANIZATION_JOB_TITLE_UPDATE());
});
