<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteThemeSetting\DTO;

use Illuminate\Http\UploadedFile;

class CreateWebsiteThemeSettingDTO
{
    public function __construct(
        public readonly array $title,
        public readonly array $description,
        public readonly array $about,
        public readonly array $departments,
        public readonly ?UploadedFile $main_image = null,
        public readonly bool $is_default = false,
    ) {
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'about' => $this->about,
            'is_default' => $this->is_default,
        ];
    }
}
