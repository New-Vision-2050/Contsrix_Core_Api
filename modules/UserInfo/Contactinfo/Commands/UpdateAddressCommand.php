<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateAddressCommand
{
    public function __construct(
        public string $company_id,
        public string $global_id,
        public ?string $address,
        public ?string $postal_code,
    ) {
    }


    public function toArray(): array
    {
        return ([
            'company_id' => $this->company_id,
            'global_id' => $this->global_id,
            'address'=> $this->address,
            'postal_code'=> $this->postal_code,
        ]);
    }
}
