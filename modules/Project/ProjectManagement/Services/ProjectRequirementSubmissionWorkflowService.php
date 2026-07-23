<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Support\Facades\Auth;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Services\WorkflowEngine;
use Modules\Process\Models\Process;
use Modules\Project\ProjectManagement\Models\ProjectProcedureSetting;
use Modules\Project\ProjectManagement\Models\ProjectRequirement;
use Modules\Project\ProjectManagement\Models\ProjectRequirementSubmission;

final class ProjectRequirementSubmissionWorkflowService
{
    public function __construct(
        private readonly WorkflowEngine $engine,
    ) {}

    public function startForSubmission(
        ProjectRequirementSubmission $submission,
        ProjectRequirement $requirement,
    ): ?Process {
        $procedureSetting = $this->resolveProjectProcedureSetting($requirement);
        if ($procedureSetting === null) {
            return null;
        }

        $existing = Process::query()
            ->where('processable_id', $submission->id)
            ->where('processable_type', ProjectRequirementSubmission::PROCESSABLE_TYPE)
            ->orderBy('sort_order')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $result = $this->engine->startWorkflow(
            processableType: ProjectRequirementSubmission::PROCESSABLE_TYPE,
            processableId: $submission->id,
            type: ProjectProcedureSetting::PROCEDURE_TYPE,
            formKey: null,
            companyId: (string) $requirement->company_id,
            branchId: null,
            createdByUserId: Auth::check() ? (string) Auth::id() : null,
            context: $this->contextFor($submission, $requirement),
            metadata: $this->metadataFor($submission, $requirement),
            resolvedSetting: $procedureSetting,
        );

        return $result->activeProcess;
    }

    private function resolveProjectProcedureSetting(ProjectRequirement $requirement): ?ProcedureSetting
    {
        if ($requirement->procedure_setting_id === null || $requirement->procedure_setting_id === '') {
            return null;
        }

        $requirement->loadMissing('procedureSetting');
        $procedureSetting = $requirement->procedureSetting;

        if (
            $procedureSetting === null
            || (string) $procedureSetting->type !== ProjectProcedureSetting::PROCEDURE_TYPE
        ) {
            return null;
        }

        $linkedToProject = ProjectProcedureSetting::query()
            ->withoutGlobalScopes()
            ->where('company_id', (string) $requirement->company_id)
            ->where('project_id', (string) $requirement->project_id)
            ->where('procedure_setting_id', (string) $requirement->procedure_setting_id)
            ->exists();

        return $linkedToProject ? $procedureSetting : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function contextFor(ProjectRequirementSubmission $submission, ProjectRequirement $requirement): array
    {
        $receiverCompanyIds = $this->receiverCompanyIds($requirement);

        return [
            'project_id' => $submission->project_id,
            'project_requirement_id' => $submission->project_requirement_id,
            'uploader_company_id' => tenant('id') !== null ? (string) tenant('id') : null,
            'receiver_company_id' => $receiverCompanyIds[0] ?? null,
            'receiver_company_ids' => $receiverCompanyIds,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function metadataFor(ProjectRequirementSubmission $submission, ProjectRequirement $requirement): array
    {
        return [
            'project_id' => $submission->project_id,
            'project_requirement_id' => $submission->project_requirement_id,
            'project_requirement_submission_id' => $submission->id,
            'procedure_setting_id' => $requirement->procedure_setting_id,
            'uploader_company_id' => tenant('id') !== null ? (string) tenant('id') : null,
            'receiver_company_ids' => $this->receiverCompanyIds($requirement),
        ];
    }

    /**
     * @return list<string>
     */
    private function receiverCompanyIds(ProjectRequirement $requirement): array
    {
        $requirement->loadMissing('receiverCompanies');

        return $requirement->receiverCompanies
            ->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->filter(static fn (string $id): bool => $id !== '')
            ->unique()
            ->values()
            ->all();
    }
}
