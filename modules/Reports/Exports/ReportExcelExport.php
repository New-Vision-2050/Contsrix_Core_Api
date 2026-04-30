<?php

declare(strict_types=1);

namespace Modules\Reports\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Modules\Reports\DTO\ReportWizardConfigDTO;
use Modules\Reports\Models\Report;
use Modules\Reports\Services\ReportLookupService;

/**
 * Top-level Excel workbook for a generated Report. One sheet is produced per
 * selected report type, plus a cover sheet summarising the wizard config.
 */
class ReportExcelExport implements WithMultipleSheets
{
    public function __construct(
        private Report                $report,
        private ReportWizardConfigDTO $config,
        private Collection            $employees,
        /** @var array<string, array<string,mixed>> */
        private array                 $sections,
        private ReportLookupService   $lookups,
    ) {
    }

    public function sheets(): array
    {
        $sheets = [
            new Sheets\ReportCoverSheet($this->report, $this->config, $this->lookups),
        ];

        foreach ($this->config->step1->reportTypeIds as $type) {
            $sheets[] = new Sheets\ReportTypeSheet(
                type:      $type,
                report:    $this->report,
                config:    $this->config,
                employees: $this->employees,
                data:      $this->sections[$type] ?? [],
                lookups:   $this->lookups,
            );
        }

        return $sheets;
    }
}
