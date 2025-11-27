<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTheme\Presenters;

use Modules\WebsiteCMS\WebsiteTheme\Models\WebsiteTheme;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WebsiteThemePresenter extends AbstractPresenter
{
    private WebsiteTheme $websiteTheme;

    public function __construct(WebsiteTheme $websiteTheme)
    {
        $this->websiteTheme = $websiteTheme;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->websiteTheme->id,
            'company_id' => $this->websiteTheme->company_id,
            'url' => $this->websiteTheme->url,
            'radius' => $this->websiteTheme->radius,
            'html_font_size' => $this->websiteTheme->html_font_size,
            'font_family' => $this->websiteTheme->font_family,
            'font_size' => $this->websiteTheme->font_size,
            'font_weight_light' => $this->websiteTheme->font_weight_light,
            'font_weight_regular' => $this->websiteTheme->font_weight_regular,
            'font_weight_medium' => $this->websiteTheme->font_weight_medium,
            'font_weight_bold' => $this->websiteTheme->font_weight_bold,
            'status' => $this->websiteTheme->status,
            'created_at' => $this->websiteTheme->created_at?->toDateTimeString(),
            'updated_at' => $this->websiteTheme->updated_at?->toDateTimeString(),
        ];

        // Add color palettes if loaded (grouped by slug)
        if ($this->websiteTheme->relationLoaded('colorPalettes')) {
            $data['color_palettes'] = [];
            foreach ($this->websiteTheme->colorPalettes as $palette) {
                $data['color_palettes'][$palette->slug] = [
                    'id' => $palette->id,
                    'name' => $palette->name,
                    'primary' => $palette->primary,
                    'light' => $palette->light,
                    'dark' => $palette->dark,
                    'contrast' => $palette->contrast,
                ];
            }
        }

        // Add icon if loaded
        if ($this->websiteTheme->relationLoaded('media')) {
            $iconMedia = $this->websiteTheme->getFirstMedia('icon');
            if ($iconMedia) {
                $data['icon'] = [
                    'id' => $iconMedia->id,
                    'name' => $iconMedia->name,
                    'file_name' => $iconMedia->file_name,
                    'url' => $iconMedia->getUrl(),
                    'size' => $iconMedia->size,
                    'mime_type' => $iconMedia->mime_type,
                ];
            }
        }

        return $data;
    }
}
