<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\ProjectManagement\Controllers\AttachmentRequestController;
use Modules\Shared\Media\Controllers\ChunkedUploadController;
use Modules\Project\ProjectManagement\Controllers\ContractorController;
use Modules\Project\ProjectManagement\Controllers\ProjectContractorController;
use Modules\Project\ProjectManagement\Controllers\ProjectEmployeeController;
use Modules\Project\ProjectManagement\Controllers\ProjectManagementController;
use Modules\Project\ProjectManagement\Controllers\ProjectNotificationController;
use Modules\Project\ProjectManagement\Controllers\ProjectNotificationSiteStatusTypeController;
use Modules\Project\ProjectManagement\Controllers\ProjectPCloudSyncController;
use Modules\Project\ProjectManagement\Controllers\ProjectPCloudTestController;
use Modules\Project\ProjectManagement\Controllers\ProjectPermissionController;
use Modules\Project\ProjectManagement\Controllers\ProjectRequirementController;
use Modules\Project\ProjectManagement\Controllers\ProjectRoleController;
use Modules\Project\ProjectManagement\Controllers\ProjectShareController;
use Modules\Project\ProjectType\Controllers\ProjectOrderPermitController;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\Project\ProjectType\Controllers\ViolationController;
use Modules\Project\ProjectType\Controllers\SafetyRecordController;
use Modules\Project\ProjectType\Controllers\SafetyAnalyticsController;
use Modules\Project\ProjectType\Controllers\SafetySearchController;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

