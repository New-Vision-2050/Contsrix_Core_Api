<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTheme\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\WebsiteCMS\WebsiteTheme\DTO\UpdateWebsiteThemeDTO;

class UpdateWebsiteThemeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'url' => 'nullable|unique:website_themes,url|string|max:255',
            'radius' => 'nullable|integer|min:0',
            'html_font_size' => 'nullable|integer|min:1',
            'font_family' => 'nullable|string',
            'font_size' => 'nullable|string|max:50',
            'font_weight_light' => 'nullable|string|max:50',
            'font_weight_regular' => 'nullable|string|max:50',
            'font_weight_medium' => 'nullable|string|max:50',
            'font_weight_bold' => 'nullable|string|max:50',
            'color_palettes' => 'required|array',
            'color_palettes.*.slug' => 'required|string|max:255',
            'color_palettes.*.name' => 'required|string|max:255',
            'color_palettes.*.primary' => 'nullable|string|max:50',
            'color_palettes.*.light' => 'nullable|string|max:50',
            'color_palettes.*.dark' => 'nullable|string|max:50',
            'color_palettes.*.contrast' => 'nullable|string|max:50',
            'color_palettes.*.divider' => 'nullable|string|max:50',
            'color_palettes.*.paper' => 'nullable|string|max:50',
            'color_palettes.*.default' => 'nullable|string|max:50',
            'color_palettes.*.black' => 'nullable|string|max:50',
            'color_palettes.*.white' => 'nullable|string|max:50',
            'color_palettes.*.disabled' => 'nullable|string|max:50',



            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ];
    }

    public function toDTO(): UpdateWebsiteThemeDTO
    {
        return new UpdateWebsiteThemeDTO(
            url: $this->get('url'),
            radius: $this->get('radius') ? (int) $this->get('radius') : null,
            html_font_size: $this->get('html_font_size') ? (int) $this->get('html_font_size') : null,
            font_family: $this->get('font_family'),
            font_size: $this->get('font_size'),
            font_weight_light: $this->get('font_weight_light'),
            font_weight_regular: $this->get('font_weight_regular'),
            font_weight_medium: $this->get('font_weight_medium'),
            font_weight_bold: $this->get('font_weight_bold'),
            color_palettes: $this->get('color_palettes'),
            icon: $this->file('icon'),
        );
    }
}
