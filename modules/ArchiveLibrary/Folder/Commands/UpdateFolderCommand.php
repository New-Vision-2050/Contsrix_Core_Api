<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateFolderCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private ?string $parentId,
        private ?string $password,
        private string $accessType,
        private array $userIds = []
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getAccessType(): string
    {
        return $this->accessType;
    }

    public function getUserIds(): array
    {
        return $this->userIds;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'parent_id' => $this->parentId,
            'password' => $this->password,
            'access_type' => $this->accessType,
        ], fn($value) => $value !== null);
    }
}
