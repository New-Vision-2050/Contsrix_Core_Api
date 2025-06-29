<?php

use Illuminate\Support\Facades\Route;
use Modules\CompanyUser\Controllers\CompanyUserController;
use Modules\CompanyUser\Controllers\CompanyUserProfileController;
use Modules\User\Controllers\UserController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::group(["prefix"=>"brokers"],function (){
        Route::get('/', [\Modules\CompanyUser\Controllers\BrokerController::class, 'index'])->middleware("permission:".Permission::BROKER_LIST());
        Route::post('/', [\Modules\CompanyUser\Controllers\BrokerController::class, 'store'])->middleware("permission:".Permission::BROKER_CREATE());

    });

    Route::group(["prefix"=>"employees"],function (){
        Route::get('/', [\Modules\CompanyUser\Controllers\EmployeeController::class, 'index'])->middleware("permission:".Permission::EMPLOYEE_LIST());
        Route::post('/', [\Modules\CompanyUser\Controllers\EmployeeController::class, 'store'])->middleware("permission:".Permission::EMPLOYEE_CREATE());

    });

    Route::group(["prefix"=>"clients"],function (){
        Route::get('/', [\Modules\CompanyUser\Controllers\ClientController::class, 'index'])->middleware("permission:".Permission::CLIENT_LIST());
        Route::post('/', [\Modules\CompanyUser\Controllers\ClientController::class, 'store'])->middleware("permission:".Permission::CLIENT_CREATE());

    });
    Route::get('/', [CompanyUserController::class, 'index'])->middleware("permission:".Permission::USER_LIST());
    Route::get('/widgets', [CompanyUserController::class, 'widgets']);
    Route::get('/roles', [CompanyUserController::class, 'roles']);
    //Route::post('/export', [CompanyUserController::class, 'export'])->name('company-users.export');

    Route::get('/profile/{id?}', [CompanyUserProfileController::class, 'profile'])->middleware("permission:".Permission::USER_PROFILE_DATA_VIEW());
    Route::post('/validate-photo/{id?}', [CompanyUserProfileController::class, 'validatePhoto']);
    Route::post('/upload-photo/{id?}', [CompanyUserProfileController::class, 'uploadPhoto'])->middleware("permission:".Permission::USER_PROFILE_DATA_UPDATE());
    Route::put('/data-info/{id?}', [CompanyUserProfileController::class, 'updateDataInfo'])->middleware("permission:".Permission::USER_PROFILE_DATA_UPDATE());
    Route::put('/contact-info/{id?}', [CompanyUserProfileController::class, 'updateContactInformation'])->middleware("permission:".Permission::USER_PROFILE_CONTACT_UPDATE());
    Route::post('/identity-data/{id?}', [CompanyUserProfileController::class, 'identityData'])->middleware("permission:".Permission::USER_PROFILE_IDENTITY_UPDATE());
    Route::post('/send-otp/{id?}', [CompanyUserProfileController::class, 'sendOtp']);
    Route::post('/validate-otp/{id?}', [CompanyUserProfileController::class, 'validateOtp']);
    Route::get('/show-data-info/{id?}', [CompanyUserProfileController::class, 'showDataInfo'])->middleware("permission:".Permission::USER_PROFILE_DATA_VIEW());
    Route::get('/show-contact-information/{id?}', [CompanyUserProfileController::class, 'showContactInformation'])->middleware("permission:".Permission::USER_PROFILE_CONTACT_VIEW());
    Route::get('/show-identity-data/{id?}', [CompanyUserProfileController::class, 'showidentityData'])->middleware("permission:".Permission::USER_PROFILE_IDENTITY_VIEW());


    Route::get('/widget/user/{id}', [CompanyUserProfileController::class, 'widget']);
    Route::get('/data-status/user/{id}', [CompanyUserProfileController::class, 'dataStatus']);

    Route::get('/show-by-email', [CompanyUserController::class, 'showByEmail']);
    Route::post('/change-time-zone/{id}', [CompanyUserController::class, 'changeTimeZone']);
    Route::post('/', [CompanyUserController::class, 'store'])->middleware("permission:".Permission::USER_CREATE());
    Route::post('/validations', [CompanyUserController::class, 'validation']);
    Route::post('/check-email', [CompanyUserController::class, 'checkEmail']);

    Route::get('/{id}', [CompanyUserController::class, 'show'])->middleware("permission:".Permission::USER_VIEW());
    Route::put('/{id}', [CompanyUserController::class, 'update'])->middleware("permission:".Permission::USER_EDIT());
    Route::post('/{id}/assign-role', [CompanyUserController::class, 'assignRoleForCompanies']);
    Route::post('/{id}/assign-role-for-current-company', [CompanyUserController::class, 'assignRoleForCurrentCompany']);
    Route::delete('/{id}', [CompanyUserController::class, 'delete'])->middleware("permission:".Permission::USER_DELETE());
    Route::delete('/{id}/specific-role', [CompanyUserController::class, 'deleteForSpecificRole']);
    Route::post('/export', [UserController::class, 'export'])->middleware("permission:".Permission::USER_EXPORT()."|".Permission::CLIENT_EXPORT()."|".Permission::BROKER_EXPORT()."|".Permission::EMPLOYEE_EXPORT())->name("users.export");

});
