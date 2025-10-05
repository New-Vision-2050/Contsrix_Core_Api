<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\DTO;

use Illuminate\Http\UploadedFile;
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
