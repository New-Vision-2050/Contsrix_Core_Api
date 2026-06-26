<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\ProjectType\Controllers\ProjectTypeController;
use Modules\Project\ProjectType\Controllers\ProjectDataSettingController;
use Modules\Project\ProjectType\Controllers\AttachmentContractSettingController;
use Modules\Project\ProjectType\Controllers\AttachmentTermsContractSettingController;
use Modules\Project\ProjectType\Controllers\ContractorContractSettingController;
use Modules\Project\ProjectType\Controllers\EmployeeContractSettingController;
use Modules\Project\ProjectType\Controllers\DepartmentContractSettingController;
use Modules\Project\ProjectType\Controllers\AttachmentCycleSettingController;
use Modules\Project\ProjectType\Controllers\ArchiveLibrarySettingController;
use Modules\Project\ProjectType\Controllers\RolesAndPermissionsSettingController;
use Modules\Project\ProjectType\Controllers\ProjectSharingSettingController;
use Modules\Project\ProjectType\Controllers\MaintenanceEmergencySettingController;
use Modules\Project\ProjectType\Controllers\SchemaController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [ProjectTypeController::class, 'index'])
        ->permission(Permission::PROJECT_TYPE_LIST());
    Route::get('/filter', [ProjectTypeController::class, 'getByFilter'])
        ->permission(Permission::PROJECT_TYPE_LIST());
    Route::post('/', [ProjectTypeController::class, 'store'])
        ->permission(Permission::PROJECT_TYPE_CREATE());
    Route::post('/second-level', [ProjectTypeController::class, 'createSecondLevel'])
        ->permission(Permission::PROJECT_TYPE_CREATE());
    Route::put('/second-level/{id}', [ProjectTypeController::class, 'updateSecondLevel'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::post('/export', [ProjectTypeController::class, 'export'])
        ->permission(Permission::PROJECT_TYPE_EXPORT());
    Route::get('/roots', [ProjectTypeController::class, 'getRootProjectTypes'])
        ->permission(Permission::PROJECT_TYPE_LIST());

    Route::get('/schemas', [SchemaController::class, 'index']);

    Route::get('/{id}', [ProjectTypeController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::get('/{id}/children', [ProjectTypeController::class, 'getDirectChildren'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::get('/{id}/schemas', [ProjectTypeController::class, 'getSchemas'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::get('/{id}/second-level-schemas', [ProjectTypeController::class, 'getSecondLevelProjectTypeSchemas'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/{id}', [ProjectTypeController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());
    Route::delete('/{id}', [ProjectTypeController::class, 'delete'])
        ->permission(Permission::PROJECT_TYPE_DELETE());

    // Project Data Settings routes
    Route::get('/{projectTypeId}/data-settings', [ProjectDataSettingController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/{projectTypeId}/data-settings', [ProjectDataSettingController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Attachment Contract Settings routes
    Route::get('/{projectTypeId}/attachment-contract-settings', [AttachmentContractSettingController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/{projectTypeId}/attachment-contract-settings', [AttachmentContractSettingController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Attachment Terms Contract Settings routes
    Route::get('/{projectTypeId}/attachment-terms-contract-settings', [AttachmentTermsContractSettingController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/{projectTypeId}/attachment-terms-contract-settings', [AttachmentTermsContractSettingController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Contractor Contract Settings routes
    Route::get('/{projectTypeId}/contractor-contract-settings', [ContractorContractSettingController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/{projectTypeId}/contractor-contract-settings', [ContractorContractSettingController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Employee Contract Settings routes
    Route::get('/{projectTypeId}/employee-contract-settings', [EmployeeContractSettingController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/{projectTypeId}/employee-contract-settings', [EmployeeContractSettingController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Department Contract Settings routes
    Route::get('/{projectTypeId}/department-contract-settings', [DepartmentContractSettingController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/{projectTypeId}/department-contract-settings', [DepartmentContractSettingController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Attachment Cycle Settings routes
    Route::get('/{projectTypeId}/attachment-cycle-settings', [AttachmentCycleSettingController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/{projectTypeId}/attachment-cycle-settings', [AttachmentCycleSettingController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Archive Library Settings routes
    Route::get('/{projectTypeId}/archive-library-settings', [ArchiveLibrarySettingController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/{projectTypeId}/archive-library-settings', [ArchiveLibrarySettingController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Roles and Permissions Settings routes
    Route::get('/{projectTypeId}/roles-and-permissions-settings', [RolesAndPermissionsSettingController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/{projectTypeId}/roles-and-permissions-settings', [RolesAndPermissionsSettingController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Project Sharing Settings routes
    Route::get('/{projectTypeId}/project-sharing-settings', [ProjectSharingSettingController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/{projectTypeId}/project-sharing-settings', [ProjectSharingSettingController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Maintenance and Emergency Settings routes
    Route::get('/{projectTypeId}/maintenance-emergency-settings', [MaintenanceEmergencySettingController::class, 'show'])
        ->permission(Permission::PROJECT_TYPE_VIEW());
    Route::put('/{projectTypeId}/maintenance-emergency-settings', [MaintenanceEmergencySettingController::class, 'update'])
        ->permission(Permission::PROJECT_TYPE_UPDATE());

    // Schema routes
//    Route::get('/schemas/{id}', [SchemaController::class, 'show'])
//        ->permission(Permission::PROJECT_TYPE_VIEW());
});
