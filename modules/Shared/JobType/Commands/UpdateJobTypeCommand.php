<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateJobTypeCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private ?bool $status = null
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

    public function getStatus(): ?bool
    {
        return $this->status;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'company_id' => tenant("id"),
            'status' => $this->status
        ], function ($value) {
            return $value !== null;
        });
    }
}
