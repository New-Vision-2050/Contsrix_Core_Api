<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\DTO;

use Illuminate\Http\UploadedFile;
use Modules\Subscription\Enums\PeriodUnitEnum;
use Ramsey\Uuid\UuidInterface;

class CreateFileDTO
{
    public function __construct(
        public string       $name,
        public string       $referenceNumber,
        public string       $startDate,
        public string       $endDate,
        public array        $userIds = [],
        public UploadedFile $file,
        public string $accessType,
        public ?string $folderId,
        public ?string $projectId,
        public int $status = 1,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'reference_number' => $this->referenceNumber,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            "access_type"=>$this->accessType,
            "folder_id"=>$this->folderId,
            "project_id"=>$this->projectId,
            "status"=>$this->status
        ];
    }

    public function getUserIds(): array
    {
        return $this->userIds;
    }

    public function getFile()
    {
        return $this->file;
    }
}
