<?php

declare(strict_types=1);

namespace Modules\Program\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateProgramCommand
{
    public function __construct(
        private UuidInterface $id,
        private array $name,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): ?array
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
