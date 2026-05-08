<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modules\Company\CompanyCore\Services\CompanyCRUDService;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Requests\ShareProjectRequest;
use Modules\Project\ProjectManagement\Requests\RespondToShareRequest;
use Modules\Project\ProjectManagement\Mail\ProjectShareMail;
use Modules\Shared\ResourceShare\Services\ResourceShareService;
use Modules\Shared\ResourceShare\Presenters\ResourceSharePresenter;
use Modules\User\Models\User;
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
                notes: $request->notes,
                typeId: $request->type_id,
                relationId: $request->relation_id,
                roleId: $request->role_id
            );

            // Send email notification to the owner of the shared company
            $this->sendShareNotificationEmail($share, $company, $project);

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

    /**
     * Get list of companies that can view the project (sender and receivers, excluding current company)
     */
    public function getSharedCompanies(Request $request): JsonResponse
    {
        try {
            $projectId = $request->route('id');

            if (!$projectId) {
                return Json::error('Project ID is required', 400);
            }

            // Verify project access (owner or shared with)
            $project = ProjectManagement::find($projectId);

            if (!$project) {
                return Json::error('Project not found', 404);
            }

            $currentCompanyId = tenant('id');
            $companies = collect();

            // Add owner company (sender) if not current company
            if ($project->company_id !== $currentCompanyId) {
                $ownerCompany = $project->company()->withoutGlobalScopes()->first();
                if ($ownerCompany) {
                    $companies->push([
                        'id' => $ownerCompany->id,
                        'name' => $ownerCompany->name,
                        'serial_number' => $ownerCompany->serial_number,
                        'role' => 'owner',
                        'shared_at' => $project->created_at?->toISOString(),
                    ]);
                }
            }

            // Get accepted shares for this project (receivers)
            $shares = $this->shareService->getSharesForResource(
                ProjectManagement::class,
                $projectId
            )->where('status', 'accepted');

            // Add shared companies (receivers) excluding current company
            $sharedCompanies = $shares
                ->filter(function ($share) use ($currentCompanyId) {
                    return $share->shared_with_company_id !== $currentCompanyId;
                })
                ->map(function ($share) {
                    return [
                        'id' => $share->sharedWithCompany->id,
                        'name' => $share->sharedWithCompany->name,
                        'serial_number' => $share->sharedWithCompany->serial_number,
                        'role' => 'receiver',
                        'shared_at' => $share->created_at?->toISOString(),
                        'accepted_at' => $share->responded_at?->toISOString(),
                    ];
                });

            $companies = $companies->merge($sharedCompanies);

            return Json::items($companies->values()->toArray());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    /**
     * Send email notification to the owner of the shared company
     * Note: This method will throw exceptions if email sending fails for testing purposes
     */
    private function sendShareNotificationEmail($share, $company, $project): void
    {
        // Get the owner of the shared company WITHOUT tenancy scope
        // This is crucial because the receiver company is in a different tenant
        $recipient = User::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('is_owner', 1)
            ->first();

        if (!$recipient || !$recipient->email) {
            \Log::warning("No owner found for project share notification", [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'project_id' => $project->id,
            ]);
            throw new \Exception("No owner email found for company: " . $company->name);
        }

        // Get current user as sender
        $currentUser = auth()->user();
        $senderName = $currentUser ? $currentUser->name : 'مدير النظام';

        // Build action URL from tenant's domain
        $actionUrl = $this->buildActionUrl($company, $share);

        // Send the email - will throw exception if it fails
        Mail::to($recipient->email)->send(
            new ProjectShareMail(
                share: $share,
                project: $project,
                recipientName: $recipient->name,
                senderName: $senderName,
                actionUrl: $actionUrl
            )
        );

        \Log::info("Project share notification email sent successfully", [
            'recipient_email' => $recipient->email,
            'recipient_name' => $recipient->name,
            'company_id' => $company->id,
            'project_id' => $project->id,
            'share_id' => $share->id,
            'action_url' => $actionUrl,
        ]);
    }

    /**
     * Build action URL from company's domain
     */
    private function buildActionUrl($company, $share): string
    {
        // Get the company's primary domain
        $domain = $company->domains()->first();

        if ($domain && $domain->domain) {
            // Build URL using company's domain: https://{domain}/ar/projects/inbox
            return 'https://' . $domain->domain . '/ar/projects/inbox';
        }

        // Fallback to config if no domain found
        return config('app.frontend_url', 'https://constrix.com') . '/ar/projects/inbox';
    }
}
