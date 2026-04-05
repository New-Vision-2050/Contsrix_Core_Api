<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Commands;

use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\UuidInterface;

class UpdateFolderCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private ?string $parentId,
        private ?string $projectId,
        private ?string $password,
        private string $accessType,
        private array $userIds = [],
        private ?UploadedFile $file,
        private ?int $status = null
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

    public function getProjectId(): ?string
    {
        return $this->projectId;
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
        $data = [
            'name' => $this->name,
            'parent_id' => $this->parentId,
            'project_id' => $this->projectId,
            'access_type' => $this->accessType,
        ];

        if($this->password !== null) {
            $data['password'] = $this->password;
        }

        if($this->status !== null) {
            $data['status'] = $this->status;
        }

        return $data;
    }

    public function getFile()
    {
        return $this->file;
    }
}
