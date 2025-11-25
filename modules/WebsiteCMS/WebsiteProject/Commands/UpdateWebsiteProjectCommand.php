<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\Commands;

use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\UuidInterface;

class UpdateWebsiteProjectCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $websiteProjectSettingId,
        private array $title,
        private array $name,
        private array $description,
        private ?UploadedFile $mainImage = null,
        private ?UploadedFile $secondaryImage = null,
        private array $projectDetails = [],
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getWebsiteProjectSettingId(): string
    {
        return $this->websiteProjectSettingId;
    }

    public function getTitle(): array
    {
        return $this->title;
    }

    public function getName(): array
    {
        return $this->name;
    }

    public function getDescription(): array
    {
        return $this->description;
    }

    public function getMainImage(): ?UploadedFile
    {
        return $this->mainImage;
    }

    public function getSecondaryImage(): ?UploadedFile
    {
        return $this->secondaryImage;
    }

    public function getProjectDetails(): array
    {
        return $this->projectDetails;
    }

    public function toArray(): array
    {
        return [
            'website_project_setting_id' => $this->websiteProjectSettingId,
            'title' => $this->title,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
