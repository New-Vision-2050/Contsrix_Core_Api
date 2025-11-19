<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\DTO;

use Illuminate\Http\UploadedFile;

class CreateWebsiteIconDTO
{
    public function __construct(
        public readonly array $name,
        public readonly ?UploadedFile $icon,
        public readonly string $category_website_cms_id,
    ) {
    }

    public function getName(): array
    {
        return $this->name;
    }

    public function getIcon(): ?UploadedFile
    {
        return $this->icon;
    }

    public function getCategoryWebsiteCmsId(): string
    {
        return $this->category_website_cms_id;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'category_website_cms_id' => $this->category_website_cms_id,
        ];
    }
}
