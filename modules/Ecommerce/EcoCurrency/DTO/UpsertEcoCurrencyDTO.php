<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCurrency\DTO;

use Ramsey\Uuid\UuidInterface;

class UpsertEcoCurrencyDTO
{
    public function __construct(
        private UuidInterface $companyId,
        private array $currencies,
    ) {
    }

    public function getCompanyId(): UuidInterface
    {
        return $this->companyId;
    }

    public function getCurrencies(): array
    {
        return $this->currencies;
    }
}
