<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\ProjectManagement\Controllers\ProjectManagementController;
use Modules\Project\ProjectManagement\Controllers\ProjectShareController;
use Modules\Project\ProjectManagement\Controllers\ProjectEmployeeController;
use Modules\Project\ProjectManagement\Controllers\AttachmentRequestController;
use Modules\Project\ProjectManagement\Controllers\ProjectPermissionController;
use Modules\Project\ProjectManagement\Controllers\ProjectRoleController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [ProjectManagementController::class, 'index'])
        ->permission(Permission::PROJECT_MANAGEMENT_LIST());
    Route::post('/', [ProjectManagementController::class, 'store'])
        ->permission(Permission::PROJECT_MANAGEMENT_CREATE());
    Route::post('/export', [ProjectManagementController::class, 'export'])
        ->permission(Permission::PROJECT_MANAGEMENT_EXPORT());
    Route::get('/widgets', [ProjectManagementController::class, 'widgets'])
        ->permission(Permission::PROJECT_MANAGEMENT_LIST());

    // Project Sharing Routes
    Route::prefix('sharing')->group(function () {
        Route::post('/share', [ProjectShareController::class, 'shareProject']);
        Route::get('/projects/{id}/shares', [ProjectShareController::class, 'getProjectShares']);
        Route::get('/projects/{id}/shared-companies', [ProjectShareController::class, 'getSharedCompanies']);
        Route::get('/invitations/pending', [ProjectShareController::class, 'getPendingInvitations']);
        Route::post('/invitations/respond', [ProjectShareController::class, 'respondToShare']);
        Route::delete('/shares/{id}', [ProjectShareController::class, 'removeShare']);
        Route::get('/shared-with-me', [ProjectShareController::class, 'getSharedWithMe']);
    });

    // Project Employees Routes
    Route::prefix('employees')->group(function () {
        Route::post('/assign', [ProjectEmployeeController::class, 'assignEmployees']);
        Route::get('/project/{project_id}', [ProjectEmployeeController::class, 'getProjectEmployees']);
        Route::get('/not-in-project/{project_id}', [ProjectEmployeeController::class, 'getEmployeesNotInProject']);
        Route::delete('/{id}', [ProjectEmployeeController::class, 'removeEmployee']);
    });

    // Attachment Request Routes
    Route::prefix('attachment-requests')->group(function () {
        // Get all requests (incoming and outgoing)
        Route::get('/', [AttachmentRequestController::class, 'getAllRequests']);

        // Get folder children for attachment type selection
        Route::get('/folders/children', [AttachmentRequestController::class, 'getFolderChildren']);

        // Create new request (outgoing)
        Route::post('/', [AttachmentRequestController::class, 'createRequest']);

        // Get outgoing requests (sent by current company)
        Route::get('/outgoing', [AttachmentRequestController::class, 'getOutgoingRequests']);

        // Get incoming requests (received by current company)
        Route::get('/incoming', [AttachmentRequestController::class, 'getIncomingRequests']);

        // Get incoming requests count
        Route::get('/count', [AttachmentRequestController::class, 'getIncomingRequestsCount']);

        // Get pending incoming requests
        Route::get('/incoming/pending', [AttachmentRequestController::class, 'getPendingIncoming']);

        // Get specific request details
        Route::get('/{id}', [AttachmentRequestController::class, 'getRequest']);

        // Respond to individual attachment item
        Route::post('/items/respond', [AttachmentRequestController::class, 'respondToItem']);

        // Replace media in attachment item
        Route::post('/items/replace-media', [AttachmentRequestController::class, 'replaceMedia']);

        // Approve entire request
        Route::post('/{id}/approve', [AttachmentRequestController::class, 'approveRequest']);

        // Decline entire request
        Route::post('/{id}/decline', [AttachmentRequestController::class, 'declineRequest']);
    });

    // Project Permissions Routes
    Route::prefix('permissions')->group(function () {
        Route::get('/', [ProjectPermissionController::class, 'index']);
        Route::get('/tree', [ProjectPermissionController::class, 'getPermissionsTree']);
        Route::get('/submodule/{submodule}', [ProjectPermissionController::class, 'getBySubmodule']);
        Route::put('/{id}', [ProjectPermissionController::class, 'update']);
    });

    // User Project Permissions Routes
    Route::get('/{project_id}/my-permissions', [ProjectPermissionController::class, 'getUserProjectPermissions']);
    Route::get('/{project_id}/my-permissions/flat', [ProjectPermissionController::class, 'getUserProjectPermissionsFlat']);

    // Bulk Permission Check
    Route::post('/{project_id}/check-permissions', [ProjectPermissionController::class, 'checkBulkPermissions']);

    // Users with Permission
    Route::get('/{project_id}/users-with-permission/{permission_key}', [ProjectPermissionController::class, 'getUsersWithPermission']);

    // Role Comparison
    Route::get('/{project_id}/roles/compare', [ProjectPermissionController::class, 'compareRoles']);

    // Project Roles Routes
    Route::prefix('{project_id}/roles')->group(function () {
        Route::get('/', [ProjectRoleController::class, 'index']);
        Route::post('/', [ProjectRoleController::class, 'store']);
        Route::get('/{id}', [ProjectRoleController::class, 'show']);
        Route::put('/{id}', [ProjectRoleController::class, 'update']);
        Route::delete('/{id}', [ProjectRoleController::class, 'delete']);
        Route::post('/{id}/assign-permissions', [ProjectRoleController::class, 'assignPermissions']);
        Route::post('/{id}/sync-permissions', [ProjectRoleController::class, 'syncPermissions']);
    });

    Route::get('/{id}', [ProjectManagementController::class, 'show'])
        ->permission(Permission::PROJECT_MANAGEMENT_VIEW());
    Route::put('/{id}', [ProjectManagementController::class, 'update'])
        ->permission(Permission::PROJECT_MANAGEMENT_UPDATE());
    Route::delete('/{id}', [ProjectManagementController::class, 'delete'])
        ->permission(Permission::PROJECT_MANAGEMENT_DELETE());


});
