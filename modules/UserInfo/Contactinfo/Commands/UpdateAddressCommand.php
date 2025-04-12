<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateAddressCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $address,
        private string $postal_code,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }


    public function toArray(): array
    {
        return array_filter([
            'address'=> $this->address,
            'postal_code'=> $this->postal_code,
        ]);
    }
}
