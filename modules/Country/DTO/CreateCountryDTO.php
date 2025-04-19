<?php

declare(strict_types=1);

namespace Modules\Country\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateCountryDTO
{
    public function __construct(
        public string $name,
        private UuidInterface $smsDriverId,
        private string $status
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status,
            'sms_driver_id' => $this->smsDriverId
        ];
    }
}
