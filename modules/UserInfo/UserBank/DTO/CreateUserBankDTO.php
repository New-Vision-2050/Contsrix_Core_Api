<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserBank\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateUserBankDTO
{
    public function __construct(
        public string $name,
    ) {
    }

    public function toArray(): array
    {
        return [
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
        ];
    }
}
