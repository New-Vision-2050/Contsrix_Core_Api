<?php

use Illuminate\Support\Facades\Route;
use Modules\Leave\PublicHoliday\Controllers\PublicHolidayController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [PublicHolidayController::class, 'index'])
        ->permission(Permission::PUBLIC_HOLIDAY_LIST());
    Route::post('/', [PublicHolidayController::class, 'store'])
        ->permission(Permission::PUBLIC_HOLIDAY_CREATE());
    Route::post('/export', [PublicHolidayController::class, 'export'])
        ->permission(Permission::PUBLIC_HOLIDAY_EXPORT());

    Route::get('/{id}', [PublicHolidayController::class, 'show'])
        ->permission(Permission::PUBLIC_HOLIDAY_VIEW(),Permission::PUBLIC_HOLIDAY_UPDATE());
    Route::put('/{id}', [PublicHolidayController::class, 'update'])
        ->permission(Permission::PUBLIC_HOLIDAY_UPDATE());
    Route::delete('/{id}', [PublicHolidayController::class, 'delete'])
        ->permission(Permission::PUBLIC_HOLIDAY_DELETE());
});
