<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Project\ProjectManagement\Models\ProjectRequirementSubmission;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProjectRequirementSubmissionPresenter extends AbstractPresenter
{
    public function __construct(private readonly ProjectRequirementSubmission $submission) {}

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->submission->id,
            'project_id' => $this->submission->project_id,
            'project_requirement_id' => $this->submission->project_requirement_id,
            'submitted_at' => $this->submission->created_at?->toIso8601String(),
            'files' => $this->submission->getMedia('files')
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
                ->all(),
            'created_at' => $this->submission->created_at?->toIso8601String(),
            'updated_at' => $this->submission->updated_at?->toIso8601String(),
        ];
    }
}
