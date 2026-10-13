<?php

use Illuminate\Support\Facades\Route;
use Modules\Advance\AdvancePolicy\Controllers\AdvancePolicyController;
use Modules\RoleAndPermission\Enums\Permission;

/*
|--------------------------------------------------------------------------
| Advance Policy API  (prefix: api/v1/advance-policies)
|--------------------------------------------------------------------------
| Admin settings form (الإعدادات → السلف). No types — a policy stands alone
| and is assigned directly to users from the user profile.
*/
Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [AdvancePolicyController::class, 'index'])
        ->permission(Permission::ADVANCE_POLICY_LIST());
    Route::post('/', [AdvancePolicyController::class, 'store'])
        ->permission(Permission::ADVANCE_POLICY_CREATE());
    Route::get('/{id}', [AdvancePolicyController::class, 'show'])
        ->permission(Permission::ADVANCE_POLICY_VIEW());
    Route::put('/{id}', [AdvancePolicyController::class, 'update'])
        ->permission(Permission::ADVANCE_POLICY_UPDATE());
    Route::delete('/{id}', [AdvancePolicyController::class, 'destroy'])
        ->permission(Permission::ADVANCE_POLICY_DELETE());
});
