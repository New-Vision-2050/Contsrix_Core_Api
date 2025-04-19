<?php

declare(strict_types=1);

namespace Modules\Country\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateCountryCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private string $status,
        private UuidInterface $smsDriverId
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
    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'status' => $this->status,
            'sms_driver_id' => $this->smsDriverId
        ]);
    }
}
