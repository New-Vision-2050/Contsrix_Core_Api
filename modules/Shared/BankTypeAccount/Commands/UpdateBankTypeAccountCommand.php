<?php

declare(strict_types=1);

namespace Modules\Shared\BankTypeAccount\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateBankTypeAccountCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $code,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
        ]);
    }
}
