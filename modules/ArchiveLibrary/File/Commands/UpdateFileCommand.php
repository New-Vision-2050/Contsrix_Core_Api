<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Commands;

use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\UuidInterface;

class UpdateFileCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private string $referenceNumber,
        private string $accessType,
        private string $startDate,
        private string $endDate,
        private array $userIds = [],
        private ?UploadedFile  $file,
        private ?string $folderId,
        private ?string $projectId,
        private ?int $status = null
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

    public function getReferenceNumber(): string
    {
        return $this->referenceNumber;
    }

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function getEndDate(): string
    {
        return $this->endDate;
    }

    public function getUserIds(): array
    {
        return $this->userIds;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            "access_type"=>$this->accessType,
            'reference_number' => $this->referenceNumber,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            "folder_id"=>$this->folderId,
            "project_id"=>$this->projectId,
            "status"=>$this->status
        ], fn($value) => !is_null($value));
    }

    public function getFile()
    {
        return $this->file;
    }
}
