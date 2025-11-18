<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\Founder\DTO;

use Ramsey\Uuid\UuidInterface;
use Illuminate\Http\UploadedFile;

class CreateFounderDTO
{
    public function __construct(
        public readonly array $name,
        public readonly array $description,
        public readonly array $job_title,
        public readonly ?UploadedFile $personal_photo = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'job_title' => $this->job_title,
        ];
    }

    public function getPersonalPhoto(): ?UploadedFile
    {
        return $this->personal_photo;
    }
}
