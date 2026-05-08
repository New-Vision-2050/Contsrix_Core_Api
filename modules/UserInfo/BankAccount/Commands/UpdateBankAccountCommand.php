<?php

declare(strict_types=1);

namespace Modules\UserInfo\BankAccount\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateBankAccountCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $country_id,
        private string $bank_id,
        private string $currency_id,
        private string $user_name,
        private string $account_number,
        private string $iban,
        private ?string $swift_bic,
        private string $type_id,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return array_filter([
            'country_id' => $this->country_id,
            'bank_id' => $this->bank_id,
            'currency_id' => $this->currency_id,
            'user_name' => $this->user_name,
            'account_number' => $this->account_number,
            'iban' => $this->iban,
            'swift_bic' => $this->swift_bic,
            'type_id'=> $this->type_id,
        ]);
    }
}
