<?php

use Illuminate\Support\Facades\Route;
use Modules\TermServiceSetting\Controllers\TermServiceSettingController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [TermServiceSettingController::class, 'index'])
        ->permission(Permission::TERM_SERVICE_SETTING_LIST());
    Route::post('/', [TermServiceSettingController::class, 'store'])
        ->permission(Permission::TERM_SERVICE_SETTING_CREATE());
    Route::post('/export', [TermServiceSettingController::class, 'export'])
        ->permission(Permission::TERM_SERVICE_SETTING_EXPORT());
    Route::get('/all', [TermServiceSettingController::class, 'getAll'])
        ->permission(Permission::TERM_SERVICE_SETTING_LIST());

    Route::get('/{id}', [TermServiceSettingController::class, 'show'])
        ->permission(Permission::TERM_SERVICE_SETTING_VIEW());
    Route::put('/{id}', [TermServiceSettingController::class, 'update'])
        ->permission(Permission::TERM_SERVICE_SETTING_UPDATE());
    Route::delete('/{id}', [TermServiceSettingController::class, 'delete'])
        ->permission(Permission::TERM_SERVICE_SETTING_DELETE());
});
