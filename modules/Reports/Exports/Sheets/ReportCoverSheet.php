<?php

declare(strict_types=1);

namespace Modules\Reports\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Modules\Reports\DTO\ReportWizardConfigDTO;
use Modules\Reports\Models\Report;
use Modules\Reports\Services\ReportLookupService;

class ReportCoverSheet implements FromArray, WithTitle
{
    public function __construct(
        private Report                $report,
        private ReportWizardConfigDTO $config,
        private ReportLookupService   $lookups,
    ) {
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function array(): array
    {
        $lang      = $this->config->step1->reportLanguage;
        $reportTypes = array_values(array_map(
            fn ($id) => $this->labelFor($this->lookups->reportTypes(), $id, $lang),
            $this->config->step1->reportTypeIds,
        ));

        return [
            ['Report ID',   $this->report->id],
            ['Report Name', is_array($this->report->name) ? ($this->report->name[$lang] ?? reset($this->report->name)) : (string) $this->report->name],
            ['Types',       implode(', ', $reportTypes)],
            ['Period',      $this->periodLabel()],
            ['Export',      strtoupper($this->config->step1->exportFormat)],
            ['Paper',       $this->config->step1->paperSize . ' / ' . $this->config->step1->printOrientation],
            ['Language',    strtoupper($lang)],
            ['Generated',   optional($this->report->created_at)->toDateTimeString()],
        ];
    }

    private function periodLabel(): string
    {
        $s = $this->config->step1;
        return match ($s->periodType) {
            'monthly'   => sprintf('%04d-%02d', $s->year, (int) $s->month),
            'weekly'    => sprintf('%d W%02d', $s->year, (int) $s->week),
            'quarterly' => sprintf('%d Q%d',    $s->year, (int) $s->quarter),
            'yearly'    => (string) $s->year,
            default     => (string) $s->year,
        };
    }

    /** @param array<int,array{id:string,label:array{ar:string,en:string}}> $catalog */
    private function labelFor(array $catalog, string $id, string $lang): string
    {
        foreach ($catalog as $entry) {
            if ($entry['id'] === $id) {
                return $entry['label'][$lang] ?? $entry['label']['en'] ?? $id;
            }
        }
        return $id;
    }
}
