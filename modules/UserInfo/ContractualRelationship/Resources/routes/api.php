<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\ContractualRelationship\Controllers\ContractualRelationshipController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/types', [ContractualRelationshipController::class, 'getTypes']);
    Route::get('/{id}', [ContractualRelationshipController::class, 'show']);
    Route::put('/{id}', [ContractualRelationshipController::class, 'update']);
});
