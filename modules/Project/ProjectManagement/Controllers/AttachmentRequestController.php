<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modules\Project\ProjectManagement\Services\AttachmentRequestService;
use Modules\Project\ProjectManagement\Requests\CreateAttachmentRequestRequest;
use Modules\Project\ProjectManagement\Requests\RespondToAttachmentItemRequest;
use Modules\Project\ProjectManagement\Requests\ReplaceMediaRequest;
use Modules\Project\ProjectManagement\Presenters\AttachmentRequestPresenter;
use Modules\Project\ProjectManagement\Mail\AttachmentRequestMail;
use Modules\Company\CompanyCore\Models\Company;
use Modules\User\Models\User;

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

            // Send email notification to receiver company owner
            $this->sendAttachmentRequestEmail($attachmentRequest);

            $data = (new AttachmentRequestPresenter($attachmentRequest))->getData();

            return Json::item($data);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Get all requests (incoming and outgoing) for current company
     *
     * Query params:
     *   project_id  – filter by project
     *   type        – filter by status (pending|approved|declined|semi-approved)
     *   direction   – outgoing | incoming
     *   receiver_id – filter by receiver company UUID
     *   name        – search by serial number (partial match)
     *   page        – page number (default 1)
     *   per_page    – items per page (default 15)
     */
    public function getAllRequests(Request $request): JsonResponse
    {
        try {
            $filters = array_filter([
                'project_id'  => $request->query('project_id'),
                'type'        => $request->query('type'),
                'direction'   => $request->query('direction'),
                'receiver_id' => $request->query('receiver_id'),
                'name'        => $request->query('name'),
                'per_page'    => $request->query('per_page', 15),
            ], fn ($v) => $v !== null && $v !== '');

            $paginated = $this->service->getAllRequests($filters);

            $data = collect($paginated->items())->map(function ($req) {
                return (new AttachmentRequestPresenter($req))->getData(true);
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

    /**
     * Send email notification to receiver company owner
     */
    private function sendAttachmentRequestEmail($attachmentRequest): void
    {
        try {
            // Get receiver company WITHOUT tenancy scope
            $receiverCompany = Company::withoutGlobalScopes()
                ->find($attachmentRequest->receiver_company_id);

            if (!$receiverCompany) {
                \Log::warning("Receiver company not found for attachment request", [
                    'request_id' => $attachmentRequest->id,
                    'receiver_company_id' => $attachmentRequest->receiver_company_id,
                ]);
                return;
            }

            // Get the owner of the receiver company WITHOUT tenancy scope
            $recipient = User::withoutGlobalScopes()
                ->where('company_id', $receiverCompany->id)
                ->where('is_owner', 1)
                ->first();

            if (!$recipient || !$recipient->email) {
                \Log::warning("No owner found for attachment request notification", [
                    'request_id' => $attachmentRequest->id,
                    'company_id' => $receiverCompany->id,
                    'company_name' => $receiverCompany->name,
                ]);
                return;
            }

            // Get project
            $project = $attachmentRequest->project;
            if (!$project) {
                \Log::warning("Project not found for attachment request", [
                    'request_id' => $attachmentRequest->id,
                ]);
                return;
            }

            // Get sender name
            $currentUser = auth()->user();
            $senderName = $currentUser ? $currentUser->name : 'مدير النظام';

            // Build action URL
            $actionUrl = $this->buildActionUrlForAttachment($receiverCompany, $attachmentRequest);

            // Send the email with extra error protection
            try {
                Mail::to($recipient->email)->send(
                    new AttachmentRequestMail(
                        attachmentRequest: $attachmentRequest,
                        project: $project,
                        recipientName: $recipient->name,
                        senderName: $senderName,
                        actionUrl: $actionUrl
                    )
                );

                \Log::info("Attachment request email sent successfully", [
                    'recipient_email' => $recipient->email,
                    'recipient_name' => $recipient->name,
                    'request_id' => $attachmentRequest->id,
                    'company_id' => $receiverCompany->id,
                ]);
            } catch (\Exception $mailException) {
                \Log::error("Mail sending failed for attachment request", [
                    'request_id' => $attachmentRequest->id,
                    'recipient_email' => $recipient->email,
                    'error' => $mailException->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error("Failed to send attachment request email", [
                'request_id' => $attachmentRequest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build action URL for attachment request
     */
    private function buildActionUrlForAttachment($company, $attachmentRequest): string
    {
        // Get the first domain for the company
        $domain = $company->domains()->first();

        if ($domain && $domain->domain) {
            return "https://{$domain->domain}/ar/projects/{$attachmentRequest->project_id}";
        }

        // Fallback to configured frontend URL
        $frontendUrl = config('app.frontend_url', 'https://constrix.com');
        return "{$frontendUrl}/ar/projects/{$attachmentRequest->project_id}";
    }
}
