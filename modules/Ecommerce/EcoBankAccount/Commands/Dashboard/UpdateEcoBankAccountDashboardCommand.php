<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBankAccount\Commands\Dashboard;

use Ramsey\Uuid\UuidInterface;

class UpdateEcoBankAccountDashboardCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $bankId,
        private string $accountHolderName,
        private string $accountNumber,
        private string $iban,
        private string $countryId,
        private bool $isPrimary = false,
        private bool $isActive = true,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getBankId(): string
    {
        return $this->bankId;
    }

    public function getAccountHolderName(): string
    {
        return $this->accountHolderName;
    }

    public function getAccountNumber(): string
    {
        return $this->accountNumber;
    }

    public function getIban(): string
    {
        return $this->iban;
    }

    public function getCountryId(): string
    {
        return $this->countryId;
    }

    public function getIsPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function toArray(): array
    {
        return [
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
