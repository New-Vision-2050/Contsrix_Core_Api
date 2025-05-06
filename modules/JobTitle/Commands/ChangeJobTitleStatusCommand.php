<?php

declare(strict_types=1);

namespace Modules\JobTitle\Commands;

use Ramsey\Uuid\UuidInterface;

class ChangeJobTitleStatusCommand
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
