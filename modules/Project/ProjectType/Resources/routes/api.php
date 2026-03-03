<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\ProjectType\Controllers\ProjectTypeController;
use Modules\Project\ProjectType\Controllers\ProjectDataSettingController;
use Modules\Project\ProjectType\Controllers\AttachmentContractSettingController;
use Modules\Project\ProjectType\Controllers\AttachmentTermsContractSettingController;
use Modules\Project\ProjectType\Controllers\ContractorContractSettingController;
use Modules\Project\ProjectType\Controllers\EmployeeContractSettingController;
use Modules\Project\ProjectType\Controllers\DepartmentContractSettingController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [ProjectTypeController::class, 'index']);
    Route::get('/filter', [ProjectTypeController::class, 'getByFilter']);
    Route::post('/', [ProjectTypeController::class, 'store']);
    Route::post('/second-level', [ProjectTypeController::class, 'createSecondLevel']);
    Route::post('/export', [ProjectTypeController::class, 'export']);
    Route::get('/roots', [ProjectTypeController::class, 'getRootProjectTypes']);

    Route::get('/{id}', [ProjectTypeController::class, 'show']);
    Route::get('/{id}/children', [ProjectTypeController::class, 'getDirectChildren']);
    Route::get('/{id}/schemas', [ProjectTypeController::class, 'getSchemas']);
    Route::get('/{id}/second-level-schemas', [ProjectTypeController::class, 'getSecondLevelProjectTypeSchemas']);
    Route::put('/{id}', [ProjectTypeController::class, 'update']);
    Route::delete('/{id}', [ProjectTypeController::class, 'delete']);

    // Project Data Settings routes
    Route::get('/{projectTypeId}/data-settings', [ProjectDataSettingController::class, 'show']);
    Route::put('/{projectTypeId}/data-settings', [ProjectDataSettingController::class, 'update']);

    // Attachment Contract Settings routes
    Route::get('/{projectTypeId}/attachment-contract-settings', [AttachmentContractSettingController::class, 'show']);
    Route::put('/{projectTypeId}/attachment-contract-settings', [AttachmentContractSettingController::class, 'update']);

    // Attachment Terms Contract Settings routes
    Route::get('/{projectTypeId}/attachment-terms-contract-settings', [AttachmentTermsContractSettingController::class, 'show']);
    Route::put('/{projectTypeId}/attachment-terms-contract-settings', [AttachmentTermsContractSettingController::class, 'update']);

    // Contractor Contract Settings routes
    Route::get('/{projectTypeId}/contractor-contract-settings', [ContractorContractSettingController::class, 'show']);
    Route::put('/{projectTypeId}/contractor-contract-settings', [ContractorContractSettingController::class, 'update']);

    // Employee Contract Settings routes
    Route::get('/{projectTypeId}/employee-contract-settings', [EmployeeContractSettingController::class, 'show']);
    Route::put('/{projectTypeId}/employee-contract-settings', [EmployeeContractSettingController::class, 'update']);

    // Department Contract Settings routes
    Route::get('/{projectTypeId}/department-contract-settings', [DepartmentContractSettingController::class, 'show']);
    Route::put('/{projectTypeId}/department-contract-settings', [DepartmentContractSettingController::class, 'update']);
});
