<?php

use Illuminate\Support\Facades\Route;
use Modules\Advance\AdvanceRequest\Controllers\UserAdvancePolicyController;

/*
|--------------------------------------------------------------------------
| My Advance Policies  (prefix: api/v1/my)
|--------------------------------------------------------------------------
| The authenticated employee's assigned advance policies with live availability
| — feeds the advance-request form's policy dropdown.
*/
Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/advance-policies', [UserAdvancePolicyController::class, 'my']);
});
