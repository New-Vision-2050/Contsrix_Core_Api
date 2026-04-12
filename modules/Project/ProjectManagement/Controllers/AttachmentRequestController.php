<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\ProjectManagement\Services\AttachmentRequestService;
use Modules\Project\ProjectManagement\Requests\CreateAttachmentRequestRequest;
use Modules\Project\ProjectManagement\Requests\RespondToAttachmentItemRequest;
use Modules\Project\ProjectManagement\Presenters\AttachmentRequestPresenter;

class AttachmentRequestController extends Controller
{
    public function __construct(
        private AttachmentRequestService $service
    ) {
    }

    /**
     * Create a new attachment request (Outgoing)
     */
    public function createRequest(CreateAttachmentRequestRequest $request): JsonResponse
    {
        try {
            $attachmentRequest = $this->service->createRequest($request->validated());

            $data = (new AttachmentRequestPresenter($attachmentRequest))->getData();

            return Json::item($data);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Get all requests (incoming and outgoing) for current company
     */
    public function getAllRequests(Request $request): JsonResponse
    {
        try {
            $projectId = $request->query('project_id');

            $requests = $this->service->getAllRequests($projectId);

            $data = $requests->map(function ($request) {
                return (new AttachmentRequestPresenter($request))->getData(true);
            });

            return Json::items($data->toArray());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    /**
     * Get all outgoing requests for current company
     */
    public function getOutgoingRequests(Request $request): JsonResponse
    {
        try {
            $projectId = $request->query('project_id');

            $requests = $this->service->getOutgoingRequests($projectId);

            $data = $requests->map(function ($request) {
                return (new AttachmentRequestPresenter($request))->getData(true);
            });

            return Json::items($data->toArray());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    /**
     * Get all incoming requests for current company
     */
    public function getIncomingRequests(Request $request): JsonResponse
    {
        try {
            $projectId = $request->query('project_id');

            $requests = $this->service->getIncomingRequests($projectId);

            $data = $requests->map(function ($request) {
                return (new AttachmentRequestPresenter($request))->getData(true);
            });

            return Json::items($data->toArray());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    /**
     * Get pending incoming requests for current company
     */
    public function getPendingIncoming(Request $request): JsonResponse
    {
        try {
            $projectId = $request->query('project_id');

            $requests = $this->service->getPendingIncoming($projectId);

            $data = $requests->map(function ($request) {
                return (new AttachmentRequestPresenter($request))->getData(true);
            });

            return Json::items($data->toArray());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    /**
     * Get specific request details
     */
    public function getRequest(Request $request): JsonResponse
    {
        try {
            $requestId = $request->route('id');

            if (!$requestId) {
                return Json::error('Request ID is required', 400);
            }

            $attachmentRequest = $this->service->getRequest($requestId);

            $data = (new AttachmentRequestPresenter($attachmentRequest))->getData();

            return Json::item($data);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 404);
        }
    }

    /**
     * Respond to individual attachment item
     */
    public function respondToItem(RespondToAttachmentItemRequest $request): JsonResponse
    {
        try {
            $item = $this->service->respondToItem(
                $request->item_id,
                $request->action,
                $request->notes
            );

            // Return the full request with updated items
            $attachmentRequest = $item->attachmentRequest->load(['items.respondedByUser']);
            $data = (new AttachmentRequestPresenter($attachmentRequest))->getData();

            return Json::item($data);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Approve entire request
     */
    public function approveRequest(Request $request): JsonResponse
    {
        try {
            $requestId = $request->route('id');

            if (!$requestId) {
                return Json::error('Request ID is required', 400);
            }

            $attachmentRequest = $this->service->approveRequest($requestId);

            $data = (new AttachmentRequestPresenter($attachmentRequest))->getData();

            return Json::item($data);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Decline entire request
     */
    public function declineRequest(Request $request): JsonResponse
    {
        try {
            $requestId = $request->route('id');

            if (!$requestId) {
                return Json::error('Request ID is required', 400);
            }

            $attachmentRequest = $this->service->declineRequest($requestId);

            $data = (new AttachmentRequestPresenter($attachmentRequest))->getData();

            return Json::item($data);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Get folder children for dropdown (attachment type selection)
     */
    public function getFolderChildren(Request $request): JsonResponse
    {
        try {
            $parentId = $request->query('parent_id');
            $projectId = $request->query('project_id');

            $folders = $this->service->getFolderChildren($parentId, $projectId);

            $data = $folders->map(function ($folder) {
                return [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'parent_id' => $folder->parent_id,
                    'project_id' => $folder->project_id,
                ];
            });

            return Json::items($data->toArray());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }
}
