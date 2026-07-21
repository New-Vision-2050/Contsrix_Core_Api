<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\ProjectManagement\Controllers\ProjectManagementController;
use Modules\Project\ProjectManagement\Controllers\ProjectShareController;
use Modules\Project\ProjectManagement\Controllers\ProjectEmployeeController;
use Modules\Project\ProjectManagement\Controllers\AttachmentRequestController;
use Modules\Project\ProjectManagement\Controllers\ProjectPermissionController;
use Modules\Project\ProjectManagement\Controllers\ProjectProcedureController;
use Modules\Project\ProjectManagement\Controllers\ProjectProcedureSettingController;
use Modules\Project\ProjectManagement\Controllers\ProjectRequirementController;
use Modules\Project\ProjectManagement\Controllers\ProjectRoleController;
use Modules\Project\ProjectManagement\Controllers\ProjectNotificationController;
use Modules\Project\ProjectManagement\Controllers\ProjectNotificationSiteStatusTypeController;
use Modules\Project\ProjectManagement\Controllers\ProjectProcedureSettingStepController;
use Modules\Project\ProjectManagement\Controllers\ContractorController;
use Modules\Project\ProjectManagement\Controllers\ProjectContractorController;
use Modules\Project\ProjectType\Controllers\ProjectOrderPermitController;
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
    Route::get('/contractual-engagements', [ProjectManagementController::class, 'contractualEngagements']);

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
        Route::get('/contractual-engagement/{key}', [ProjectEmployeeController::class, 'getByContractualEngagement']);
        Route::get('/project/{project_id}', [ProjectEmployeeController::class, 'getProjectEmployees']);
        Route::get('/not-in-project/{project_id}', [ProjectEmployeeController::class, 'getEmployeesNotInProject']);
        Route::put('/{id}/assign-role', [ProjectEmployeeController::class, 'assignRole']);
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

    // Project Requirements Routes
    Route::prefix('{project}/requirements')->group(function () {
        Route::get('/', [ProjectRequirementController::class, 'index'])
            ->permission(Permission::PROJECT_REQUIREMENT_LIST());
        Route::post('/', [ProjectRequirementController::class, 'store'])
            ->permission(Permission::PROJECT_REQUIREMENT_CREATE());
        Route::get('/{requirement}', [ProjectRequirementController::class, 'show'])
            ->permission(Permission::PROJECT_REQUIREMENT_VIEW());
        Route::put('/{requirement}', [ProjectRequirementController::class, 'update'])
            ->permission(Permission::PROJECT_REQUIREMENT_UPDATE());
        Route::delete('/{requirement}', [ProjectRequirementController::class, 'destroy'])
            ->permission(Permission::PROJECT_REQUIREMENT_DELETE());
    });

    // Project Internal Procedures Routes
    Route::prefix('{project}/internal-procedures')->group(function () {
        Route::get('/', [ProjectProcedureController::class, 'index'])
            ->permission(Permission::PROJECT_MANAGEMENT_VIEW());
        Route::post('/', [ProjectProcedureController::class, 'store'])
            ->permission(Permission::PROJECT_MANAGEMENT_UPDATE());
        Route::get('/{procedure}', [ProjectProcedureController::class, 'show'])
            ->permission(Permission::PROJECT_MANAGEMENT_VIEW());
        Route::put('/{procedure}', [ProjectProcedureController::class, 'update'])
            ->permission(Permission::PROJECT_MANAGEMENT_UPDATE());
        Route::delete('/{procedure}', [ProjectProcedureController::class, 'destroy'])
            ->permission(Permission::PROJECT_MANAGEMENT_UPDATE());
    });

    // Project-scoped Procedure Settings Routes
    Route::prefix('{project_id}/procedure-settings')->group(function () {
        Route::get('/', [ProjectProcedureSettingController::class, 'index']);
        Route::post('/', [ProjectProcedureSettingController::class, 'store']);
        Route::get('/{id}', [ProjectProcedureSettingController::class, 'show']);
        Route::put('/{id}', [ProjectProcedureSettingController::class, 'update']);
        Route::delete('/{id}', [ProjectProcedureSettingController::class, 'delete']);

        Route::get('/{procedureSettingId}/steps', [ProjectProcedureSettingStepController::class, 'index']);
        Route::post('/{procedureSettingId}/steps', [ProjectProcedureSettingStepController::class, 'store']);
        Route::get('/{procedureSettingId}/steps/{stepId}', [ProjectProcedureSettingStepController::class, 'show']);
        Route::put('/{procedureSettingId}/steps/{stepId}', [ProjectProcedureSettingStepController::class, 'update']);
        Route::delete('/{procedureSettingId}/steps/{stepId}', [ProjectProcedureSettingStepController::class, 'delete']);
    });

    // Project Notifications Routes
    // Route::prefix('{project}/contractors')->group(function () {
    //     Route::get('/', [ContractorController::class, 'index']);
    //     Route::post('/', [ContractorController::class, 'store']);
    //     Route::get('/{id}', [ContractorController::class, 'show']);
    //     Route::put('/{id}', [ContractorController::class, 'update']);
    //     Route::delete('/{id}', [ContractorController::class, 'destroy']);
    // });

    Route::prefix('{project}/project-contractors')->group(function () {
        Route::get('/', [ProjectContractorController::class, 'index']);
        Route::post('/', [ProjectContractorController::class, 'store']);
        Route::get('/{id}', [ProjectContractorController::class, 'show']);
        Route::put('/{id}', [ProjectContractorController::class, 'update']);
        Route::delete('/{id}', [ProjectContractorController::class, 'destroy']);
    });

    // All project order permits (must come before {project}/order-permits)
    Route::get('/order-permits', [ProjectOrderPermitController::class, 'all']);

    Route::prefix('{project}/order-permits')->group(function () {
        Route::get('/', [ProjectOrderPermitController::class, 'index']);
        Route::post('/', [ProjectOrderPermitController::class, 'store']);
        Route::get('/{id}', [ProjectOrderPermitController::class, 'show']);
        Route::put('/', [ProjectOrderPermitController::class, 'update']);
        Route::delete('/', [ProjectOrderPermitController::class, 'destroy']);
    });

    Route::prefix('notifications')->group(function () {
        // Static routes MUST come before /{id} to avoid route conflicts
        Route::get('/map-tasks', [ProjectNotificationController::class, 'mapTasks'])
            ->permission(Permission::PROJECT_NOTIFICATION_LIST());
        Route::get('/contractors', [ProjectContractorController::class, 'index'])
            ->permission(Permission::PROJECT_NOTIFICATION_CREATE());
        Route::get('/employees-with-locations', [ProjectNotificationController::class, 'employeesWithLocations']);
        Route::get('/site-statuses', [ProjectNotificationController::class, 'siteStatuses'])
            ;
        Route::get('/update-site-statuses', [ProjectNotificationController::class, 'updateSiteStatuses'])
            ;
        Route::get('/end-task-statuses', [ProjectNotificationController::class, 'endTaskStatuses'])
            ;
        Route::post('/{id}/update-site-status', [ProjectNotificationController::class, 'updateSiteStatus'])
            ;
        Route::post('/{id}/end-task-status', [ProjectNotificationController::class, 'updateEndTaskStatus'])
            ;
        Route::get('/work-stoppage-reasons', [ProjectNotificationController::class, 'workStoppageReasons'])
            ;
        Route::get('/notification-types', [ProjectNotificationController::class, 'notificationTypes'])
            ;

        // Site status types (dynamic schema for maintenance & emergency)
        Route::prefix('site-status-types')->group(function () {
            Route::get('/', [ProjectNotificationSiteStatusTypeController::class, 'index'])
                ->permission(Permission::PROJECT_NOTIFICATION_LIST());
            Route::get('/with-keys', [ProjectNotificationSiteStatusTypeController::class, 'indexWithKeys'])
                ->permission(Permission::PROJECT_NOTIFICATION_LIST());
            Route::post('/', [ProjectNotificationSiteStatusTypeController::class, 'store'])
                ->permission(Permission::PROJECT_NOTIFICATION_CREATE());
            Route::get('/{id}', [ProjectNotificationSiteStatusTypeController::class, 'show'])
                ->permission(Permission::PROJECT_NOTIFICATION_VIEW());
            Route::put('/{id}', [ProjectNotificationSiteStatusTypeController::class, 'update'])
                ->permission(Permission::PROJECT_NOTIFICATION_UPDATE());
            Route::delete('/{id}', [ProjectNotificationSiteStatusTypeController::class, 'destroy'])
                ->permission(Permission::PROJECT_NOTIFICATION_DELETE());

            Route::get('/{id}/keys', [ProjectNotificationSiteStatusTypeController::class, 'keys'])
                ->permission(Permission::PROJECT_NOTIFICATION_LIST());
            Route::post('/{id}/keys', [ProjectNotificationSiteStatusTypeController::class, 'storeKey'])
                ->permission(Permission::PROJECT_NOTIFICATION_CREATE());
            Route::put('/{id}/keys/{key_id}', [ProjectNotificationSiteStatusTypeController::class, 'updateKey'])
                ->permission(Permission::PROJECT_NOTIFICATION_UPDATE());
            Route::delete('/{id}/keys/{key_id}', [ProjectNotificationSiteStatusTypeController::class, 'destroyKey'])
                ->permission(Permission::PROJECT_NOTIFICATION_DELETE());
        });
        Route::get('/charts', [ProjectNotificationController::class, 'charts'])
            ;
        Route::post('/export', [ProjectNotificationController::class, 'export'])
            ->permission(Permission::PROJECT_NOTIFICATION_EXPORT());

        // Mobile routes (employee-facing)
        Route::get('/my-tasks', [ProjectNotificationController::class, 'myTasks'])
            ;
        Route::get('/my-inbox', [ProjectNotificationController::class, 'myInbox'])
            ;
        Route::get('/my-inbox-counts', [ProjectNotificationController::class, 'myInboxCounts'])
            ;
        Route::get('/filters', [ProjectNotificationController::class, 'filters'])
            ;
        Route::get('/{id}/available-actions', [ProjectNotificationController::class, 'availableActions'])
            ;
        Route::post('/{id}/confirm-receive', [ProjectNotificationController::class, 'confirmReceive'])
           ;
        Route::post('/{id}/start', [ProjectNotificationController::class, 'start'])
           ;
        Route::post('/{id}/take-action', [ProjectNotificationController::class, 'takeAction'])
           ;
        Route::post('/{id}/request-update', [ProjectNotificationController::class, 'requestUpdate'])
           ;
        Route::post('/{id}/request-site-status-update', [ProjectNotificationController::class, 'requestSiteStatusUpdate'])
           ;
        Route::post('/{id}/notify-site-status-update-by-voice', [ProjectNotificationController::class, 'notifySiteStatusUpdateByVoice'])
           ;
        Route::post('/{id}/request-fine', [ProjectNotificationController::class, 'requestFine'])
           ;
        Route::post('/{id}/confirm-location', [ProjectNotificationController::class, 'confirmLocation'])
           ;
        Route::post('/{id}/request-work-stoppage-report', [ProjectNotificationController::class, 'requestWorkStoppageReport'])
           ;
        Route::post('/{id}/request-work-resumption', [ProjectNotificationController::class, 'requestWorkResumption'])
           ;
        Route::post('/{id}/request-task-postponement', [ProjectNotificationController::class, 'requestTaskPostponement'])
           ;
        Route::get('/{id}/site-status-updates', [ProjectNotificationController::class, 'siteStatusUpdates'])
            ;
        Route::get('/{id}/site-status-updates/copied', [ProjectNotificationController::class, 'copiedSiteStatusUpdates'])
            ;
        Route::post('/{id}/site-status-updates/{site_status_update_id}/copy', [ProjectNotificationController::class, 'copySiteStatusUpdate'])
            ;
        Route::get('/{id}/notes', [ProjectNotificationController::class, 'notes'])
            ;
        Route::post('/{id}/notes', [ProjectNotificationController::class, 'addNote'])
            ;
        Route::get('/{id}/procedures', [ProjectNotificationController::class, 'procedures'])
            ;
        Route::post('/{id}/end', [ProjectNotificationController::class, 'end'])
           ;
        Route::post('/{id}/reassign', [ProjectNotificationController::class, 'reassign'])
           ;

        // CRUD routes
        Route::get('/', [ProjectNotificationController::class, 'index'])
            ;
        Route::post('/', [ProjectNotificationController::class, 'store'])
            ->permission(Permission::PROJECT_NOTIFICATION_CREATE());
        Route::get('/{id}', [ProjectNotificationController::class, 'show'])
            ;
        Route::put('/{id}', [ProjectNotificationController::class, 'update'])
           ;
        Route::delete('/{id}', [ProjectNotificationController::class, 'destroy'])
            ->permission(Permission::PROJECT_NOTIFICATION_DELETE());

        // Action routes
        Route::post('/{id}/approve', [ProjectNotificationController::class, 'approve'])
           ;
        Route::post('/{id}/reject', [ProjectNotificationController::class, 'reject'])
           ;
        Route::post('/{id}/read-status', [ProjectNotificationController::class, 'updateReadStatus'])
           ;
    });

    Route::get('/{id}', [ProjectManagementController::class, 'show'])
        ->permission(Permission::PROJECT_MANAGEMENT_VIEW());
    Route::put('/{id}', [ProjectManagementController::class, 'update'])
        ->permission(Permission::PROJECT_MANAGEMENT_UPDATE());
    Route::delete('/{id}', [ProjectManagementController::class, 'delete'])
        ->permission(Permission::PROJECT_MANAGEMENT_DELETE());


});
