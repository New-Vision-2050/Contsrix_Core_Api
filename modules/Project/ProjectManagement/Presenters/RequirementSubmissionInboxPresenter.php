<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Models\ProcessStep;
use Modules\Project\ProjectManagement\Models\ProjectRequirementSubmission;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Normalizes a ProjectRequirementSubmission into the same top-level envelope the
 * attachment-request presenter produces, so both can live in one unified inbox.
 * The `item_type` discriminator is added by the controller.
 */
class RequirementSubmissionInboxPresenter extends AbstractPresenter
{
    public function __construct(private readonly ProjectRequirementSubmission $submission) {}

    protected function present(bool $isListing = false): array
    {
        $process = $this->submission->relationLoaded('projectRequirementSubmissionProcess')
            ? $this->submission->projectRequirementSubmissionProcess
            : null;

        $requirement = $this->submission->relationLoaded('requirement')
            ? $this->submission->requirement
            : null;

        $procedureSetting = $requirement?->procedureSetting;

        $data = [
            'id' => $this->submission->id,
            'serial_number' => $requirement?->requirement_code,
            'name' => $requirement?->required_document_name,
            'project_id' => $this->submission->project_id,
            'project_requirement_id' => $this->submission->project_requirement_id,
            'procedure_setting_id' => $requirement?->procedure_setting_id,
            'status' => $this->status($process),
            'type' => $this->direction($process),
            'submitted_at' => $this->submission->created_at?->toIso8601String(),
            'created_at' => $this->submission->created_at?->toIso8601String(),
            'updated_at' => $this->submission->updated_at?->toIso8601String(),
        ];

        $data['project'] = $this->submission->relationLoaded('project') && $this->submission->project
            ? [
                'id' => $this->submission->project->id,
                'name' => $this->submission->project->name,
                'serial_number' => $this->submission->project->serial_number,
            ]
            : null;

        $data['requirement'] = $requirement ? [
            'id' => $requirement->id,
            'name' => $requirement->required_document_name,
            'requirement_code' => $requirement->requirement_code,
        ] : null;

        $data['procedure_setting'] = $procedureSetting ? [
            'id' => $procedureSetting->id,
            'name' => $procedureSetting->name,
            'type' => $procedureSetting->type,
            'execute_type' => $procedureSetting->execute_type,
            'is_active' => (bool) $procedureSetting->is_active,
        ] : null;

        $data['files'] = $this->files();

        $data['process'] = null;
        $data['process_steps'] = [];
        $data['workflow'] = null;

        if ($process !== null) {
            $steps = $process->relationLoaded('steps') ? $process->steps : collect();
            $processPayload = [
                'id' => $process->id,
                'status' => $process->status->value,
                'execute_type' => $process->execute_type,
                'type' => $process->processable_type,
                'project_requirement_submission_id' => $process->processable_id,
                'created_at' => $process->created_at?->toIso8601String(),
                'updated_at' => $process->updated_at?->toIso8601String(),
            ];

            $stepsPayload = $steps->map(static function (ProcessStep $step): array {
                return [
                    'id' => $step->id,
                    'process_id' => $step->process_id,
                    'step_id' => $step->step_id,
                    'template_step_order' => $step->template_step_order,
                    'assigned_user_id' => $step->assigned_user_id,
                    'authorized_user_ids' => $step->authorized_user_ids,
                    'escalation_management_hierarchy_id' => $step->escalation_management_hierarchy_id,
                    'status' => $step->status->value,
                    'action_by' => $step->action_by,
                    'acted_at' => $step->acted_at?->toIso8601String(),
                    'created_at' => $step->created_at?->toIso8601String(),
                    'updated_at' => $step->updated_at?->toIso8601String(),
                ];
            })->values()->all();

            $data['process'] = $processPayload;
            $data['process_steps'] = $stepsPayload;
            $data['workflow'] = [
                'process' => $processPayload,
                'process_steps' => $stepsPayload,
            ];
        }

        return $data;
    }

    private function status(?object $process): string
    {
        if ($process === null) {
            return 'approved';
        }

        return match ($process->status) {
            ProcessStatus::Completed => 'approved',
            ProcessStatus::Failed => 'declined',
            default => 'pending',
        };
    }

    private function direction(?object $process): string
    {
        $uploaderCompanyId = null;
        if ($process !== null) {
            $metadata = $process->metadata ?? [];
            $uploaderCompanyId = isset($metadata['uploader_company_id'])
                ? (string) $metadata['uploader_company_id']
                : null;
        }

        return $uploaderCompanyId !== null && $uploaderCompanyId === (string) tenant('id')
            ? 'outgoing'
            : 'incoming';
    }

    private function files(): array
    {
        $mediaItems = $this->submission->relationLoaded('media')
            ? $this->submission->media
            : $this->submission->getMedia('files');

        return collect($mediaItems)
            ->map(static fn (Media $media): array => [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'url' => $media->getFullUrl(),
                'created_at' => $media->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
