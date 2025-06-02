<?php

declare(strict_types=1);

namespace Modules\Shared\BankTypeAccount\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateBankTypeAccountDTO
{
    public function __construct(
        public string $code,
    ) {
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
        ];
    }
}
