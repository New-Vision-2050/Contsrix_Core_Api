<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\DTO;

use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\UuidInterface;

class CreateWebsiteProjectDTO
{
    public function __construct(
        public readonly string $websiteProjectSettingId,
        public readonly array $title,
        public readonly array $name,
        public readonly array $description,
        public readonly ?UploadedFile $mainImage = null,
        public readonly ?UploadedFile $secondaryImage = null,
        public readonly array $projectDetails = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'website_project_setting_id' => $this->websiteProjectSettingId,
            'title' => $this->title,
            'name' => $this->name,
            'description' => $this->description,
            'company_id' => tenant('id'),
            'status' => 1,
        ];
    }
}
