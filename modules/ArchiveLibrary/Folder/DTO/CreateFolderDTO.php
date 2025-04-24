<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateFolderDTO
{
    public function __construct(
        public string $name,
        public ?string $parentId
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'parent_id'=> $this->parentId
        ];
    }
}
