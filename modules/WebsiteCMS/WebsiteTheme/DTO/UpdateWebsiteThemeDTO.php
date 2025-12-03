<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTheme\DTO;

use Illuminate\Http\UploadedFile;

class UpdateWebsiteThemeDTO
{
    public function __construct(
        public readonly ?string $url = null,
        public readonly ?int $radius = null,
        public readonly ?int $html_font_size = null,
        public readonly ?string $font_family = null,
        public readonly ?string $font_size = null,
        public readonly ?string $font_weight_light = null,
        public readonly ?string $font_weight_regular = null,
        public readonly ?string $font_weight_medium = null,
        public readonly ?string $font_weight_bold = null,
        public readonly ?array $color_palettes = null,
        public readonly ?UploadedFile $icon = null,
    ) {
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->url !== null) {
            $data['url'] = $this->url;
        }
        if ($this->radius !== null) {
            $data['radius'] = $this->radius;
        }
        if ($this->html_font_size !== null) {
            $data['html_font_size'] = $this->html_font_size;
        }
        if ($this->font_family !== null) {
            $data['font_family'] = $this->font_family;
        }
        if ($this->font_size !== null) {
            $data['font_size'] = $this->font_size;
        }
        if ($this->font_weight_light !== null) {
            $data['font_weight_light'] = $this->font_weight_light;
        }
        if ($this->font_weight_regular !== null) {
            $data['font_weight_regular'] = $this->font_weight_regular;
        }
        if ($this->font_weight_medium !== null) {
            $data['font_weight_medium'] = $this->font_weight_medium;
        }
        if ($this->font_weight_bold !== null) {
            $data['font_weight_bold'] = $this->font_weight_bold;
        }

        return $data;
    }
}
