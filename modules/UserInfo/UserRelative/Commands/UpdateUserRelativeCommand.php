<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserRelative\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateUserRelativeCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private string $marital_status,
        private string $relationship,
        private string $phone,
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
            'marital_status' => $this->marital_status,
            'relationship' => $this->relationship,
            'phone' => $this->phone,
        ]);
    }
}
