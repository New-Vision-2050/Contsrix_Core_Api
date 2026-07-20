<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\ProjectManagement\Presenters\ProjectNotificationSiteStatusTypeKeyPresenter;
use Modules\Project\ProjectManagement\Presenters\ProjectNotificationSiteStatusTypePresenter;
use Modules\Project\ProjectManagement\Requests\CreateProjectNotificationSiteStatusTypeKeyRequest;
use Modules\Project\ProjectManagement\Requests\CreateProjectNotificationSiteStatusTypeRequest;
use Modules\Project\ProjectManagement\Requests\UpdateProjectNotificationSiteStatusTypeKeyRequest;
use Modules\Project\ProjectManagement\Requests\UpdateProjectNotificationSiteStatusTypeRequest;
use Modules\Project\ProjectManagement\Services\ProjectNotificationSiteStatusTypeKeyService;
use Modules\Project\ProjectManagement\Services\ProjectNotificationSiteStatusTypeService;

class ProjectNotificationSiteStatusTypeController extends Controller
{
    public function __construct(
        private readonly ProjectNotificationSiteStatusTypeService $typeService,
        private readonly ProjectNotificationSiteStatusTypeKeyService $keyService,
    ) {}

    /**
     * GET /projects/notifications/site-status-types
     *
     * List active site status types for the dropdown. Optionally filtered by
     * ?project_type_id= or ?project_id= (resolved to its project_type_id) so
     * every project of the same type sees the same list.
     */
    public function index(Request $request): JsonResponse
    {
        $projectTypeId = $this->typeService->resolveProjectTypeId(
            $request->filled('project_type_id') ? (int) $request->query('project_type_id') : null,
            $request->query('project_id'),
        );

        $notificationTypeId = $request->query('notification_type_id');

        $types = $this->typeService->list($projectTypeId, $notificationTypeId);

        return Json::items(
            ProjectNotificationSiteStatusTypePresenter::collection($types),
            message: 'Site status types retrieved successfully',
        );
    }

    /**
     * GET /projects/notifications/site-status-types/with-keys
     *
     * List active types with their active keys (admin view). Optionally
     * filtered by ?project_type_id= or ?project_id=.
     */
    public function indexWithKeys(Request $request): JsonResponse
    {
        $projectTypeId = $this->typeService->resolveProjectTypeId(
            $request->filled('project_type_id') ? (int) $request->query('project_type_id') : null,
            $request->query('project_id'),
        );

        $notificationTypeId = $request->query('notification_type_id');

        $types = $this->typeService->listWithKeys($projectTypeId, $notificationTypeId);

        return Json::items(
            ProjectNotificationSiteStatusTypePresenter::collectionWithKeys($types),
            message: 'Site status types with keys retrieved successfully',
        );
    }

    /**
     * GET /projects/notifications/site-status-types/{id}
     */
    public function show(string $id): JsonResponse
    {
        $type = $this->typeService->show($id);
        $type->load('notificationTypes');

        return Json::item(
            ProjectNotificationSiteStatusTypePresenter::withKeys($type),
            message: 'Site status type retrieved successfully',
        );
    }

    /**
     * POST /projects/notifications/site-status-types
     */
    public function store(CreateProjectNotificationSiteStatusTypeRequest $request): JsonResponse
    {
        $type = $this->typeService->create($request->toDTO());

        return Json::item(
            ProjectNotificationSiteStatusTypePresenter::withKeys($type),
            message: 'Site status type created successfully',
        );
    }

    /**
     * PUT /projects/notifications/site-status-types/{id}
     */
    public function update(string $id, UpdateProjectNotificationSiteStatusTypeRequest $request): JsonResponse
    {
        $type = $this->typeService->update($id, $request->toDTO());

        return Json::item(
            ProjectNotificationSiteStatusTypePresenter::withKeys($type),
            message: 'Site status type updated successfully',
        );
    }

    /**
     * DELETE /projects/notifications/site-status-types/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $this->typeService->delete($id);

        return Json::deleted();
    }

    /**
     * GET /projects/notifications/site-status-types/{id}/keys
     *
     * Returns all keys for a given site status type.
     */
    public function keys(string $id): JsonResponse
    {
        $keys = $this->keyService->listByType($id);

        return Json::items(
            ProjectNotificationSiteStatusTypeKeyPresenter::collection($keys),
            message: 'Site status type keys retrieved successfully',
        );
    }

    /**
     * POST /projects/notifications/site-status-types/{id}/keys
     *
     * Create a key under the given site status type.
     */
    public function storeKey(string $id, CreateProjectNotificationSiteStatusTypeKeyRequest $request): JsonResponse
    {
        $key = $this->keyService->create($request->toDTO($id));

        return Json::item(
            ProjectNotificationSiteStatusTypeKeyPresenter::single($key),
            message: 'Site status type key created successfully',
        );
    }

    /**
     * PUT /projects/notifications/site-status-types/{id}/keys/{key_id}
     */
    public function updateKey(
        string $id,
        string $keyId,
        UpdateProjectNotificationSiteStatusTypeKeyRequest $request,
    ): JsonResponse {
        $key = $this->keyService->update($keyId, $request->toDTO());

        return Json::item(
            ProjectNotificationSiteStatusTypeKeyPresenter::single($key),
            message: 'Site status type key updated successfully',
        );
    }

    /**
     * DELETE /projects/notifications/site-status-types/{id}/keys/{key_id}
     */
    public function destroyKey(string $id, string $keyId): JsonResponse
    {
        $this->keyService->delete($keyId);

        return Json::deleted();
    }
}
