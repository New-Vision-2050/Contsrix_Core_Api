<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Company\CompanyCore\Services\CompanyCRUDService;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Requests\ShareProjectRequest;
use Modules\Project\ProjectManagement\Requests\RespondToShareRequest;
use Modules\Shared\ResourceShare\Services\ResourceShareService;
use Modules\Shared\ResourceShare\Presenters\ResourceSharePresenter;
use Ramsey\Uuid\Uuid;

class ProjectShareController extends Controller
{
    public function __construct(
        private ResourceShareService $shareService,
        private CompanyCRUDService $companyService
    ) {
    }

    /**
     * Share a project with another company
     */
    public function shareProject(ShareProjectRequest $request): JsonResponse
    {
        try {
            // Get the company by serial number
            $company = $this->companyService->getBySerialNumber($request->company_serial_number);

            if (!$company) {
                return Json::error('Company not found with serial number: ' . $request->company_serial_number, 404);
            }

            // Get the project
            $project = ProjectManagement::withoutGlobalScope('shareable')
                ->where('id', $request->project_id)
                ->where('company_id', tenant('id'))
                ->first();

            if (!$project) {
                return Json::error('Project not found or you do not have permission to share it', 404);
            }

            // Cannot share with self
            if ($company->id === tenant('id')) {
                return Json::error('Cannot share project with your own company', 400);
            }

            // Share the project
            $share = $this->shareService->shareResource(
                shareableType: ProjectManagement::class,
                shareableId: $project->id,
                ownerCompanyId: tenant('id'),
                sharedWithCompanyId: $company->id,
                schemaIds: $request->schema_ids,
                notes: $request->notes
            );

            $presenter = new ResourceSharePresenter($share);

            return Json::item($presenter->getData());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Get all companies a project is shared with
     */
    public function getProjectShares(Request $request): JsonResponse
    {
        try {
            $projectId = $request->route('id');

            if (!$projectId) {
                return Json::error('Project ID is required', 400);
            }

            // Verify project belongs to current company
            $project = ProjectManagement::withoutGlobalScope('shareable')
                ->where('id', $projectId)
                ->where('company_id', tenant('id'))
                ->first();

            if (!$project) {
                return Json::error('Project not found or you do not have permission', 404);
            }

            $shares = $this->shareService->getSharesForResource(
                ProjectManagement::class,
                $projectId
            );

            $data = $shares->map(function ($share) {
                return (new ResourceSharePresenter($share))->getData();
            });

            return Json::items($data->toArray());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    /**
     * Get pending share invitations for current company
     */
    public function getPendingInvitations(): JsonResponse
    {
        try {
            $invitations = $this->shareService->getPendingInvitations()
                ->filter(function ($share) {
                    return $share->shareable_type === ProjectManagement::class;
                });

            $data = $invitations->map(function ($share) {
                $presenter = new ResourceSharePresenter($share);
                $shareData = $presenter->getData();

                // Add project details
                if ($share->shareable) {
                    $shareData['project'] = [
                        'id' => $share->shareable->id,
                        'name' => $share->shareable->name,
                        'serial_number' => $share->shareable->serial_number,
                    ];
                }

                return $shareData;
            });

            return Json::items($data->toArray());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    /**
     * Accept or reject a share invitation
     */
    public function respondToShare(RespondToShareRequest $request): JsonResponse
    {
        try {
            if ($request->action === 'accept') {
                $result = $this->shareService->acceptShare($request->share_id);
            } else {
                $result = $this->shareService->rejectShare($request->share_id);
            }

            if ($result) {
                return Json::item([
                    'message' => 'Share ' . $request->action . 'ed successfully',
                    'action' => $request->action,
                ]);
            }

            return Json::error('Failed to ' . $request->action . ' share', 400);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Remove a share (only owner can remove)
     */
    public function removeShare(Request $request): JsonResponse
    {
        try {
            $shareId = $request->route('share_id');

            if (!$shareId) {
                return Json::error('Share ID is required', 400);
            }

            $result = $this->shareService->removeShare($shareId);

            if ($result) {
                return Json::deleted();
            }

            return Json::error('Failed to remove share', 400);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Get all accepted shares for current company (projects shared with us)
     */
    public function getSharedWithMe(): JsonResponse
    {
        try {
            $shares = $this->shareService->getAcceptedShares()
                ->filter(function ($share) {
                    return $share->shareable_type === ProjectManagement::class;
                });

            $data = $shares->map(function ($share) {
                $presenter = new ResourceSharePresenter($share);
                $shareData = $presenter->getData();

                // Add project details
                if ($share->shareable) {
                    $shareData['project'] = [
                        'id' => $share->shareable->id,
                        'name' => $share->shareable->name,
                        'serial_number' => $share->shareable->serial_number,
                        'status' => $share->shareable->status,
                    ];
                }

                return $shareData;
            });

            return Json::items($data->toArray());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }
}
