<?php

use Illuminate\Support\Facades\Route;
use Modules\Advance\AdvanceRequest\Controllers\UserAdvancePolicyController;
use Modules\RoleAndPermission\Enums\Permission;

/*
|--------------------------------------------------------------------------
| User Advance Policy API  (prefix: api/v1/users)
|--------------------------------------------------------------------------
| Assign multiple advance policies to a user and inspect a user's policies
| together with their live availability (user profile → السلف section).
*/
Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/{userId}/advance-policies', [UserAdvancePolicyController::class, 'forUser'])
        ->permission(Permission::ADVANCE_POLICY_ASSIGN());
    Route::put('/{userId}/advance-policies', [UserAdvancePolicyController::class, 'sync'])
        ->permission(Permission::ADVANCE_POLICY_ASSIGN());
});
