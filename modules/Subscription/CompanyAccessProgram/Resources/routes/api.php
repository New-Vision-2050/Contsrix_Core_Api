<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\CompanyAccessProgram\Controllers\CompanyAccessProgramController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [CompanyAccessProgramController::class, 'index']);//->permission(Permission::COMPANY_ACCESS_PROGRAM_LIST());
    Route::get('/list', [CompanyAccessProgramController::class, 'list']);
    Route::get('/counts', [CompanyAccessProgramController::class, 'counts']);//->permission(Permission::COMPANY_ACCESS_PROGRAM_VIEW());
    Route::post('/export', [CompanyAccessProgramController::class, 'export']);//->permission(Permission::COMPANY_ACCESS_PROGRAM_EXPORT());
    Route::post('/', [CompanyAccessProgramController::class, 'store']);//->permission(Permission::COMPANY_ACCESS_PROGRAM_CREATE());
    Route::get('/{id}', [CompanyAccessProgramController::class, 'show']);//->permission(Permission::COMPANY_ACCESS_PROGRAM_VIEW());
    Route::put('/{id}', [CompanyAccessProgramController::class, 'update']);//->permission(Permission::COMPANY_ACCESS_PROGRAM_UPDATE());
    Route::put('/{id}/status', [CompanyAccessProgramController::class, 'updateStatus']);//->permission(Permission::COMPANY_ACCESS_PROGRAM_UPDATE());
    Route::delete('/{id}', [CompanyAccessProgramController::class, 'delete']);//->permission(Permission::COMPANY_ACCESS_PROGRAM_DELETE());
    Route::get('/{id}/package-form-meta', [CompanyAccessProgramController::class, 'getPackageFormMeta']);//->permission(Permission::COMPANY_ACCESS_PROGRAM_VIEW());
    Route::get('/{id}/permissions-hierarchy', [CompanyAccessProgramController::class, 'getPermissionsHierarchy']);//->permission(Permission::COMPANY_ACCESS_PROGRAM_VIEW());
});
