<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateFolderCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private ?string $parentId
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
    public function getparentId(): ?string
    {
        return $this->parentId ;
    }
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'parent_id' => $this->parentId
        ]);
    }
}
