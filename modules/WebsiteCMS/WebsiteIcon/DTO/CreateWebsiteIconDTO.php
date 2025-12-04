<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\DTO;

use Illuminate\Http\UploadedFile;
use Modules\WebsiteCMS\WebsiteIcon\Enums\WebsiteIconCategoryType;

class CreateWebsiteIconDTO
{
    public function __construct(
        public readonly array $name,
        public readonly ?UploadedFile $icon,
        public readonly WebsiteIconCategoryType $website_icon_category_type,
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
