<?php

use Illuminate\Support\Facades\Route;
use Modules\CompanyUser\Controllers\CompanyUserController;
use Modules\CompanyUser\Controllers\CompanyUserProfileController;
use Modules\User\Controllers\UserController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::group(["prefix"=>"brokers"],function (){
        Route::get('/', [\Modules\CompanyUser\Controllers\BrokerController::class, 'index']);
        Route::post('/', [\Modules\CompanyUser\Controllers\BrokerController::class, 'store']);

    });

    Route::group(["prefix"=>"employees"],function (){
        Route::get('/', [\Modules\CompanyUser\Controllers\EmployeeController::class, 'index']);
        Route::post('/', [\Modules\CompanyUser\Controllers\EmployeeController::class, 'store']);

    });

    Route::group(["prefix"=>"clients"],function (){
        Route::get('/', [\Modules\CompanyUser\Controllers\ClientController::class, 'index']);
        Route::post('/', [\Modules\CompanyUser\Controllers\ClientController::class, 'store']);

    });
    Route::get('/', [CompanyUserController::class, 'index']);
    Route::get('/widgets', [CompanyUserController::class, 'widgets']);
    Route::get('/roles', [CompanyUserController::class, 'roles']);
    //Route::post('/export', [CompanyUserController::class, 'export'])->name('company-users.export');

    Route::get('/profile/{id?}', [CompanyUserProfileController::class, 'profile']);
    Route::post('/validate-photo/{id?}', [CompanyUserProfileController::class, 'validatePhoto']);
    Route::post('/upload-photo/{id?}', [CompanyUserProfileController::class, 'uploadPhoto']);
    Route::put('/data-info/{id?}', [CompanyUserProfileController::class, 'updateDataInfo']);
    Route::put('/contact-info/{id?}', [CompanyUserProfileController::class, 'updateContactInformation']);
    Route::post('/identity-data/{id?}', [CompanyUserProfileController::class, 'identityData']);
    Route::post('/send-otp/{id?}', [CompanyUserProfileController::class, 'sendOtp']);
    Route::post('/validate-otp/{id?}', [CompanyUserProfileController::class, 'validateOtp']);
    Route::get('/show-data-info/{id?}', [CompanyUserProfileController::class, 'showDataInfo']);
    Route::get('/show-contact-information/{id?}', [CompanyUserProfileController::class, 'showContactInformation']);
    Route::get('/show-identity-data/{id?}', [CompanyUserProfileController::class, 'showidentityData']);


    Route::get('/widget/user/{id}', [CompanyUserProfileController::class, 'widget']);
    Route::get('/data-status/user/{id}', [CompanyUserProfileController::class, 'dataStatus']);

    Route::get('/show-by-email', [CompanyUserController::class, 'showByEmail']);
    Route::post('/change-time-zone/{id}', [CompanyUserController::class, 'changeTimeZone']);
    Route::post('/', [CompanyUserController::class, 'store']);
    Route::post('/validations', [CompanyUserController::class, 'validation']);
    Route::post('/check-email', [CompanyUserController::class, 'checkEmail']);

    Route::get('/{id}', [CompanyUserController::class, 'show']);
    Route::put('/{id}', [CompanyUserController::class, 'update']);
    Route::post('/{id}/assign-role', [CompanyUserController::class, 'assignRoleForCompanies']);
    Route::delete('/{id}', [CompanyUserController::class, 'delete']);
    Route::delete('/{id}/specific-role', [CompanyUserController::class, 'deleteForSpecificRole']);
    Route::post('/export', [UserController::class, 'export'])->middleware("permission:user.list")->name("users.export");

});
