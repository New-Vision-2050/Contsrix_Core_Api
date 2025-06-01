<?php

declare(strict_types=1);

namespace Modules\UserInfo\BankAccount\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateTypeBankAccountCommand
{
    public function __construct(
        private UuidInterface $id,
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
            'type_id'=> $this->type_id,
        ]);
    }
}
