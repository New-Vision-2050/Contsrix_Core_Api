<?php

declare(strict_types=1);

namespace Modules\UserInfo\BankAccount\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateBankAccountDTO
{
    public function __construct(
        public string $company_id,
        public string $global_id,
        public string $country_id,
        public string $bank_id,
        public string $currency_id,
        public string $user_name,
        public string $account_number,
        public ?string $iban,
        public ?string $swift_bic,
        public string $type_id,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'global_id' => $this->global_id,
            'country_id' => $this->country_id,
            'bank_id' => $this->bank_id,
            'currency_id' => $this->currency_id,
            'user_name' => $this->user_name,
            'account_number' => $this->account_number,
            'iban' => $this->iban,
            'swift_bic' => $this->swift_bic,
            'type_id' => $this->type_id
        ];
    }
}
