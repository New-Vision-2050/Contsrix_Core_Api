<?php

declare(strict_types=1);

namespace Modules\Reports\DTO;

/**
 * Step 1 — Report type & period (نوع التقرير + الفترة + الإخراج).
 *
 * Mirrors the frontend `ReportWizardStep1` shape exactly.
 */
final class ReportWizardStep1DTO
{
    public function __construct(
        /** @var string[] */
        public readonly array  $reportTypeIds,
        public readonly string $periodType,         // monthly|weekly|quarterly|yearly
        public readonly int    $year,
        public readonly ?int   $month,              // 1..12 (only when periodType=monthly|quarterly)
        public readonly ?int   $week,               // 1..53 (only when periodType=weekly)
        public readonly ?int   $quarter,            // 1..4  (only when periodType=quarterly)
        public readonly string $exportFormat,       // pdf|excel|csv
        public readonly string $reportLanguage,     // ar|en
        public readonly string $paperSize,          // A4|Letter|A3
        public readonly string $printOrientation,   // portrait|landscape
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            reportTypeIds:    array_values($payload['reportTypeIds'] ?? []),
            periodType:       (string) ($payload['periodType'] ?? 'monthly'),
            year:             (int)    ($payload['year']        ?? (int) date('Y')),
            month:            isset($payload['month'])   ? (int) $payload['month']   : null,
            week:             isset($payload['week'])    ? (int) $payload['week']    : null,
            quarter:          isset($payload['quarter']) ? (int) $payload['quarter'] : null,
            exportFormat:     (string) ($payload['exportFormat']     ?? 'pdf'),
            reportLanguage:   (string) ($payload['reportLanguage']   ?? 'ar'),
            paperSize:        (string) ($payload['paperSize']        ?? 'A4'),
            printOrientation: (string) ($payload['printOrientation'] ?? 'portrait'),
        );
    }

    public function toArray(): array
    {
        return [
            'reportTypeIds'    => $this->reportTypeIds,
            'periodType'       => $this->periodType,
            'year'             => $this->year,
            'month'            => $this->month,
            'week'             => $this->week,
            'quarter'          => $this->quarter,
            'exportFormat'     => $this->exportFormat,
            'reportLanguage'   => $this->reportLanguage,
            'paperSize'        => $this->paperSize,
            'printOrientation' => $this->printOrientation,
        ];
    }
}
