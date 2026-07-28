<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Project\ProjectManagement\Models\AttachmentRequest;
use Modules\Project\ProjectManagement\Services\AttachmentRequestService;
use Modules\Project\ProjectManagement\Services\AttachmentRequestChartsService;
use Modules\Project\ProjectManagement\Services\ProjectRequirementSubmissionService;
use Modules\Project\ProjectManagement\Requests\CreateAttachmentRequestRequest;
use Modules\Project\ProjectManagement\Requests\FilterAttachmentRequestChartsRequest;
use Modules\Project\ProjectManagement\Requests\RespondToAttachmentItemRequest;
use Modules\Project\ProjectManagement\Requests\ReplaceMediaRequest;
use Modules\Project\ProjectManagement\Presenters\AttachmentRequestChartsPresenter;
use Modules\Project\ProjectManagement\Presenters\AttachmentRequestPresenter;
use Modules\Project\ProjectManagement\Presenters\ProjectRequirementSubmissionPresenter;
use Modules\Project\ProjectManagement\Presenters\RequirementSubmissionInboxPresenter;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AttachmentRequestController extends Controller
{
    public function __construct(
        private AttachmentRequestService $service,
        private ProjectRequirementSubmissionService $submissionService,
        private AttachmentRequestChartsService $chartsService,
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
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400, httpStatus: 400);
        }
    }

    public function charts(FilterAttachmentRequestChartsRequest $request): JsonResponse
    {
        $chartsData = $this->chartsService->getChartsData($request->toDTO());

        return Json::item(AttachmentRequestChartsPresenter::presentCharts($chartsData));
    }

    /**
     * Get all requests (incoming and outgoing) for current company
     *
     * Query params:
     *   project_id  – filter by project
     *   type        – filter by status (pending|approved|declined|semi-approved)
     *   direction   – outgoing | incoming
     *   name        – search by serial number (partial match)
     *   page        – page number (default 1)
     *   per_page    – items per page (default 15)
     */
    public function getAllRequests(Request $request): JsonResponse
    {
        try {
            $filters = array_filter([
                'project_id'                 => $request->query('project_id'),
                'contractual_engagement_key' => $request->query('contractual_engagement_key'),
                'type'                       => $request->query('type'),
                'direction'                  => $request->query('direction'),
                'name'                       => $request->query('name'),
                'per_page'                   => $request->query('per_page', 15),
            ], fn ($v) => $v !== null && $v !== '');

            $paginated = $this->service->getAllRequests($filters);

            $data = collect($paginated->items())->map(function ($item) {
                if ($item instanceof AttachmentRequest) {
                    $payload = (new AttachmentRequestPresenter($item))->getData(true);
                    $payload['item_type'] = 'attachment_request';

                    return $payload;
                }

                $payload = (new RequirementSubmissionInboxPresenter($item))->getData(true);
                $payload['item_type'] = 'requirement_submission';

                return $payload;
            });

            return response()->json([
                'data'         => $data,
                'current_page' => $paginated->currentPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'last_page'    => $paginated->lastPage(),
            ]);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    /**
     * List selectable project procedures for the create-form dropdown.
     */
    public function getProcedures(Request $request): JsonResponse
    {
        try {
            $projectId = $request->query('project_id');
            if (! $projectId) {
                return Json::error('project_id is required', 400);
            }

            return Json::items($this->service->getSelectableProcedures($projectId));
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
     * Get incoming requests count for current company (pending only)
     */
    public function getIncomingRequestsCount(Request $request): JsonResponse
    {
        try {
            $projectId = $request->query('project_id');

            $requests = $this->service->getPendingIncoming($projectId);

            return response()->json([
                'count' => $requests->count()
            ]);
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
    public function respondToItem(RespondToAttachmentItemRequest $request)
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
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400, httpStatus: 400);
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
            return Json::error($e->getMessage(), 400, httpStatus: 400);
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
            return Json::error($e->getMessage(), 400, httpStatus: 400);
        }
    }

    /**
     * Approve a requirement submission from the unified inbox (workflow step action).
     */
    public function approveSubmission(string $submission): JsonResponse
    {
        try {
            $item = $this->submissionService->approveById($submission);

            return Json::item((new ProjectRequirementSubmissionPresenter($item))->getData());
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400, httpStatus: 400);
        }
    }

    /**
     * Decline a requirement submission from the unified inbox (workflow step action).
     */
    public function declineSubmission(string $submission): JsonResponse
    {
        try {
            $item = $this->submissionService->declineById($submission);

            return Json::item((new ProjectRequirementSubmissionPresenter($item))->getData());
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400, httpStatus: 400);
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

    /**
     * Replace media in attachment request item
     */
    public function replaceMedia(ReplaceMediaRequest $request): JsonResponse
    {
        try {
            $item = $this->service->replaceMedia(
                $request->item_id,
                $request->file('new_file')
            );

            // Return the full request with updated items
            $attachmentRequest = $item->attachmentRequest->load(['items.respondedByUser']);
            $data = (new AttachmentRequestPresenter($attachmentRequest))->getData();

            return Json::item($data);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

}
