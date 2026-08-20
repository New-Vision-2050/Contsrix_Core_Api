<?php

namespace Modules\Project\ProjectType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Project\ProjectType\Models\SafetyWeeklyReport;

class SafetyWeeklyReportPresenter extends AbstractPresenter
{
    public function __construct(private SafetyWeeklyReport $report) {}

    protected function present(bool $isListing = false): array
    {
        $media = $this->report->getFirstMedia(SafetyWeeklyReport::MEDIA_COLLECTION);

        return [
            'id' => $this->report->id,
            'serial_number' => $this->report->serial_number,
            'project_id' => $this->report->project_id,
            'company_id' => $this->report->company_id,
            'name' => $this->report->name,
            'from_date' => optional($this->report->from_date)?->format('Y-m-d'),
            'to_date' => optional($this->report->to_date)?->format('Y-m-d'),
            'status' => $this->report->status,
            'file_size' => $this->report->file_size,
            'generated_at' => optional($this->report->generated_at)?->toISOString(),
            'created_by' => $this->report->created_by,
            'created_at' => optional($this->report->created_at)?->toISOString(),
            'updated_at' => optional($this->report->updated_at)?->toISOString(),
            'download_url' => $media?->getFullUrl(),
            'has_file' => $media !== null && $this->report->isReady(),
        ];
    }
}
