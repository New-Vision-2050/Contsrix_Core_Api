<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectManagement\Enums\ProjectReportCode;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectType\Models\SafetyRecord;

class SafetyViolationReportUrlResolver
{
    public function __construct(
        private SafetyViolationReportService $makkahReportService,
        private SafetyViolationFormReportService $jeddahReportService,
    ) {}

    public function resolveCode(?ProjectManagement $project): ProjectReportCode
    {
        return $project?->code_report instanceof ProjectReportCode
            ? $project->code_report
            : ProjectReportCode::default();
    }

    public function storeAndGetPublicUrl(SafetyRecord $record): string
    {
        $record->loadMissing('project');

        $projectId = (string) $record->project_id;
        $recordId = (string) $record->id;

        return match ($this->resolveCode($record->project)) {
            ProjectReportCode::Makkah => $this->makkahReportService->storeAndGetPublicUrl($projectId, $recordId),
            ProjectReportCode::Jeddah => $this->jeddahReportService->storeAndGetPublicUrl($projectId, $recordId),
        };
    }
}
