<?php

declare(strict_types=1);

namespace Modules\Reports\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Reports\Models\Report;

/**
 * Compact presenter used by the listing table — only the columns surfaced in
 * the table view (name, types, period, status, date).
 */
class ReportListPresenter extends AbstractPresenter
{
    public function __construct(private Report $report)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id'           => $this->report->id,
            'name'         => $this->report->name,
            'name_ar'      => $this->report->getTranslation('name', 'ar'),
            'name_en'      => $this->report->getTranslation('name', 'en'),
            'report_types' => $this->report->report_types ?? [],
            'period_type'  => $this->report->period_type,
            'year'         => $this->report->year,
            'month'        => $this->report->month,
            'export_format'=> $this->report->export_format,
            'status'       => $this->report->status,
            'created_at'   => optional($this->report->created_at)->toDateTimeString(),
            'generated_at' => optional($this->report->generated_at)->toDateTimeString(),
        ];
    }
}
