<?php

declare(strict_types=1);

namespace Modules\Reports\Services;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Modules\Reports\DTO\ReportWizardStep1DTO;
use Modules\Reports\Enums\ReportEnums;

/**
 * Converts the `periodType + year + month/week/quarter` fields from Step 1 of
 * the wizard into concrete `[start, end]` dates used by queries + persistence.
 */
class ReportPeriodResolver
{
    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    public function resolve(ReportWizardStep1DTO $step1): array
    {
        $year = $step1->year;

        return match ($step1->periodType) {
            ReportEnums::PERIOD_MONTHLY   => $this->monthly($year,   $step1->month   ?? (int) date('n')),
            ReportEnums::PERIOD_WEEKLY    => $this->weekly($year,    $step1->week    ?? (int) CarbonImmutable::now()->isoWeek),
            ReportEnums::PERIOD_QUARTERLY => $this->quarterly($year, $step1->quarter ?? (int) ceil(((int) date('n')) / 3)),
            ReportEnums::PERIOD_YEARLY    => $this->yearly($year),
            ReportEnums::PERIOD_RANGE     => $this->range($step1->dateFrom, $step1->dateTo),
            default => throw new InvalidArgumentException("Unsupported period type: {$step1->periodType}"),
        };
    }

    /** @return array{start: CarbonImmutable, end: CarbonImmutable} */
    private function monthly(int $year, int $month): array
    {
        $start = CarbonImmutable::create($year, $month, 1, 0, 0, 0);
        return ['start' => $start, 'end' => $start->endOfMonth()];
    }

    /** @return array{start: CarbonImmutable, end: CarbonImmutable} */
    private function weekly(int $year, int $week): array
    {
        $start = CarbonImmutable::now()->setISODate($year, $week)->startOfWeek();
        return ['start' => $start, 'end' => $start->endOfWeek()];
    }

    /** @return array{start: CarbonImmutable, end: CarbonImmutable} */
    private function quarterly(int $year, int $quarter): array
    {
        $startMonth = (($quarter - 1) * 3) + 1;
        $start      = CarbonImmutable::create($year, $startMonth, 1, 0, 0, 0);
        return ['start' => $start, 'end' => $start->addMonths(2)->endOfMonth()];
    }

    /** @return array{start: CarbonImmutable, end: CarbonImmutable} */
    private function yearly(int $year): array
    {
        $start = CarbonImmutable::create($year, 1, 1, 0, 0, 0);
        return ['start' => $start, 'end' => $start->endOfYear()];
    }

    /** @return array{start: CarbonImmutable, end: CarbonImmutable} */
    private function range(?string $dateFrom, ?string $dateTo): array
    {
        if (!$dateFrom || !$dateTo) {
            throw new InvalidArgumentException('periodType=range requires both dateFrom and dateTo.');
        }
        $start = CarbonImmutable::parse($dateFrom)->startOfDay();
        $end   = CarbonImmutable::parse($dateTo)->endOfDay();
        return ['start' => $start, 'end' => $end];
    }
}
