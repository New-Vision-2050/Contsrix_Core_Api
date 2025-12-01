<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteThemeSetting\DTO;

use Illuminate\Http\UploadedFile;

class UpdateWebsiteThemeSettingDTO
{
    public function __construct(
        public readonly ?array $title = null,
        public readonly ?array $description = null,
        public readonly ?array $about = null,
        public readonly ?array $departments = null,
        public readonly ?UploadedFile $main_image = null,
        public readonly ?bool $is_default = null,
    ) {
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->title !== null) {
            $data['title'] = $this->title;
        }
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        if ($this->about !== null) {
            $data['about'] = $this->about;
        }
        if ($this->is_default !== null) {
            $data['is_default'] = $this->is_default;
        }

        return $data;
    }
}
