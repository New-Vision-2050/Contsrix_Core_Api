<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateJobTypeCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private int $status
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

    public function getStatus(): int
    {
        return $this->status;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'company_id' => tenant("id"),
            'status' => $this->status
        ];
    }
}
