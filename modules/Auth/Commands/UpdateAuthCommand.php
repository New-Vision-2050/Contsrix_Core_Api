<?php

declare(strict_types=1);

namespace Modules\Auth\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateAuthCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
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
        ]);
    }
}
