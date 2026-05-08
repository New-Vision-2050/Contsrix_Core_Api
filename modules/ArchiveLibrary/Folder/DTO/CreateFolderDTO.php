<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\DTO;

use Illuminate\Http\UploadedFile;
use Modules\ArchiveLibrary\Folder\Handlers\UpdateFolderHandler;
use Ramsey\Uuid\UuidInterface;

class CreateFolderDTO
{
    public function __construct(
        public string $name,
        public ?string $parentId,
        public ?string $projectId,
        public ?string $password,
        public string $accessType,
        public array $userIds=[],
        private ?UploadedFile $file,
        public int $status = 1,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'parent_id'=> $this->parentId,
            'project_id'=> $this->projectId,
            "password"=>$this->password,
            "access_type"=>$this->accessType,
            "status"=>$this->status
        ];
    }
    public function getUserIds()
    {
        return $this->userIds;
    }

    public function getFile()
    {
        return $this->file;
    }
}
