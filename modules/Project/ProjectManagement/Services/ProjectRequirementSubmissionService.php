<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectRequirement;
use Modules\Project\ProjectManagement\Models\ProjectRequirementSubmission;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\Shared\ResourceShare\Models\ResourceShare;

class ProjectRequirementSubmissionService
{
    public function __construct(
        private readonly ProjectRequirementUploadStatusService $uploadStatusService,
    ) {}

    public function create(string $projectId, string $requirementId, array $data): ProjectRequirementSubmission
    {
        $requirement = $this->findAccessibleRequirement($projectId, $requirementId, true);
        $project = $requirement->project;
        $senderCompanyId = (string) tenant('id');
        $isOwner = (string) $project->company_id === $senderCompanyId;

        if ($isOwner && ! Auth::user()?->can(Permission::PROJECT_REQUIREMENT_UPDATE())) {
            abort(403);
        }

        $status = $this->uploadStatusService->statusFor($requirement, $senderCompanyId);

        if (! ($status['can_upload'] ?? false)) {
            throw ValidationException::withMessages([
                'requirement' => 'Requirement upload is not available: '.($status['disabled_reason'] ?? 'not_available').'.',
            ]);
        }

        return DB::transaction(function () use ($requirement, $project, $data): ProjectRequirementSubmission {
            $submission = ProjectRequirementSubmission::query()->create([
                'project_id' => $project->id,
                'project_requirement_id' => $requirement->id,
            ]);

            foreach ($data['files'] as $file) {
                $submission->addMedia($file)->toMediaCollection('files');
            }

            return $submission->load('media');
        });
    }

    public function list(string $projectId, string $requirementId): Collection
    {
        $requirement = $this->findAccessibleRequirement($projectId, $requirementId);

        return ProjectRequirementSubmission::query()
            ->with('media')
            ->where('project_requirement_id', $requirement->id)
            ->orderByDesc('created_at')
            ->get();
    }

    private function findAccessibleRequirement(
        string $projectId,
        string $requirementId,
        bool $validationOnDenied = false
    ): ProjectRequirement {
        $project = ProjectManagement::query()
            ->withoutGlobalScopes()
            ->where('id', $projectId)
            ->first();

        if (! $project instanceof ProjectManagement) {
            $this->denyRequirementAccess($validationOnDenied);
        }

        $requirement = ProjectRequirement::query()
            ->withoutGlobalScopes()
            ->with([
                'project',
                'receiverCompanies',
            ])
            ->where('project_id', $project->id)
            ->where('id', $requirementId)
            ->first();

        if (! $requirement instanceof ProjectRequirement) {
            $this->denyRequirementAccess($validationOnDenied);
        }

        $companyId = (string) tenant('id');
        $isOwner = (string) $project->company_id === $companyId;
        $isAssigned = $requirement->receiverCompanies
            ->pluck('id')
            ->contains($companyId);

        if (! $isOwner && (! $isAssigned || ! $this->companyCanAccessProject($project, $companyId))) {
            $this->denyRequirementAccess($validationOnDenied);
        }

        $requirement->setRelation('project', $project);

        return $requirement;
    }

    private function denyRequirementAccess(bool $validationOnDenied): never
    {
        if ($validationOnDenied) {
            throw ValidationException::withMessages([
                'requirement' => 'Selected requirement is not available for file upload.',
            ]);
        }

        abort(404);
    }

    private function companyCanAccessProject(ProjectManagement $project, string $companyId): bool
    {
        if ((string) $project->company_id === $companyId) {
            return true;
        }

        return ResourceShare::query()
            ->where('shareable_type', ProjectManagement::class)
            ->where('shareable_id', $project->id)
            ->where('owner_company_id', $project->company_id)
            ->where('shared_with_company_id', $companyId)
            ->where('status', 'accepted')
            ->exists();
    }
}
