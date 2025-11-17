<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteSetting\DTO;

use Illuminate\Http\UploadedFile;

readonly class CreateWebsiteSettingDTO
{
    public function __construct(
        public ?string $main_color = null,
        public ?string $second_color = null,
        public ?string $background_color = null,
        public ?UploadedFile $logo = null,
        public ?string $website_address = null,
        public string $name,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'main_color' => $this->main_color,
            'second_color' => $this->second_color,
            'background_color' => $this->background_color,
            'website_address' => $this->website_address,
        ];
    }

    public function getMainColor(): ?string
    {
        return $this->main_color;
    }

    public function getSecondColor(): ?string
    {
        return $this->second_color;
    }

    public function getBackgroundColor(): ?string
    {
        return $this->background_color;
    }

    public function getLogo(): ?UploadedFile
    {
        return $this->logo;
    }

    public function getWebsiteAddress(): ?string
    {
        return $this->website_address;
    }
}
