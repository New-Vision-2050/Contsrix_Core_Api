<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoComplaint\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateEcoComplaintCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?string $name = null,
        private ?string $status = null,
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

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'status' => $this->status,
        ], fn ($value) => !is_null($value));
    }
}
