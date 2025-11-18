<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\Founder\Commands;

use Ramsey\Uuid\UuidInterface;
use Illuminate\Http\UploadedFile;

class UpdateFounderCommand
{
    public function __construct(
        private UuidInterface $id,
        private array $name,
        private array $description,
        private array $job_title,
        private ?UploadedFile $personal_photo = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): array
    {
        return $this->name;
    }

    public function getDescription(): array
    {
        return $this->description;
    }

    public function getJobTitle(): array
    {
        return $this->job_title;
    }

    public function getPersonalPhoto(): ?UploadedFile
    {
        return $this->personal_photo;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'job_title' => $this->job_title,
        ];
    }
}
