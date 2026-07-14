<?php

declare(strict_types=1);

namespace Modules\Country\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateStateCommand
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
        ]);
    }
}
