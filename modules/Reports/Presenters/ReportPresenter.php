<?php

declare(strict_types=1);

namespace Modules\Reports\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Reports\Models\Report;

class ReportPresenter extends AbstractPresenter
{
    public function __construct(private Report $report)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id'                => $this->report->id,
            'serial_number'     => $this->report->serial_number,
            'name'              => $this->report->name,
            'name_ar'           => $this->report->getTranslation('name', 'ar'),
            'name_en'           => $this->report->getTranslation('name', 'en'),
            'company_id'        => $this->report->company_id,
            'created_by'        => $this->report->created_by,
            'template_id'       => $this->report->template_id,
            'report_types'      => $this->report->report_types ?? [],
            'period_type'       => $this->report->period_type,
            'year'              => $this->report->year,
            'month'             => $this->report->month,
            'week'              => $this->report->week,
            'quarter'           => $this->report->quarter,
            'period_start'      => optional($this->report->period_start)->toDateString(),
            'period_end'        => optional($this->report->period_end)->toDateString(),
            'export_format'     => $this->report->export_format,
            'language'          => $this->report->language,
            'paper_size'        => $this->report->paper_size,
            'print_orientation' => $this->report->print_orientation,
            'config'            => $isListing ? null : ($this->report->config ?? []),
            'status'            => $this->report->status,
            'file_path'         => $this->report->file_path,
            'file_size'         => $this->report->file_size,
            'generated_at'      => optional($this->report->generated_at)->toDateTimeString(),
            'error_message'     => $this->report->error_message,
            'created_at'        => optional($this->report->created_at)->toDateTimeString(),
            'updated_at'        => optional($this->report->updated_at)->toDateTimeString(),
        ];
    }
}
