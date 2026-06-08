<?php

declare(strict_types=1);

namespace Modules\Reports\Presenters;

use Carbon\Carbon;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
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
        $branchName = $this->getBranchName();

        return [
            'id'            => $this->report->id,
            'serial_number' => $this->report->serial_number,
            'name'          => $this->report->name,
            'name_ar'      => $this->report->getTranslation('name', 'ar'),
            'name_en'      => $this->report->getTranslation('name', 'en'),
            'report_types' => $this->report->report_types ?? [],
            'period_type'  => $this->report->period_type,
            'year'         => $this->report->year,
            'month'        => $this->report->month,
            'export_format'=> $this->report->export_format,
            'status'       => $this->report->status,
            'branch'       => $branchName,
            'created_at'   => $this->report->created_at
                ? Carbon::parse($this->report->created_at)->setTimezone(getTimeZoneBranchByRequest())->format('Y-m-d H:i:s')
                : null,
            'generated_at' => $this->report->generated_at
                ? Carbon::parse($this->report->generated_at)->setTimezone(getTimeZoneBranchByRequest())->format('Y-m-d H:i:s')
                : null,
        ];
    }

    /**
     * Get branch name from config or return "لم يتم الاختيار" if no branch selected
     */
    private function getBranchName(): string
    {
        $config = $this->report->config ?? [];
        $branchId = $config['step2']['branch_id'] ?? null;

        if (!$branchId) {
            return 'لم يتم الاختيار';
        }

        // Try to get branch name from ManagementHierarchy
        $branch = ManagementHierarchy::find($branchId);

        return $branch ? $branch->name : 'لم يتم الاختيار';
    }
}
