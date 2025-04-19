<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Commands;

use Ramsey\Uuid\UuidInterface;

class TakeActionOnAdminRequestCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $status,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function toArray(): array
    {
        return [
            "status" => $this->status
        ];
    }
}
