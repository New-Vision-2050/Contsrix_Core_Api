<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Process\Models\ProcessStep;
use Modules\Project\ProjectManagement\Models\ProjectRequirementSubmission;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProjectRequirementSubmissionPresenter extends AbstractPresenter
{
    public function __construct(private readonly ProjectRequirementSubmission $submission) {}

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->submission->id,
            'project_id' => $this->submission->project_id,
            'project_requirement_id' => $this->submission->project_requirement_id,
            'submitted_at' => $this->submission->created_at?->toIso8601String(),
            'files' => $this->files(),
            'created_at' => $this->submission->created_at?->toIso8601String(),
            'updated_at' => $this->submission->updated_at?->toIso8601String(),
        ];

        $data['process'] = null;
        $data['process_steps'] = [];
        $data['workflow'] = null;
        if ($this->submission->relationLoaded('projectRequirementSubmissionProcess')) {
            $process = $this->submission->projectRequirementSubmissionProcess;
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
        }

        return $data;
    }

    private function files(): array
    {
        $mediaItems = $this->submission->getMedia('files');

        if ($mediaItems->isEmpty()) {
            $mediaItems = Media::query()
                ->where('model_id', $this->submission->id)
                ->whereIn('model_type', array_values(array_unique([
                    ProjectRequirementSubmission::class,
                    ProjectRequirementSubmission::PROCESSABLE_TYPE,
                    $this->submission->getMorphClass(),
                ])))
                ->where('collection_name', 'files')
                ->orderBy('order_column')
                ->get();
        }

        return $mediaItems
            ->map(fn (Media $media): array => [
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
