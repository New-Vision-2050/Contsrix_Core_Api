<?php

declare(strict_types=1);

namespace Modules\Reports\DTO;

/**
 * Step 4 — Salary components & deductions (بيانات الرواتب).
 */
final class ReportWizardStep4DTO
{
    public function __construct(
        /** @var string[] */
        public readonly array  $salaryComponentIds,
        /** @var string[] */
        public readonly array  $deductionIds,
        public readonly string $disbursementStatus,
        public readonly bool   $netSalaryOnly,
        public readonly bool   $compareWithPreviousMonth,
        public readonly bool   $employeeDetailsSeparatePage,
        public readonly bool   $addTotalSummaryEnd,
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            salaryComponentIds:          array_values($payload['salaryComponentIds'] ?? []),
            deductionIds:                array_values($payload['deductionIds']       ?? []),
            disbursementStatus:          (string) ($payload['disbursementStatus']    ?? 'all'),
            netSalaryOnly:               (bool)   ($payload['netSalaryOnly']               ?? false),
            compareWithPreviousMonth:    (bool)   ($payload['compareWithPreviousMonth']    ?? false),
            employeeDetailsSeparatePage: (bool)   ($payload['employeeDetailsSeparatePage'] ?? false),
            addTotalSummaryEnd:          (bool)   ($payload['addTotalSummaryEnd']          ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'salaryComponentIds'          => $this->salaryComponentIds,
            'deductionIds'                => $this->deductionIds,
            'disbursementStatus'          => $this->disbursementStatus,
            'netSalaryOnly'               => $this->netSalaryOnly,
            'compareWithPreviousMonth'    => $this->compareWithPreviousMonth,
            'employeeDetailsSeparatePage' => $this->employeeDetailsSeparatePage,
            'addTotalSummaryEnd'          => $this->addTotalSummaryEnd,
        ];
    }
}