Route::group(['middleware' => ['auth:api', InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [ProjectManagementController::class, 'index'])
        ->permission(Permission::PROJECT_MANAGEMENT_LIST());
    Route::post('/', [ProjectManagementController::class, 'store'])
        ->permission(Permission::PROJECT_MANAGEMENT_CREATE());
    Route::post('/export', [ProjectManagementController::class, 'export'])
        ->permission(Permission::PROJECT_MANAGEMENT_EXPORT());
    Route::get('/widgets', [ProjectManagementController::class, 'widgets'])
        ->permission(Permission::PROJECT_MANAGEMENT_LIST());
    Route::get('/contractual-engagements', [ProjectManagementController::class, 'contractualEngagements']);
    Route::get('/project-tags', [ProjectManagementController::class, 'projectTags']);


        //inquiry
       Route::get('/inquiry/my-map-tasks', [ProjectNotificationController::class, 'myMapTasks']);
       Route::get('/inquiry/search', [SafetySearchController::class, 'search']);

        // Safety & Violations — static routes MUST come before {project}/safety/{id}
        Route::get('/violations', [ViolationController::class, 'index']);
        Route::get('/safety/inbox', [SafetyRecordController::class, 'inbox']);
        Route::get('/safety/analytics/top-violations', [SafetyAnalyticsController::class, 'topViolations']);
        Route::get('/safety/analytics/violation-frequencies', [SafetyAnalyticsController::class, 'globalViolationFrequencies']);

        Route::prefix('{project}/safety')->group(function () {
            Route::get('/', [SafetyRecordController::class, 'index']);
            Route::get('/report', [SafetyRecordController::class, 'report']);
            Route::post('/weekly-report', [SafetyAnalyticsController::class, 'weeklyReport']);
            Route::post('/weekly-reports', [SafetyAnalyticsController::class, 'storeWeeklyReport']);
            Route::get('/weekly-reports', [SafetyAnalyticsController::class, 'listWeeklyReports']);
            Route::get('/weekly-reports/{id}', [SafetyAnalyticsController::class, 'showWeeklyReport']);
            Route::get('/weekly-reports/{id}/download', [SafetyAnalyticsController::class, 'downloadWeeklyReport']);
            Route::post('/', [SafetyRecordController::class, 'store']);

            Route::prefix('analytics')->group(function () {
                Route::get('/overall', [SafetyAnalyticsController::class, 'overall']);
                Route::get('/compliant', [SafetyAnalyticsController::class, 'compliant']);
                Route::get('/frequent-violations', [SafetyAnalyticsController::class, 'frequentViolations']);
                Route::get('/violation-performance', [SafetyAnalyticsController::class, 'violationPerformance']);
                Route::get('/by-contractor-consultant', [SafetyAnalyticsController::class, 'byContractorConsultant']);
                Route::get('/contractor-compliance', [SafetyAnalyticsController::class, 'contractorCompliance']);
                Route::get('/contractor-top-violations', [SafetyAnalyticsController::class, 'contractorTopViolations']);
            });

            Route::get('/{id}', [SafetyRecordController::class, 'show']);
            Route::put('/{id}', [SafetyRecordController::class, 'update']);
            Route::post('/{id}/violations', [SafetyRecordController::class, 'evaluateViolations']);
            Route::get('/{id}/violation-report', [SafetyRecordController::class, 'violationReport']);
            Route::get('/{id}/violation-form-report', [SafetyRecordController::class, 'violationFormReport']);
            Route::delete('/{id}', [SafetyRecordController::class, 'destroy']);
        });




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

        // List selectable procedures for the create form
        Route::get('/procedures', [AttachmentRequestController::class, 'getProcedures']);

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

        // Get attachment request and requirement submission charts
        Route::get('/charts', [AttachmentRequestController::class, 'charts']);

        // Resumable/chunked file uploads (used before create-request / replace-media
        // for large files) — see RESUMABLE_UPLOAD_FRONTEND_GUIDE.md
        Route::prefix('uploads')->group(function () {
            Route::post('/init', [ChunkedUploadController::class, 'initiate']);
            Route::post('/{uploadId}/chunk', [ChunkedUploadController::class, 'uploadChunk']);
            Route::get('/{uploadId}/status', [ChunkedUploadController::class, 'status']);
            Route::post('/{uploadId}/complete', [ChunkedUploadController::class, 'complete']);
            Route::delete('/{uploadId}', [ChunkedUploadController::class, 'abort']);
        });

        // Get specific request details
        Route::get('/{id}', [AttachmentRequestController::class, 'getRequest']);

        // Respond to individual attachment item
        Route::post('/items/respond', [AttachmentRequestController::class, 'respondToItem']);

        // Replace media in attachment item
        Route::post('/items/replace-media', [AttachmentRequestController::class, 'replaceMedia']);

        // Act on a requirement submission from the unified inbox (workflow step)
        Route::post('/submissions/{submission}/approve', [AttachmentRequestController::class, 'approveSubmission']);
        Route::post('/submissions/{submission}/decline', [AttachmentRequestController::class, 'declineSubmission']);

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

    Route::post('/pcloud-sync', ProjectPCloudSyncController::class);
    Route::post('/pcloud-sync/test', ProjectPCloudTestController::class);
    Route::post('/{project}/pcloud-sync', ProjectPCloudSyncController::class);

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
        Route::get('/{requirement}/submissions', [ProjectRequirementController::class, 'submissions']);
        Route::post('/{requirement}/submissions', [ProjectRequirementController::class, 'storeSubmission']);
        Route::post('/{requirement}/submissions/{submission}/approve', [ProjectRequirementController::class, 'approveSubmission']);
        Route::post('/{requirement}/submissions/{submission}/decline', [ProjectRequirementController::class, 'declineSubmission']);
        Route::get('/{requirement}', [ProjectRequirementController::class, 'show'])
            ->permission(Permission::PROJECT_REQUIREMENT_VIEW());
        Route::put('/{requirement}', [ProjectRequirementController::class, 'update'])
            ->permission(Permission::PROJECT_REQUIREMENT_UPDATE());
        Route::delete('/{requirement}', [ProjectRequirementController::class, 'destroy'])
            ->permission(Permission::PROJECT_REQUIREMENT_DELETE());
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
        // Route::get('/department/{departmentId}', [ProjectOrderPermitController::class, 'getByDepartment']);
        Route::get('/uds-work-orders', [ProjectOrderPermitController::class, 'searchUds']);
        Route::post('/', [ProjectOrderPermitController::class, 'store']);
        Route::get('/export-uds-template', [ProjectOrderPermitController::class, 'downloadImportTemplate']);
        Route::post('/import', [ProjectOrderPermitController::class, 'importExcel']);
        Route::get('/{name}/update-from-uds', [ProjectOrderPermitController::class, 'updateFromUds']);
        Route::get('/{id}', [ProjectOrderPermitController::class, 'show']);
        Route::put('/{id}', [ProjectOrderPermitController::class, 'update']);
        Route::put('/{id}/statuses', [ProjectOrderPermitController::class, 'updateStatuses']);
        Route::get('/{id}/note-logs', [ProjectOrderPermitController::class, 'noteLogs']);
        Route::delete('/', [ProjectOrderPermitController::class, 'destroy']);
    });






    Route::prefix('notifications')->group(function () {
        // Static routes MUST come before /{id} to avoid route conflicts
        Route::get('/map-tasks', [ProjectNotificationController::class, 'mapTasks']);
        Route::get('/contractors', [ProjectContractorController::class, 'index']);
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
        Route::post('/{id}/request-safety-violation', [ProjectNotificationController::class, 'requestSafetyViolation'])
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

    Route::post('/{id}/stamp', [ProjectManagementController::class, 'storeStamp'])
        ->permission(Permission::PROJECT_MANAGEMENT_CREATE(), Permission::PROJECT_MANAGEMENT_UPDATE());
    Route::get('/{id}/stamp', [ProjectManagementController::class, 'showStamp'])
        ->permission(Permission::PROJECT_MANAGEMENT_VIEW());
    Route::get('/{id}', [ProjectManagementController::class, 'show'])
        ->permission(Permission::PROJECT_MANAGEMENT_VIEW());
    Route::put('/{id}', [ProjectManagementController::class, 'update'])
        ->permission(Permission::PROJECT_MANAGEMENT_UPDATE());
    Route::delete('/{id}', [ProjectManagementController::class, 'delete'])
        ->permission(Permission::PROJECT_MANAGEMENT_DELETE());


});
