<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserBank\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateUserBankCommand
{
    public function __construct(
        private UuidInterface $id,
        private string company_id,
        private string $global_id,
        private string $country_id,
        private string $bank_id,
        private string $currency_id,
        private string $user_name,
        private string $account_number,
        private string $iban,
        private string $swift_bic,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'company_id'
            'global_id'
            'country_id'
            'bank_id'
            'currency_id'
            'user_name'
            'account_number'
            'iban'
            'swift_bic'
        ]);
    }
}
