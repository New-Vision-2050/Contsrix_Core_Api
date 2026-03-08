<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\TermSetting\Controllers\TermSettingController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [TermSettingController::class, 'index'])
        ->permission(Permission::TERM_SETTING_LIST());
    Route::post('/', [TermSettingController::class, 'store'])
        ->permission(Permission::TERM_SETTING_CREATE());
    Route::post('/export', [TermSettingController::class, 'export'])
        ->permission(Permission::TERM_SETTING_EXPORT());
    Route::get('/tree', [TermSettingController::class, 'getTree'])
        ->permission(Permission::TERM_SETTING_LIST());

    Route::get('/{id}/children', [TermSettingController::class, 'getChildren'])
        ->permission(Permission::TERM_SETTING_VIEW());
    Route::put('/{id}/services', [TermSettingController::class, 'updateServices'])
        ->permission(Permission::TERM_SETTING_UPDATE());
    Route::put('/{id}/status', [TermSettingController::class, 'updateStatus'])
        ->permission(Permission::TERM_SETTING_UPDATE());
    Route::get('/{id}', [TermSettingController::class, 'show'])
        ->permission(Permission::TERM_SETTING_VIEW());
    Route::put('/{id}', [TermSettingController::class, 'update'])
        ->permission(Permission::TERM_SETTING_UPDATE());
    Route::delete('/{id}', [TermSettingController::class, 'delete'])
        ->permission(Permission::TERM_SETTING_DELETE());
});
