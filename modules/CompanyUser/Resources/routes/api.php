<?php

use Illuminate\Support\Facades\Route;
use Modules\CompanyUser\Controllers\CompanyUserController;
use Modules\CompanyUser\Controllers\CompanyUserProfileController;
use Modules\User\Controllers\UserController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::group(["prefix"=>"brokers"],function (){
        Route::get('/', [\Modules\CompanyUser\Controllers\BrokerController::class, 'index']);
        Route::get('/widgets', [\Modules\CompanyUser\Controllers\BrokerController::class, 'widgets']);

        Route::get('/{id}', [\Modules\CompanyUser\Controllers\BrokerController::class, 'show']);
        Route::post('/', [\Modules\CompanyUser\Controllers\BrokerController::class, 'store']);
        Route::post('/{id}', [\Modules\CompanyUser\Controllers\BrokerController::class, 'update']);

        Route::post('/export', [\Modules\CompanyUser\Controllers\BrokerController::class, 'export']);

        // Broker Dashboard Widgets Routes
        Route::delete('/{id}', [\Modules\CompanyUser\Controllers\BrokerController::class, 'deleteBrokerRole']);


    });

    Route::group(["prefix"=>"employees"],function (){
        Route::get('/', [\Modules\CompanyUser\Controllers\EmployeeController::class, 'index']);
        Route::post('/', [\Modules\CompanyUser\Controllers\EmployeeController::class, 'store']);
        Route::post('/{id}', [\Modules\CompanyUser\Controllers\EmployeeController::class, 'update']);

    });

    Route::group(["prefix"=>"clients"],function (){
        Route::get('/', [\Modules\CompanyUser\Controllers\ClientController::class, 'index']);
        Route::get('/widgets', [\Modules\CompanyUser\Controllers\ClientController::class, 'getWidgets']);

        Route::get('/{id}', [\Modules\CompanyUser\Controllers\ClientController::class, 'show']);
        Route::post('/company', [\Modules\CompanyUser\Controllers\ClientController::class, 'createClientCompany']);
        Route::post('/', [\Modules\CompanyUser\Controllers\ClientController::class, 'store']);
        Route::post('/{id}', [\Modules\CompanyUser\Controllers\ClientController::class, 'update']);
        Route::post('/export', [\Modules\CompanyUser\Controllers\ClientController::class, 'export']);

        // Dashboard Widgets Routes
        Route::delete('/{id}', [\Modules\CompanyUser\Controllers\ClientController::class, 'deleteClientRole']);


    });
    Route::get('/', [CompanyUserController::class, 'index'])->permission(Permission::USER_LIST());
    Route::get('/widgets', [CompanyUserController::class, 'widgets']);
    Route::get('/charts', [CompanyUserController::class, 'charts']);
    Route::get('/roles', [CompanyUserController::class, 'roles']);
    //Route::post('/export', [CompanyUserController::class, 'export'])->name('company-users.export');

    Route::get('/profile/{id?}', [CompanyUserProfileController::class, 'profile']);
    Route::post('/validate-photo/{id?}', [CompanyUserProfileController::class, 'validatePhoto']);
    Route::post('/upload-photo/{id?}', [CompanyUserProfileController::class, 'uploadPhoto']);
    Route::put('/data-info/{id?}', [CompanyUserProfileController::class, 'updateDataInfo']);
    Route::put('/contact-info/{id?}', [CompanyUserProfileController::class, 'updateContactInformation'])->permission(Permission::USER_PROFILE_CONTACT_UPDATE(),Permission::PROFILE_CONTACT_INFO_UPDATE(),Permission::PROFILE_ADDRESS_INFO_UPDATE());
    Route::post('/identity-data/{id?}', [CompanyUserProfileController::class, 'identityData'])->permission(Permission::USER_PROFILE_IDENTITY_UPDATE(),Permission::PROFILE_PASSPORT_INFO_UPDATE(),Permission::PROFILE_BORDER_NUMBER_UPDATE(),Permission::PROFILE_RESIDENCE_INFO_UPDATE());
    Route::post('/send-otp/{id?}', [CompanyUserProfileController::class, 'sendOtp']);
    Route::post('/validate-otp/{id?}', [CompanyUserProfileController::class, 'validateOtp']);
    Route::get('/show-data-info/{id?}', [CompanyUserProfileController::class, 'showDataInfo']);
    Route::get('/show-contact-information/{id?}', [CompanyUserProfileController::class, 'showContactInformation'])->permission(Permission::USER_PROFILE_CONTACT_VIEW(),Permission::PROFILE_CONTACT_INFO_VIEW(),Permission::PROFILE_ADDRESS_INFO_VIEW());
    Route::get('/show-identity-data/{id?}', [CompanyUserProfileController::class, 'showidentityData'])->permission(Permission::USER_PROFILE_IDENTITY_VIEW(),Permission::PROFILE_PASSPORT_INFO_VIEW(),Permission::PROFILE_BORDER_NUMBER_VIEW(),Permission::PROFILE_RESIDENCE_INFO_VIEW());


    Route::get('/widget/user/{id}', [CompanyUserProfileController::class, 'widget']);
    Route::get('/data-status/user/{id}', [CompanyUserProfileController::class, 'dataStatus']);

    Route::get('/show-by-email', [CompanyUserController::class, 'showByEmail']);
    Route::post('/change-time-zone/{id}', [CompanyUserController::class, 'changeTimeZone']);
    Route::post('/', [CompanyUserController::class, 'store'])->permission(Permission::USER_CREATE());
    Route::post('/validations', [CompanyUserController::class, 'validation']);
    Route::post('/check-email', [CompanyUserController::class, 'checkEmail']);

    Route::get('/{id}', [CompanyUserController::class, 'show'])->permission(Permission::USER_VIEW());
    Route::put('/{id}', [CompanyUserController::class, 'update'])->permission(Permission::USER_UPDATE());
    Route::post('/{id}/assign-role', [CompanyUserController::class, 'assignRoleForCompanies']);
    Route::post('/{id}/assign-role-for-current-company', [CompanyUserController::class, 'assignRoleForCurrentCompany']);
    Route::delete('/{id}', [CompanyUserController::class, 'delete'])->permission(Permission::USER_DELETE());
    Route::delete('/{id}/specific-role', [CompanyUserController::class, 'deleteForSpecificRole']);
    Route::delete('/users/{user_id}/specific-role', [CompanyUserController::class, 'deleteUserSpecificRole']);
    Route::post('/export', [UserController::class, 'export'])->permission(Permission::USER_EXPORT())->name("users.export");

});
