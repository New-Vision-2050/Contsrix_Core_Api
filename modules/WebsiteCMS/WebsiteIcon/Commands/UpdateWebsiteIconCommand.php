<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\Commands;

use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\WebsiteIcon\Enums\WebsiteIconCategoryType;

class UpdateWebsiteIconCommand
{
    public function __construct(
        private UuidInterface $id,
        private array $name,
        private ?UploadedFile $icon,
        private WebsiteIconCategoryType $website_icon_category_type,
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

    public function getWebsiteIconCategoryType(): WebsiteIconCategoryType
    {
        return $this->website_icon_category_type;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'website_icon_category_type' => $this->website_icon_category_type->value,
        ];
    }
}
