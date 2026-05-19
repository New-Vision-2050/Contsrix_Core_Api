<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Commands;

use Modules\MedicalInsurance\DTO\CreateMedicalInsuranceSubscriptionDTO;

class BulkReplaceMedicalInsuranceSubscriptionsCommand
{
    /**
     * @param array<CreateMedicalInsuranceSubscriptionDTO> $dtos
     */
    public function __construct(
        private array $dtos,
    ) {
    }

    /**
     * @return array<CreateMedicalInsuranceSubscriptionDTO>
     */
    public function getDtos(): array
    {
        return $this->dtos;
    }
}
