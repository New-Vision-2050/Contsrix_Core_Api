<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\ProfessionalCertificate\Controllers\ProfessionalCertificateController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/professional-degrees', [ProfessionalCertificateController::class, 'getProfessionalDegrees']);
    Route::get('/user/{id}', [ProfessionalCertificateController::class, 'index'])->permission(Permission::PROFILE_CERTIFICATES_VIEW());
    Route::post('/', [ProfessionalCertificateController::class, 'store'])->permission(Permission::PROFILE_CERTIFICATES_CREATE());
    Route::get('/{id}', [ProfessionalCertificateController::class, 'show'])->permission(Permission::PROFILE_CERTIFICATES_VIEW());
    Route::post('/{id}', [ProfessionalCertificateController::class, 'update'])->permission(Permission::PROFILE_CERTIFICATES_UPDATE());
    Route::delete('/{id}', [ProfessionalCertificateController::class, 'delete'])->permission(Permission::PROFILE_CERTIFICATES_DELETE());
});
