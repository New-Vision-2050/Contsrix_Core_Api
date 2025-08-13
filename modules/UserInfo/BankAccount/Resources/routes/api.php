<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\BankAccount\Controllers\BankAccountController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/user/{id}', [BankAccountController::class, 'index'])->permission(Permission::PROFILE_BANK_INFO_VIEW());
    Route::post('/', [BankAccountController::class, 'store'])->permission(Permission::PROFILE_BANK_INFO_CREATE());
    Route::get('/{id}', [BankAccountController::class, 'show'])->permission(Permission::PROFILE_BANK_INFO_VIEW());
    Route::put('/{id}', [BankAccountController::class, 'update'])->permission(Permission::PROFILE_BANK_INFO_UPDATE());
    Route::put('/{id}/type', [BankAccountController::class, 'updateType'])->permission(Permission::PROFILE_BANK_INFO_UPDATE());
    Route::delete('/{id}', [BankAccountController::class, 'delete'])->permission(Permission::PROFILE_BANK_INFO_DELETE());
});
