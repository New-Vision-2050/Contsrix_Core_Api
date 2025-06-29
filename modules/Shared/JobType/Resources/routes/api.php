<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\JobType\Controllers\JobTypeController;
use Modules\RoleAndPermission\Enums\Permission;

Route::get('/export', [JobTypeController::class, 'export'])->name('job-type.export')->middleware("permission:" . Permission::ORGANIZATION_JOB_TYPE_VIEW());

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [JobTypeController::class, 'index'])->middleware("permission:" . Permission::ORGANIZATION_JOB_TYPE_VIEW());
    Route::get('/list', [JobTypeController::class, 'listSimple'])->middleware("permission:" . Permission::ORGANIZATION_JOB_TYPE_VIEW());
    Route::post('/export', [JobTypeController::class, 'export'])->name('job-type.export.auth')->middleware("permission:" . Permission::ORGANIZATION_JOB_TYPE_EXPORT());
    Route::post('/', [JobTypeController::class, 'store'])->middleware("permission:" . Permission::ORGANIZATION_JOB_TYPE_CREATE());
    Route::get('/{id}', [JobTypeController::class, 'show'])->middleware("permission:" . Permission::ORGANIZATION_JOB_TYPE_VIEW());
    Route::put('/{id}', [JobTypeController::class, 'update'])->middleware("permission:" . Permission::ORGANIZATION_JOB_TYPE_UPDATE());
    Route::delete('/{id}', [JobTypeController::class, 'delete'])->middleware("permission:" . Permission::ORGANIZATION_JOB_TYPE_DELETE());
    Route::patch('/{id}/status', [JobTypeController::class, 'changeStatus'])->middleware("permission:" . Permission::ORGANIZATION_JOB_TYPE_UPDATE());
});
