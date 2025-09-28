<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBankAccount\DTO\Dashboard;

use Ramsey\Uuid\UuidInterface;

class CreateEcoBankAccountDashboardDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public string $bankId,
        public string $accountHolderName,
        public string $accountNumber,
        public string $iban,
        public string $countryId,
        public bool $isPrimary = false,
        public bool $isActive = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'bank_id' => $this->bankId,
            'account_holder_name' => $this->accountHolderName,
            'account_number' => $this->accountNumber,
            'iban' => $this->iban,
            'country_id' => $this->countryId,
            'is_primary' => $this->isPrimary,
            'is_active' => $this->isActive,
        ];
    }
}
