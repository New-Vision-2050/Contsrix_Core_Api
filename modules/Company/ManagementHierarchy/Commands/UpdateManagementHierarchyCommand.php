<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateManagementHierarchyCommand
{
    public function __construct(
        private int $id,
        private string $name,
    ) {
    }

    public function getId(): int
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
