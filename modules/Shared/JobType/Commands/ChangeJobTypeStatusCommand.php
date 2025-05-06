<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Commands;

use Ramsey\Uuid\UuidInterface;

class ChangeJobTypeStatusCommand
{
    public function __construct(
        private UuidInterface $id,
        private int $status
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status
        ];
    }
}
