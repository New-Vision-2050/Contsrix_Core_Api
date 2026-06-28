<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\DTO;

use Illuminate\Http\UploadedFile;

final class RequestProjectNotificationSiteStatusUpdateDTO
{
    /**
     * @param array<int, UploadedFile>|null $files
     */
    public function __construct(
        public readonly ?string $updateDate = null,
        public readonly ?string $updateTime = null,
        public readonly ?string $siteStatusId = null,
        public readonly ?string $currentSiteStatusId = null,
        public readonly ?string $workStagesCompleted = null,
        public readonly ?string $currentStatusDescription = null,
        public readonly ?float $completionPercentage = null,
        public readonly ?string $updatesObstacles = null,
        public readonly ?string $additionalNotes = null,
        public readonly ?string $internalProcedureSettingId = null,
        public readonly ?array $files = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'update_date' => $this->updateDate,
            'update_time' => $this->updateTime,
            'site_status_id' => $this->siteStatusId,
            'current_site_status_id' => $this->currentSiteStatusId,
            'work_stages_completed' => $this->workStagesCompleted,
            'current_status_description' => $this->currentStatusDescription,
            'completion_percentage' => $this->completionPercentage,
            'updates_obstacles' => $this->updatesObstacles,
            'additional_notes' => $this->additionalNotes,
        ], static fn ($value) => $value !== null);
    }
}
