<?php

use Illuminate\Support\Facades\Route;
use Modules\Leave\LeaveRequest\Controllers\UserLeavePolicyController;

/*
|--------------------------------------------------------------------------
| My Leave Policies  (prefix: api/v1/my)
|--------------------------------------------------------------------------
| Mobile: the authenticated employee's assigned leave policies with live
| yearly balances — feeds the leave-request form's policy dropdown.
*/
Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/leave-policies', [UserLeavePolicyController::class, 'my']);
});
