<?php

declare(strict_types=1);

namespace Modules\Reports\DTO;

/**
 * Aggregate of the full wizard payload across all 5 steps. Used internally as
 * the canonical immutable shape for storage, generation, and template re-use.
 */
final class ReportWizardConfigDTO
{
    public function __construct(
        public readonly ReportWizardStep1DTO $step1,
        public readonly ReportWizardStep2DTO $step2,
        public readonly ReportWizardStep3DTO $step3,
        public readonly ReportWizardStep4DTO $step4,
        public readonly ReportWizardStep5DTO $step5,
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            step1: ReportWizardStep1DTO::fromArray($payload['step1'] ?? []),
            step2: ReportWizardStep2DTO::fromArray($payload['step2'] ?? []),
            step3: ReportWizardStep3DTO::fromArray($payload['step3'] ?? []),
            step4: ReportWizardStep4DTO::fromArray($payload['step4'] ?? []),
            step5: ReportWizardStep5DTO::fromArray($payload['step5'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'step1' => $this->step1->toArray(),
            'step2' => $this->step2->toArray(),
            'step3' => $this->step3->toArray(),
            'step4' => $this->step4->toArray(),
            'step5' => $this->step5->toArray(),
        ];
    }
}
