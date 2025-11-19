<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\Commands;

use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\UuidInterface;

class UpdateWebsiteIconCommand
{
    public function __construct(
        private UuidInterface $id,
        private array $name,
        private ?UploadedFile $icon,
        private string $category_website_cms_id,
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
